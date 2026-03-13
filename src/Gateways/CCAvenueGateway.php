<?php

declare(strict_types=1);

namespace MrRijal\CCAvenue\Gateways;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View as ViewFactory;
use MrRijal\CCAvenue\Exceptions\PaymentGatewayException;

/**
 * CCAvenue payment gateway implementation.
 *
 * Handles encryption, API endpoints (test/live), payment request building,
 * response decryption, and order status checks per CCAvenue API 1.1.
 */
class CCAvenueGateway
{
    /** @var array<string, mixed> Request parameters sent to CCAvenue */
    protected array $parameters = [];

    /** @var string Concatenated merchant data string (key=value&...) */
    protected string $merchantData = '';

    /** @var string AES-encrypted request payload */
    protected string $encRequest = '';

    protected bool $testMode = false;

    protected string $workingKey = '';

    protected string $accessCode = '';

    /** Live web transaction URL */
    protected string $liveEndPoint = 'https://secure.ccavenue.com/transaction/transaction.do?command=';

    /** Test web transaction URL */
    protected string $testEndPoint = 'https://test.ccavenue.com/transaction/transaction.do?command=';

    /** Live API URL (CCAvenue API 1.1) */
    protected string $apiLiveEndPoint = 'https://api.ccavenue.com/apis/servlet/DoWebTrans?';

    /** Test API URL */
    protected string $apiTestEndPoint = 'https://apitest.ccavenue.com/apis/servlet/DoWebTrans?';

    protected string $apiVersion = '1.1';

    protected string $apiRequestType = 'JSON';

    public function __construct()
    {
        $this->workingKey = (string) Config::get('ccavenue.workingKey', '');
        $this->accessCode = (string) Config::get('ccavenue.accessCode', '');
        $this->testMode = (bool) Config::get('ccavenue.testMode', true);
        $this->parameters['merchant_id'] = Config::get('ccavenue.merchantId');
        $this->parameters['currency'] = Config::get('ccavenue.currency');
        $this->parameters['redirect_url'] = url((string) Config::get('ccavenue.redirectUrl', ''));
        $this->parameters['cancel_url'] = url((string) Config::get('ccavenue.cancelUrl', ''));
        $this->parameters['language'] = Config::get('ccavenue.language');
    }

    /**
     * Get the web transaction endpoint (test or live) for redirects.
     */
    public function getEndPoint(): string
    {
        return $this->testMode ? $this->testEndPoint : $this->liveEndPoint;
    }

    /**
     * Get the server-to-server API endpoint (test or live).
     */
    public function getAPIEndPoint(): string
    {
        return $this->testMode ? $this->apiTestEndPoint : $this->apiLiveEndPoint;
    }

    /**
     * Override API endpoints and version (e.g. for custom CCAvenue setup).
     *
     * @param  string  $testEndpoint  Optional test API base URL
     * @param  string  $liveEndpoint  Optional live API base URL
     * @param  string  $apiVersion  API version (default 1.1)
     */
    public function initializeApiRequest(
        string $testEndpoint = '',
        string $liveEndpoint = '',
        string $apiVersion = '1.1'
    ): self {
        if ($liveEndpoint !== '') {
            $this->apiLiveEndPoint = $liveEndpoint;
        }
        if ($testEndpoint !== '') {
            $this->apiTestEndPoint = $testEndpoint;
        }
        if ($apiVersion !== '') {
            $this->apiVersion = $apiVersion;
        }

        return $this;
    }

    /**
     * Build and encrypt the payment request; returns $this for chaining with send().
     *
     * @param  array<string, mixed>  $parameters  order_id, amount, and any overrides
     *
     * @throws PaymentGatewayException When required parameters are missing or invalid
     */
    public function request(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        $this->checkParameters($this->parameters);

        $this->merchantData = '';
        foreach ($this->parameters as $key => $value) {
            $this->merchantData .= $key.'='.$value.'&';
        }

        $this->encRequest = $this->encrypt($this->merchantData, $this->workingKey);

        return $this;
    }

    /**
     * Return the view that auto-submits the form to CCAvenue (initiate transaction).
     */
    public function send(): View
    {
        Log::info('CCAvenue payment gateway: initiating transaction');

        return ViewFactory::make('ccavenue::ccavenue')
            ->with('encRequest', $this->encRequest)
            ->with('accessCode', $this->accessCode)
            ->with('endPoint', $this->getEndPoint().'initiateTransaction');
    }

