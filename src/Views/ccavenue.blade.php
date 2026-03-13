{{--
    CCAvenue redirect form.
    Auto-submits to CCAvenue with encrypted request and access code.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to CCAvenue</title>
</head>
<body>
    <form method="post" name="redirect" action="{{ $endPoint }}">
        @csrf
        <input type="hidden" name="encRequest" value="{{ $encRequest }}">
        <input type="hidden" name="access_code" value="{{ $accessCode }}">
    </form>
    <script>
        document.redirect.submit();
    </script>
    <p>Redirecting to payment gateway…</p>
</body>
</html>
