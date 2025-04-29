@extends('layouts.app')

@section('content')
<section class="w-full py-12 md:py-24 lg:py-32">
    <div class="container px-4 md:px-6">
        <div class="grid gap-6 lg:grid-cols-2 lg:gap-12 items-start">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-xl">
                    <img
                        src="{{ asset('images/cakes/cakes.jpg' . $cake->image) }}"
                        alt="{{ $cake->name }}"
                        class="aspect-square object-cover w-full"
                    />
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="{{ asset('images/cakes/' . $cake->image) }}"
                            alt="{{ $cake->name }} - Thumbnail 1"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="{{ asset('images/cakes/' . $cake->image) }}"
                            alt="{{ $cake->name }} - Thumbnail 2"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="{{ asset('images/cakes/' . $cake->image) }}"
                            alt="{{ $cake->name }} - Thumbnail 3"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="{{ asset('images/cakes/' . $cake->image) }}"
                            alt="{{ $cake->name }} - Thumbnail 4"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                        {{ $cake->name }}
                    </h1>
                    <p class="text-2xl font-bold text-pink-600">
                        {{ $cake->formatted_price }}
                    </p>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="flex items-center">
                            @for($i = 0; $i < 5; $i++)
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 fill-yellow-400 text-yellow-400">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                            @endfor
                        </div>
                        <span class="ml-2 text-sm text-gray-600">(25 reviews)</span>
                    </div>
                    <p class="text-gray-600">
                        {{ $cake->description }}
                    </p>
                    <div class="space-y-2">
                        <h3 class="font-semibold">Key Features:</h3>
                        <ul class="list-disc list-inside space-y-1 text-gray-600">
                            <li>Made with premium ingredients</li>
                            <li>Freshly baked to order</li>
                            <li>Available in various sizes</li>
                            <li>Customizable decorations</li>
                            <li>Perfect for special occasions</li>
                        </ul>
                    </div>
                </div>
                <form action="{{ route('cart.add', $cake) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="space-y-2">
                        <label for="quantity" class="text-sm font-medium">
                            Quantity
                        </label>
                        <div class="flex items-center">
                            <button type="button" onclick="decrementQuantity()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M5 12h14" />
                                </svg>
                                <span class="sr-only">Decrease quantity</span>
                            </button>
                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                min="1"
                                value="1"
                                class="flex-1 text-center border-y h-10 px-3 py-2"
                            />
                            <button type="button" onclick="incrementQuantity()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M12 5v14M5 12h14" />
                                </svg>
                                <span class="sr-only">Increase quantity</span>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label for="customization" class="text-sm font-medium">
                            Special Instructions (Optional)
                        </label>
                        <textarea
                            id="customization"
                            name="customization"
                            placeholder="E.g., Happy Birthday message, specific decorations, etc."
                            class="w-full min-h-[100px] rounded-md border border-input px-3 py-2"
                        ></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full">
                        Add to Cart
                    </button>
                </form>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10" />
                    </svg>
                    <span>Secure checkout</span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function incrementQuantity() {
        const input = document.getElementById('quantity');
        input.value = parseInt(input.value) + 1;
    }
    
    function decrementQuantity() {
        const input = document.getElementById('quantity');
        if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
        }
    }
</script>
@endsection