    /**
     * Decrypt CCAvenue response and return decoded parameters.
     *
     * @param  object  $request  Object with encResp property (e.g. Illuminate\Http\Request)
     * @return array<string, string> Decrypted key-value pairs
     */
    public function response(object $request): array
    {
        $encResponse = $request->encResp ?? '';
        $rcvdString = $this->decrypt((string) $encResponse, $this->workingKey);
        parse_str($rcvdString, $decResponse);

        return is_array($decResponse) ? $decResponse : [];
    }

    /**
     * Validate required payment parameters.
     *
     * @param  array<string, mixed>  $parameters
     *
     * @throws PaymentGatewayException When validation fails
     */
    public function checkParameters(array $parameters): void
    {
        $validator = Validator::make($parameters, [
            'merchant_id' => 'required',
            'currency' => 'required',
            'redirect_url' => 'required|url',
            'cancel_url' => 'required|url',
            'language' => 'required',
            'order_id' => 'required',
            'amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            throw new PaymentGatewayException($validator->errors()->toJson());
        }
    }

    /**
     * Decrypt CCAvenue response string (AES-128-CBC per CCAvenue spec).
     *
     * @param  string  $encryptedText  Hex-encoded ciphertext
     * @param  string  $key  Working key
     */
    public function decrypt(string $encryptedText, string $key): string
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E, 0x0F);
        $encryptedText = $this->hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);

        return $decryptedText !== false ? $decryptedText : '';
    }

    /**
     * Encrypt plain text for CCAvenue (AES-128-CBC).
     *
     * @param  string  $plainText  Query string or JSON payload
     * @param  string  $key  Working key
     */
    public function encrypt(string $plainText, string $key): string
    {
        $key = $this->hextobin(md5($key));
        $initVector = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E, 0x0F);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);

        return $openMode !== false ? bin2hex($openMode) : '';
    }

    /**
     * PKCS5 padding (for block cipher; kept for compatibility if needed).
     */
    public function pkcs5_pad(string $plainText, int $blockSize): string
    {
        $pad = $blockSize - (strlen($plainText) % $blockSize);

        return $plainText.str_repeat(chr($pad), $pad);
    }

    /**
     * Convert hex string to binary (used by encrypt/decrypt).
     */
    public function hextobin(string $hexString): string
    {
        $length = strlen($hexString);
        $binString = '';
        $count = 0;

        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack('H*', $subString);
            $binString = $count === 0 ? $packedString : $binString.$packedString;
            $count += 2;
        }

        return $binString;
    }

    /**
     * Fetch order/transaction status from CCAvenue order status API.
     *
     * @param  string|int  $orderNumber  Your order reference
     * @param  int  $transactionId  Optional CCAvenue transaction reference
     * @return array<string, mixed>|false Decoded order data or false on failure
     */
    public function getOrderDetails(string|int $orderNumber, int $transactionId = 0): array|false
    {
        $merchantData = [];

        if ($transactionId !== 0) {
            $merchantData['reference_no'] = $transactionId;
        } elseif ($orderNumber !== '' && $orderNumber !== 0) {
            $merchantData['order_no'] = $orderNumber;
        }

        if ($merchantData === []) {
            return false;
        }

        $workingKey = (string) Config::get('ccavenue.workingKey', '');
        $accessCode = (string) Config::get('ccavenue.accessCode', '');
        $encRequest = $this->encrypt(json_encode($merchantData), $workingKey);

        $client = new Client;
        $orderStatusParams = [
            'enc_request' => $encRequest,
            'access_code' => $accessCode,
            'command' => 'orderStatusTracker',
            'request_type' => 'JSON',
            'version' => '1.1',
        ];
        $queryString = http_build_query($orderStatusParams);

        try {
            $response = $client->post($this->getAPIEndPoint().$queryString);
            $body = $response->getBody()->getContents();
            $orderData = [];
            parse_str($body, $orderData);

            return $this->getOrderStatus($orderData);
        } catch (BadResponseException|ConnectException|ClientException $e) {
            Log::error('CCAvenue getOrderDetails failed: '.$e->getMessage());

            throw new PaymentGatewayException('Order status check failed: '.$e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Decrypt enc_response from order status API and return decoded order array.
     *
     * @param  array<string, mixed>  $parsedData  Response containing enc_response
     * @return array<string, mixed>|false
     */
    private function getOrderStatus(array $parsedData = []): array|false
    {
        $encResponse = $parsedData['enc_response'] ?? '';
        if ($encResponse === '') {
            return false;
        }

        try {
            $decrypted = $this->decrypt(str_replace(["\n", "\r"], '', (string) $encResponse), $this->workingKey);
        } catch (\Throwable $e) {
            Log::warning('CCAvenue getOrderStatus decrypt failed', ['exception' => $e->getMessage()]);

            return false;
        }

        $order = json_decode($decrypted, true);

        return is_array($order) ? $order : false;
    }
}
