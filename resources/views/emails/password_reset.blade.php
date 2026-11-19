<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
</head>
<body>
    <p>Hello,</p>
    <p>Your OTP for resetting your password is:</p>
    <h2>{{ $otp }}</h2>
    <p>This OTP will expire in 10 minutes.</p>
    <p>If you did not request this, please ignore this email.</p>
    <br>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>
