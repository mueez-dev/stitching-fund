<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found | Zaryq</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes float {
            0%,100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-950 via-gray-900 to-black text-white min-h-screen flex items-center justify-center overflow-hidden">

<div class="text-center max-w-lg px-6">

    <h1 class="text-8xl font-extrabold bg-gradient-to-r from-indigo-500 to-purple-500 bg-clip-text text-transparent floating">
        404
    </h1>

    <h2 class="mt-6 text-2xl font-semibold text-gray-200">
        Page Not Found
    </h2>

    <p class="mt-4 text-gray-400 leading-relaxed">
        The page you're looking for doesn’t exist or may have been moved.
    </p>

    <div class="mt-10 flex justify-center gap-4 relative">

        <!-- Normal Button -->
        <a href="{{ url('/admin') }}"
           class="px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 transition duration-200 shadow-lg shadow-indigo-500/20">
            Back To Home
        </a>

        <!-- Smart Moving Button -->
        <button id="smartBtn"
        class="fixed px-6 py-3 rounded-lg border border-gray-700 bg-gray-900 hover:bg-gray-800 transition-colors duration-150">
            Catch ME 😜
        </button>

    </div>

    <p class="mt-10 text-sm text-gray-600">
        Error Code: 404 • Zaryq SaaS Platform
    </p>

</div>

<script>
    const btn = document.getElementById('smartBtn');

    function moveButton() {
        const padding = 30;

        const maxX = window.innerWidth - btn.offsetWidth - padding;
        const maxY = window.innerHeight - btn.offsetHeight - padding;

        const randomX = Math.random() * maxX;
        const randomY = Math.random() * maxY;

        btn.style.left = randomX + "px";
        btn.style.top = randomY + "px";
    }

    document.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();

        const btnCenterX = rect.left + rect.width / 2;
        const btnCenterY = rect.top + rect.height / 2;

        const distance = Math.sqrt(
            Math.pow(e.clientX - btnCenterX, 2) +
            Math.pow(e.clientY - btnCenterY, 2)
        );

        // Move only if cursor gets close (professional trolling 😄)
        if (distance < 150) {
            moveButton();
        }
    });

    // Initial position
    window.onload = () => {
        moveButton();
    };

    // If somehow clicked
    btn.addEventListener('click', () => {
        history.back();
    });
</script>

</body>
</html>
