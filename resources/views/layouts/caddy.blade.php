<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Caddy Metrics Dashboard</title>

    <!-- Tailwind CDN for standalone use -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @livewireStyles
</head>

<body class="h-full font-sans antialiased text-gray-900">
    <div class="min-h-full flex flex-col">
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <a href="/" class="font-bold text-xl tracking-tight text-indigo-600">
                                Caddy Metrics
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-10 flex-grow">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
                    <div>
                        &copy; {{ date('Y') }} Caddy Metrics Dashboard. All rights reserved.
                    </div>
                    <div class="mt-2 md:mt-0 flex items-center">
                        Made with <span class="text-red-500 mx-1">&hearts;</span> by
                        <a href="https://iperamuna.online" target="_blank"
                            class="ml-1 text-indigo-600 hover:text-indigo-500 font-medium transition-colors">
                            Indunil Peramuna
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>

</html>