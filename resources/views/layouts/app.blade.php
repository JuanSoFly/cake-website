<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sweet Delights') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased">
    <div class="flex flex-col min-h-screen">
        <header class="sticky top-0 z-10 bg-white border-b">
            <div class="container flex items-center justify-between h-16 px-4 md:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-pink-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                        <path d="M8.37 12.37a2.5 2.5 0 0 0-3.326.602c-.47.692-.348 1.635.252 2.236l1.544 1.544c.603.603 1.55.726 2.24.254a2.5 2.5 0 0 0 .601-3.328"></path>
                        <path d="m14.5 12.5 2.25 2.25"></path>
                        <path d="m11.5 9.5 2.25 2.25"></path>
                        <path d="M8.5 6.5 13 11"></path>
                        <path d="M20 14c0 4.418-4.477 8-10 8-3.41 0-6.42-1.33-8-3.5"></path>
                        <path d="M5.5 6.5 8 9"></path>
                        <path d="M3 3v4"></path>
                        <path d="M7 3H3"></path>
                        <path d="M14 10V4a2 2 0 0 0-2-2H8"></path>
                        <path d="M4 15H2"></path>
                    </svg>
                    <span>Sweet Delights</span>
                </a>
                <nav class="hidden md:flex items-center gap-6">
                    <a href="{{ route('home') }}" class="text-sm font-medium hover:underline underline-offset-4">
                        Home
                    </a>
                    <a href="#cakes" class="text-sm font-medium hover:underline underline-offset-4">
                        Our Cakes
                    </a>
                    <a href="#about" class="text-sm font-medium hover:underline underline-offset-4">
                        About Us
                    </a>
                    <a href="#testimonials" class="text-sm font-medium hover:underline underline-offset-4">
                        Testimonials
                    </a>
                    <a href="#contact" class="text-sm font-medium hover:underline underline-offset-4">
                        Contact
                    </a>
                </nav>
                <a href="{{ route('order') }}" class="hidden md:inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                    Order Now
                </a>
                <button x-data @click="$dispatch('toggle-mobile-menu')" class="md:hidden inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 w-10">
                    <span class="sr-only">Toggle menu</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <line x1="4" x2="20" y1="12" y2="12" />
                        <line x1="4" x2="20" y1="6" y2="6" />
                        <line x1="4" x2="20" y1="18" y2="18" />
                    </svg>
                </button>
            </div>
        </header>
        
        <main class="flex-1">
            @yield('content')
        </main>
        
        <footer class="border-t bg-white">
            <div class="container flex flex-col gap-4 px-4 py-6 md:flex-row md:items-center md:gap-6 md:px-6 md:py-8">
                <div class="flex items-center gap-2 text-xl font-bold text-pink-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                        <path d="M8.37 12.37a2.5 2.5 0 0 0-3.326.602c-.47.692-.348 1.635.252 2.236l1.544 1.544c.603.603 1.55.726 2.24.254a2.5 2.5 0 0 0 .601-3.328"></path>
                        <path d="m14.5 12.5 2.25 2.25"></path>
                        <path d="m11.5 9.5 2.25 2.25"></path>
                        <path d="M8.5 6.5 13 11"></path>
                        <path d="M20 14c0 4.418-4.477 8-10 8-3.41 0-6.42-1.33-8-3.5"></path>
                        <path d="M5.5 6.5 8 9"></path>
                        <path d="M3 3v4"></path>
                        <path d="M7 3H3"></path>
                        <path d="M14 10V4a2 2 0 0 0-2-2H8"></path>
                        <path d="M4 15H2"></path>
                    </svg>
                    <span>Sweet Delights</span>
                </div>
                <nav class="flex gap-4 md:gap-6 md:ml-auto">
                    <a href="#" class="text-sm font-medium hover:underline underline-offset-4">
                        Privacy Policy
                    </a>
                    <a href="#" class="text-sm font-medium hover:underline underline-offset-4">
                        Terms of Service
                    </a>
                    <a href="#" class="text-sm font-medium hover:underline underline-offset-4">
                        Careers
                    </a>
                </nav>
                <div class="flex items-center gap-4 md:ml-auto md:gap-2">
                    <a href="#" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 w-10" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                        </svg>
                    </a>
                    <a href="#" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 w-10" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                        </svg>
                    </a>
                    <a href="#" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 w-10" aria-label="Twitter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z" />
                        </svg>
                    </a>
                </div>
            </div>
            <div class="border-t py-4 text-center text-sm text-gray-500">
                © {{ date('Y') }} Sweet Delights. All rights reserved.
            </div>
        </footer>
    </div>
</body>
</html>
