<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - ZARYQ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center">
                    <img src="{{ asset('images/logo.png') }}" alt="ZARYQ" class="h-10 w-auto rounded-lg">
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Verify Your Email
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    @isset($email)
                        Enter the 6-digit code sent to {{ $email }}
                    @else
                        Enter the 6-digit verification code
                    @endisset
                </p>
            </div>
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif
            
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            
            <form id="verificationForm" class="mt-8 space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-4">Enter 6-digit verification code</label>
                    <div class="flex space-x-2 justify-center" id="codeInputs">
                        <input type="text" maxlength="1" pattern="[0-9]" class="w-12 h-12 text-center text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" data-index="0" autocomplete="off">
                        <input type="text" maxlength="1" pattern="[0-9]" class="w-12 h-12 text-center text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" data-index="1" autocomplete="off">
                        <input type="text" maxlength="1" pattern="[0-9]" class="w-12 h-12 text-center text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" data-index="2" autocomplete="off">
                        <input type="text" maxlength="1" pattern="[0-9]" class="w-12 h-12 text-center text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" data-index="3" autocomplete="off">
                        <input type="text" maxlength="1" pattern="[0-9]" class="w-12 h-12 text-center text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" data-index="4" autocomplete="off">
                        <input type="text" maxlength="1" pattern="[0-9]" class="w-12 h-12 text-center text-lg border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" data-index="5" autocomplete="off">
                    </div>
                    <input type="hidden" name="verification_code" id="verification_code" required>
                    <p id="codeError" class="mt-2 text-sm text-red-600 hidden">Please enter a valid 6-digit code</p>
                    <p id="codeHelp" class="mt-2 text-sm text-gray-500">Enter the 6-digit code from your email</p>
                </div>

                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        id="verifyBtn"
                    >
                        <span id="btnText">Verify Email</span>
                        <span id="btnLoading" class="hidden">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verifying...
                        </span>
                    </button>
                </div>
                
                <div class="text-center">
                    <button type="button" id="resendBtn" class="text-indigo-600 hover:text-indigo-500 text-sm">
                        Didn't receive the code? Resend
                    </button>
                </div>
            </form>
            
            <div class="text-center">
                <a href="{{ route('filament.admin.auth.login') }}" class="text-gray-600 hover:text-gray-500 text-sm">
                    Back to Login
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Handle 6-box verification code input
        const codeInputs = document.querySelectorAll('#codeInputs input');
        const hiddenInput = document.getElementById('verification_code');
        const codeError = document.getElementById('codeError');
        const codeHelp = document.getElementById('codeHelp');
        const verifyBtn = document.getElementById('verifyBtn');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');

        // Auto-focus next input when digit is entered
        codeInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                const value = e.target.value;
                
                // Only allow numbers
                if (!/^[0-9]$/.test(value)) {
                    e.target.value = '';
                    return;
                }
                
                // Move to next input
                if (value && index < codeInputs.length - 1) {
                    codeInputs[index + 1].focus();
                }
                
                // Update hidden input
                updateHiddenInput();
                
                // Clear error when user starts typing
                if (value) {
                    codeError.classList.add('hidden');
                    codeHelp.classList.remove('hidden');
                    codeInputs.forEach(inp => inp.classList.remove('border-red-500'));
                }
            });
            
            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    codeInputs[index - 1].focus();
                }
            });
            
            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                
                if (pastedData.length === 6) {
                    // Fill all inputs
                    pastedData.split('').forEach((digit, i) => {
                        if (i < codeInputs.length) {
                            codeInputs[i].value = digit;
                        }
                    });
                    updateHiddenInput();
                    codeInputs[5].focus();
                }
            });
        });
        
        function updateHiddenInput() {
            const code = Array.from(codeInputs).map(input => input.value).join('');
            hiddenInput.value = code;
            
            // Enable/disable verify button
            if (code.length === 6) {
                verifyBtn.disabled = false;
            } else {
                verifyBtn.disabled = true;
            }
        }
        
        // Show error for invalid code
        function showCodeError(message) {
            codeError.textContent = message;
            codeError.classList.remove('hidden');
            codeHelp.classList.add('hidden');
            codeInputs.forEach(input => input.classList.add('border-red-500'));
            codeInputs[0].focus();
        }
        
        // Form submission
        document.getElementById("verificationForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const code = hiddenInput.value;
            
            // Validate code format
            if (!/^[0-9]{6}$/.test(code)) {
                showCodeError('Please enter a valid 6-digit code');
                return;
            }
            
            // Show loading state
            verifyBtn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            
            try {
                const formData = new FormData(this);
                
                const response = await fetch("{{ route('email.verify.submit') }}", {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": formData.get("_token"),
                        "Accept": "text/html"
                    }
                });
                
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    const text = await response.text();
                    document.body.innerHTML = text;
                }
            } catch (error) {
                console.error('Verification error:', error);
                showCodeError('Network error. Please try again.');
            } finally {
                // Reset button state
                verifyBtn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
            }
        });
        
        // Resend code
        document.getElementById("resendBtn").addEventListener("click", async function() {
            this.disabled = true;
            this.textContent = 'Sending...';
            
            try {
                const response = await fetch("{{ route('email.verify.resend') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                        "Accept": "text/html"
                    }
                });
                
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    const text = await response.text();
                    document.body.innerHTML = text;
                }
            } catch (error) {
                console.error('Resend error:', error);
                alert('Error resending verification code');
            } finally {
                this.disabled = false;
                this.textContent = "Didn't receive the code? Resend";
            }
        });
        
        // Initial state
        updateHiddenInput();
    </script>
</body>
</html>
