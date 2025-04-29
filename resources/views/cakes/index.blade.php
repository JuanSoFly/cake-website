@extends('layouts.app')

@section('content')
<section class="w-full py-12 md:py-24 lg:py-32">
    <div class="container px-4 md:px-6">
        <div class="flex flex-col items-center justify-center space-y-4 text-center">
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                    Our Delicious Cakes
                </h1>
                <p class="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                    Browse our selection of handcrafted cakes made with premium ingredients and love.
                </p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            @foreach($cakes as $cake)
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
                <div class="relative">
                    <img
                        src="{{ asset('images/cakes/cake.jpg' . $cake->image) }}"
                        alt="{{ $cake->name }}"
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
                            <h3 class="font-semibold text-lg">{{ $cake->name }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $cake->description }}</p>
                        </div>
                        <div class="text-pink-600 font-bold">{{ $cake->formatted_price }}</div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('cakes.show', $cake) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 flex-1">
                            View Details
                        </a>
                        <form action="{{ route('cart.add', $cake) }}" method="POST" class="flex-1">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full">
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
