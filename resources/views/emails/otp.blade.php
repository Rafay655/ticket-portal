<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        table {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
           
        }
        .header {
            background: #0a3aa9;
            text-align: center;
            color: white;
        }
        .content {
            padding: 70px;
        }
        .content p {
            font-size: 16px;
            line-height: 1.7;
            color: #555;
        }
        .otp-box {
            background-color: #e5f2fc;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #0a3aa9;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #0a3aa9;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }
        .footer {
            background-color: #f4f4f4;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #ddd;
            padding: 10px 0;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <table align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td class="header" style="padding:10px 0">
                <p style="font-size:40px;text-align:center;font-weight:700;margin:10px 0"> OTP Verification</p>
            </td>
        </tr>
        <tr>
            <td class="content" style="padding:40px">
                <p>Dear User,</p>
                <p>Your OTP for password reset is:</p>
                <div class="otp-box">
                    {{ $otp }}
                </div>
                <p>This OTP is valid for <strong>2 minutes</strong>.</p>
                <div class="divider"></div>
                <p>If you did not request a password reset, please ignore this email or contact support immediately.</p>
            </td>
        </tr>
        <tr>
            <td class="footer">
            <p>Best regards,<br><strong>Flexi Desk</strong> <br>Powered by <a href="https://hrpsp.net/">Hrpsp</a>   </p>

            </td>
        </tr>
    </table>
</body>
</html>
