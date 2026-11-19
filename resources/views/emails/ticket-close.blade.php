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
        .ticket-details {
            background-color: #e5f2fc;
            padding: 20px;
            margin: 20px 0;
            border-left: 5px solid #0a3aa9;
            border-radius: 8px;
            z-index: 10;
        }
        .ticket-details p {
            margin: 5px 0;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }
        .cta-button {
            text-align: center;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .cta-button a {
            background-color: #0a3aa9;
            color: white;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        .cta-button a:hover {
            background-color: #0056b3;
        }
        .footer {
            background-color: #f4f4f4;
          
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #ddd;
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
                <p style="font-size:40px;text-align:center;font-weight:700;margin:10px 0">🎫 Ticket Closed</p>
            </td>
        </tr>
        <tr>
            <td  class="content" style="padding:40px">
                <p>Dear {{$notifiable->name }},</p>
                <p>Your ticket has been successfully closed. Below are the details:</p>
                <table class="ticket-details" width="100%" cellpadding="10">
                    <tr>
                        <td><strong>Ticket ID/Reference:</strong> {{$ticket->id}}</td>
                    </tr>
                    <tr>
                        <td><strong>Ticket Type:</strong> {{ $ticket->tickettype->name}}</td>
                    </tr>
                    <tr>
                        <td><strong>Sub Type:</strong> {{$ticket->ticketsubtype->name}}</td>
                    </tr>
                    <tr>
                        <td><strong>Priority:</strong> {{$ticket->ticketsubtype->priority}}</td>
                    </tr>
                    @if(!empty($comment['comment']))
                        <tr>
                            <td><strong>Closed Commen:</strong> {{ $comment['comment'] }}</td>
                        </tr>
                    @endif

                    @if(!empty($comment['comment_attachment']))
                        <tr>
                            <td><strong>Attachment:</strong> A file has been sent.</td>
                        </tr>
                    @endif

                 
                </table>
                <div class="divider"></div>
                <p>We value your feedback! Please provide your [feedback and rating] by clicking below</p>
            </td>
        </tr>
        <tr>
            <td class="cta-button">
                <a href="{{route('ticket.view', ['id' => $ticket->id])}}">🔗 View Ticket</a>
            </td>
        </tr>
        <tr>
            <td class="footer" style="padding:10px 0 0">
            <p>Best regards,<br><strong>Flexi Desk</strong> <br>Powered by <a href="https://hrpsp.net/">Hrpsp</a>   </p>

            </td>
        </tr>
    </table>
</body>
</html>
