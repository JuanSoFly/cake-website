@extends('layouts.app')

@section('content')
    <section class="w-full py-12 md:py-24 lg:py-25 bg-pink-50">
        <div class="container px-4 md:px-6">
            <div class="grid gap-6 lg:grid-cols-2 lg:gap-12 items-center">
                <div class="flex flex-col justify-center space-y-4">
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold tracking-tighter sm:text-5xl xl:text-6xl/none text-pink-800">
                            Delicious Cakes for Every Occasion
                        </h1>
                        <p class="max-w-[600px] text-gray-600 md:text-xl">
                            Handcrafted with love and the finest ingredients. Our cakes make your special moments unforgettable.
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 min-[400px]:flex-row">
                        <a href="{{ route('order') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                            Order Now
                        </a>
                        <a href="{{ route('menu') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            View Menu
                        </a>
                    </div>
                </div>
                <div class="mx-auto w-full max-w-[500px] lg:max-w-none relative">
                    <img
                        src="{{ asset('images/hero-cake.png') }}"
                        alt="Beautiful tiered cake with floral decorations"
                        class="mx-auto aspect-square rounded-xl object-cover"
                    />
                </div>
            </div>
        </div>
    </section>

    <section id="cakes" class="w-full py-12 md:py-24 lg:py-32">
        <div class="container px-4 md:px-6">
            <div class="flex flex-col items-center justify-center space-y-4 text-center">
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                        Our Signature Cakes & Breads
                    </h2>
                    <p class="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                        Explore our collection of handcrafted cakes made with premium ingredients and love.
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                @foreach($cakes as $cake)
                <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
                    <div class="relative">
                        <img
                            src="{{ asset('images/cakes/' . $cake['image']) }}"
                            alt="{{ $cake['name'] }}"
                            class="object-cover w-full h-48"
                        />
                        <button
                            class="absolute top-2 right-2 rounded-full bg-white/80 hover:bg-white/90 inline-flex items-center justify-center h-8 w-8"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-pink-600">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                            </svg>
                            <span class="sr-only">Add to favorites</span>
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg">{{ $cake['name'] }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $cake['description'] }}</p>
                            </div>
                            <div class="text-pink-600 font-bold">{{ $cake['price'] }}</div>
                        </div>
                        <a href="{{ route('order', ['cake' => $cake['id']]) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full mt-4">
                            Order Now
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="flex justify-center mt-8">
                <a href="{{ route('menu') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                    View All Cakes 
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <section id="about" class="w-full py-12 md:py-24 lg:py-32 bg-pink-50">
        <div class="container px-4 md:px-6">
            <div class="grid gap-6 lg:grid-cols-2 lg:gap-12 items-center">
                <div class="mx-auto w-full max-w-[500px] lg:max-w-none">
                    <img
                        src="{{ asset('images/bakery.jpg') }}"
                        alt="Our bakery interior with bakers working"
                        class="mx-auto rounded-xl object-cover"
                    />
                </div>
                <div class="flex flex-col justify-center space-y-4">
                    <div class="space-y-2">
                        <h2 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                            Our O'Cake Story
                        </h2>
                        <p class="max-w-[600px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                            O'Cakes with Sweets was founded in 2025 with a simple mission: to create delicious, beautiful cakes that
                            bring joy to every celebration.
                        </p>
                    </div>
                    <div class="space-y-4">
                        <p class="text-gray-600">
                            Our team of passionate bakers combines traditional techniques with innovative flavors to create
                            cakes that are as delightful to look at as they are to eat. We use only the finest ingredients,
                            sourced locally whenever possible.
                        </p>
                        <p class="text-gray-600">
                            From intimate birthday celebrations to grand weddings, we take pride in being part of your special
                            moments. Each cake is crafted with attention to detail and customized to your preferences.
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('team') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                            Meet Our Team
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="testimonials" class="w-full py-12 md:py-24 lg:py-32">
        <div class="container px-4 md:px-6">
            <div class="flex flex-col items-center justify-center space-y-4 text-center">
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                        What Our Customers Say
                    </h2>
                    <p class="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                        Don't just take our word for it. Here's what our happy customers have to say.
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                @foreach($testimonials as $testimonial)
                <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
                    <div class="flex flex-col space-y-4">
                        <div class="flex">
                            @for($i = 0; $i < $testimonial['rating']; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 fill-yellow-400 text-yellow-400">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                            @endfor
                        </div>
                        <p class="text-gray-600 italic">"{{ $testimonial['comment'] }}"</p>
                        <div class="flex items-center space-x-2">
                            <div class="rounded-full bg-pink-100 p-1">
                                <span class="text-pink-600 font-bold text-sm">
                                    {{ $testimonial['initials'] }}
                                </span>
                            </div>
                            <span class="font-medium">{{ $testimonial['name'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="contact" class="w-full py-12 md:py-24 lg:py-32 bg-pink-50">
        <div class="container px-4 md:px-6">
            <div class="grid gap-6 lg:grid-cols-2 lg:gap-12 items-center">
                <div class="flex flex-col justify-center space-y-4">
                    <div class="space-y-2">
                        <h2 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                            Get in Touch
                        </h2>
                        <p class="max-w-[600px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                            Have questions or want to place an order? We'd love to hear from you!
                        </p>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-pink-600">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <p>123 Bakery Street, Sweet City, SC 12345</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-pink-600">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                            <p>(555) 123-4567</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-pink-600">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                            <p>info@sweetdelights.com</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xl font-bold">Hours</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div>Monday - Friday</div>
                            <div>8:00 AM - 6:00 PM</div>
                            <div>Saturday</div>
                            <div>9:00 AM - 5:00 PM</div>
                            <div>Sunday</div>
                            <div>10:00 AM - 3:00 PM</div>
                        </div>
                    </div>
                </div>
                <div class="mx-auto w-full max-w-[500px] lg:max-w-none">
                    <form action="{{ route('contact.submit') }}" method="POST" class="grid gap-4 p-6 bg-white rounded-xl shadow-sm">
                        @csrf
                        <div class="grid gap-2">
                            <label for="name" class="text-sm font-medium">
                                Name
                            </label>
                            <input id="name" name="name" placeholder="Your name" class="border rounded-md px-3 py-2" required />
                        </div>
                        <div class="grid gap-2">
                            <label for="email" class="text-sm font-medium">
                                Email
                            </label>
                            <input id="email" name="email" type="email" placeholder="Your email" class="border rounded-md px-3 py-2" required />
                        </div>
                        <div class="grid gap-2">
                            <label for="message" class="text-sm font-medium">
                                Message
                            </label>
                            <textarea id="message" name="message" placeholder="Your message" class="border rounded-md px-3 py-2 min-h-[120px]" required></textarea>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
