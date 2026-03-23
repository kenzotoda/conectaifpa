<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | ConectaIFPA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/js/app.js'])
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .font-dm-sans { font-family: 'DM Sans', sans-serif; }
        .auth-panel {
            background-color: #0f766e;
            background-image:
                radial-gradient(at 40% 20%, rgba(255,255,255,0.12) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(255,255,255,0.08) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(255,255,255,0.06) 0px, transparent 50%),
                linear-gradient(135deg, #0f766e 0%, #0d9488 30%, #0f766e 70%, #134e4a 100%);
        }
        .float-slow { animation: float 8s ease-in-out infinite; }
        .float-slower { animation: float 12s ease-in-out infinite; }
        .float-reverse { animation: float 10s ease-in-out infinite reverse; }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(20px, -15px) scale(1.02); }
            66% { transform: translate(-10px, 10px) scale(0.98); }
        }
        .blob { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
        .glass-card { background: rgba(255,255,255,0.9); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        @media (max-width: 1023px) {
            .glass-card { background: rgba(255,255,255,0.98); }
        }
        /* Evita zoom em inputs no iOS */
        input, select, textarea { font-size: 16px !important; }
        /* Scroll suave no mobile */
        body { -webkit-overflow-scrolling: touch; }
        @supports (padding: max(0px)) {
            .auth-safe-bottom { padding-bottom: max(1.5rem, env(safe-area-inset-bottom)); }
            .auth-safe-top { padding-top: max(0.5rem, env(safe-area-inset-top)); }
        }
    </style>
</head>
<body class="font-dm-sans antialiased min-h-screen overflow-x-hidden overflow-y-auto bg-slate-100">
    <div class="min-h-screen flex flex-col lg:flex-row">
        @yield('content')
    </div>
</body>
</html>
