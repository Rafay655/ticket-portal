<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{asset('assets/img/logos/Flexi_FF-03.png')}}">
    <title>OTP Verification - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="{{ asset('assets/css/custom.css') }}?v={{ time() }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('build/assets/app.css') }}">

    <style>
        .otp-input-box {
            width: 100px;
            height: 100px;
            font-size: 2rem;
            text-align: center;
            background-color: transparent;
            border: 1px solid #fff;
            border-radius: 5px;
            margin: 5px;
            outline: none
        }

        .otp-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .timer {
            font-size: 1.2rem;
            color: red;
        }

        .resend-btn,
        .verify-btn {
            display: none;
        }

        .main-wrapper {
            min-height: 100vh;

            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            position: relative;
        }

        .custom-logo-size {
            width: 150px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <div class="wrapper min-vh-100 flex-1">
            <img src="{{ asset('assets/img/logos/Flexi_FF-02.png') }}" alt="Flexi Logo"
                class="position-absolute custom-logo-size">

            <!-- OTP Message -->
            <p class="text-center mb-5" style="font-size: 1.2rem;">Please enter the 6-digit OTP sent to your email. It
                is valid for 2 minutes.</p>

            <!-- OTP Input Boxes -->
            <!-- OTP Input Boxes -->
            <div class="otp-container" style="z-index:1000">
                <input class="otp-input-box" oninput="moveToNext(this)" autofocus>
                <input maxlength="1" class="otp-input-box" oninput="moveToNext(this)">
                <input maxlength="1" class="otp-input-box" oninput="moveToNext(this)">
                <input maxlength="1" class="otp-input-box" oninput="moveToNext(this)">
                <input maxlength="1" class="otp-input-box" oninput="moveToNext(this)">
                <input maxlength="1" class="otp-input-box" oninput="moveToNext(this)">
            </div>


            <!-- Timer -->
            <div class="timer mt-3 text-white text-center">
                <span id="timer" class="text-white">2:00</span>
            </div>

            <!-- Resend Button -->
            <div class="d-flex gap-6 justify-center" style="z-index:100">

                <button id="resendBtn" class="text-primary bg-white btn mt-3 resend-btn" onclick="resendOtp()">Resend
                    OTP</button>

                <!-- Verify Button -->
                <button id="verifyBtn" class="text-primary bg-white btn mt-3 verify-btn" onclick="verifyOtp()">Verify
                    OTP</button>
            </div>
            <ul class="bg-bubbles">
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('build/assets/app.js') }}"></script>

    <script>
        let timerDuration = 120; // 2 minutes in seconds
        let timerInterval;
        const urlParams = new URLSearchParams(window.location.search);
        // Retrieve specific parameters
        const email = urlParams.get('email'); // Get the 'email' parameter
        const token = urlParams.get('token');
        // console.log(email)
        console.log(JSON.stringify({ email, token }))
        function startTimer() {
            let timeLeft = timerDuration;
            document.getElementById('timer').textContent = '2:00'; // Reset display to 2:00
            timerInterval = setInterval(() => {
                let minutes = String(Math.floor(timeLeft / 60)).padStart(2, '0');
                let seconds = String(timeLeft % 60).padStart(2, '0');
                document.getElementById('timer').textContent = `${minutes}:${seconds}`;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    document.getElementById('resendBtn').style.display = 'inline-block'; // Show resend button
                }
                timeLeft--;
            }, 1000);
        }

        async function resendOtp() {

            const resendButton = document.getElementById('resendBtn');
            resendButton.textContent = 'Sending...';
            resendButton.disabled = true;


            try {
                const response = await fetch("{{ route('password.resend') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ email, token }), // Replace with dynamic email if needed
                });
                console.log(response);
                if (response.ok) {
                    alert('OTP has been resent to your email.');
                    // Reset timer and button
                    clearInterval(timerInterval);
                    document.getElementById('resendBtn').style.display = 'none';
                    startTimer();
                } else {
                    alert('Failed to resend OTP. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while resending the OTP.');
            } finally {
                // Reset button state
                resendButton.textContent = 'Resend OTP';
                resendButton.disabled = false;
            }
        }

        async function verifyOtp() {
            const verifyButton = document.getElementById('verifyBtn');
            verifyButton.textContent = 'Verifying...';
            verifyButton.disabled = true;

            try {
                const otp = Array.from(document.querySelectorAll('.otp-input-box')).map(input => input.value).join('');
                console.log(otp.length);

                if (otp.length === 6) {
                    const response = await fetch("{{ route('password.verify') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ email, token, otp }),
                    });

                    if (response.ok) {
                        const data = await response.json();
                        alert(data.message); // Show success message
                        window.location.href = data.redirect_url; // Redirect to the password reset page
                    } else {
                        const errorData = await response.json();
                        alert(errorData.error); // Show error message
                    }
                } else {
                    alert('Please enter a valid 6-digit OTP.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while verifying the OTP.');
            } finally {
                // Reset button state
                verifyButton.textContent = 'Verify OTP';
                verifyButton.disabled = false;
            }
        }

        // Start the timer when page loads
        startTimer();

        // Move to the next input box after each digit is entered
        function moveToNext(current) {
            if (current.value.length === 1) {
                let next = current.nextElementSibling;
                if (next && next.classList.contains('otp-input-box')) {
                    next.focus();
                }
            }
            // Show verify button once all OTP boxes are filled
            const otpFilled = Array.from(document.querySelectorAll('.otp-input-box')).every(input => input.value.length === 1);
            document.getElementById('verifyBtn').style.display = otpFilled ? 'inline-block' : 'none';
        }
        
        // Select the clicked OTP box
        document.querySelectorAll('.otp-input-box').forEach(box => {
         
            box.addEventListener('click', function () {
              
                this.focus(); // Focus on the clicked box
                this.select(); // Highlight its content
            });
        });


    </script>

</body>

</html>