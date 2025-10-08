<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        @vite(['resources/js/app.js'])

        <!-- Procura a pasta css direto na pasta public -->
        <link rel="stylesheet" href="/css/styles.css">

        <!-- Procura a pasta js direto na pasta public -->
        <script src="/js/scripts.js"></script>
       
    </head>
    <body>
        <header>
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid" id="navbar">
                    <!-- Logo + título -->
                    <a href="/" class="navbar-brand d-flex align-items-center">
                        <img src="/img/ifpa.png" alt="Logo IFPA">
                    </a>
                    <h4>TADS</h4>

                    <!-- Botão hamburguer -->
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarrr"
                        aria-controls="navbarrr" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Menu -->
                    <div class="collapse navbar-collapse" id="navbarrr">
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item">
                                <a href="/" class="nav-link">Eventos</a>
                            </li>
                            <li class="nav-item">
                                <a href="/events/create" class="nav-link">Criar Eventos</a>
                            </li>
                            @auth
                                <li class="nav-item">
                                    <a href="/dashboard" class="nav-link">Meus eventos</a>
                                </li>
                                <li class="nav-item">
                                    <form action="/logout" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-link nav-link" id="sair">Sair</button>
                                    </form>
                                </li>
                            @endauth
                            @guest
                                <li class="nav-item">
                                    <a href="/login" class="nav-link">Entrar</a>
                                </li>
                                <li class="nav-item">
                                    <a href="/register" class="nav-link">Cadastrar</a>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <main>
            <div class="container-fluid">
                <div class="row">
                    @if (session('msg'))
                        <p class="msg">{{ session('msg') }}</p>
                    @endif
                    @yield('content')
                </div>
            </div>
        </main>
        <footer>
            <p>Kenzo e Marcelo &copy; 2025</p>
        </footer>
        <!-- ionicons -->
        <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    </body>
</html>
