<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | ConectaIFPA</title>
    @include('partials.favicon')

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
            background-color: #eef8f3;
            background-image:
                radial-gradient(at 88% 8%, rgba(64, 218, 152, 0.40) 0px, transparent 46%),
                radial-gradient(at 8% 92%, rgba(18, 167, 106, 0.26) 0px, transparent 44%),
                linear-gradient(180deg, #f7fdf9 0%, #e5f6ee 100%);
            color: #0f172a;
        }
        .auth-panel .text-white,
        .auth-panel [class*="text-white"] {
            color: #0f172a;
        }
        .auth-panel a[class*="text-white"]:hover {
            color: #0b6b45;
        }
        .auth-panel .blob.bg-white\/10 { background-color: rgba(64, 218, 152, 0.34); }
        .auth-panel .blob.bg-white\/15 { background-color: rgba(18, 167, 106, 0.22); }
        .auth-panel .blob.bg-white\/5 { background-color: rgba(11, 107, 69, 0.12); }
        .float-slow { animation: float 8s ease-in-out infinite; }
        .float-slower { animation: float 12s ease-in-out infinite; }
        .float-reverse { animation: float 10s ease-in-out infinite reverse; }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(20px, -15px) scale(1.02); }
            66% { transform: translate(-10px, 10px) scale(0.98); }
        }
        .blob { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
        .glass-card {
            background: #ffffff;
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            box-shadow: 0 1px 2px rgba(11, 107, 69, 0.05), 0 10px 24px rgba(11, 107, 69, 0.06);
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
<body class="layout-auth dash-page font-dm-sans antialiased min-h-screen overflow-x-hidden overflow-y-auto flex flex-col">
    <header class="auth-top-bar site-header sticky top-0 z-50 shrink-0 border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-3 sm:gap-4 min-h-[3.5rem] min-w-0">
            <a href="{{ url('/') }}" class="flex items-center gap-2 sm:gap-2.5 no-underline group min-w-0 flex-shrink">
                @include('partials.brand-mark', ['class' => 'w-8 h-8 sm:w-9 sm:h-9'])
                <span class="site-brand font-outfit font-bold text-sm sm:text-base truncate">ConectaIFPA</span>
            </a>
            @include('partials.nav-a11y-toolbar')
        </div>
    </header>

    {{-- Sem altura mínima fixa: evita cortar conteúdo alto (ex.: registro); a página rola naturalmente. --}}
    <main class="flex-1 flex flex-col min-w-0 lg:flex-row lg:items-start">
        @yield('content')
    </main>
    @include('partials.vlibras')
</body>
</html>
