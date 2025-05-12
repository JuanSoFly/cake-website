@extends('layouts.app')

@section('content')
<section class="w-full py-12 md:py-24 lg:py-32">
    <div class="container px-4 md:px-6">
        <div class="flex flex-col items-center justify-center space-y-4 text-center">
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                    Your Shopping Cart
                </h1>
                <p class="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                    Review your items before proceeding to checkout.
                </p>
            </div>
        </div>
        
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
        @endif
        
        @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
        @endif
        
        @if(count($cart['items']) > 0)
        <div class="mt-8 space-y-8">
            <div class="rounded-lg border shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Product</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Price</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Quantity</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Subtotal</th>
                                <th class="px-4 py-3 text-sm font-medium text-muted-foreground"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart['items'] as $index => $item)
                            <tr class="border-b">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-4">
                                        <img
                                            src="{{ asset('images/cakes/' . $item['image']) }}"
                                            alt="{{ $item['name'] }}"
                                            class="aspect-square rounded-md object-cover h-16 w-16"
                                        />
                                        <div>
                                            <h3 class="font-medium">{{ $item['name'] }}</h3>
                                            @if($item['customization'])
                                            <p class="text-sm text-gray-600 mt-1">{{ $item['customization'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm">₱{{ number_format($item['price'], 2) }}</td>
                                <td class="px-4 py-4">
                                    <form action="{{ route('cart.update', $index) }}" method="POST" class="flex items-center">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex items-center">
                                            <button type="button" onclick="decrementQuantity{{ $index }}()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                                    <path d="M5 12h14" />
                                                </svg>
                                                <span class="sr-only">Decrease quantity</span>
                                            </button>
                                            <input
                                                type="number"
                                                id="quantity{{ $index }}"
                                                name="quantity"
                                                min="0"
                                                value="{{ $item['quantity'] }}"
                                                class="w-12 text-center border-y h-8 px-2 py-1"
                                            />
                                            <button type="button" onclick="incrementQuantity{{ $index }}()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                                    <path d="M12 5v14M5 12h14" />
                                                </svg>
                                                <span class="sr-only">Increase quantity</span>
                                            </button>
                                        </div>
                                        <button type="submit" class="ml-2 text-sm text-gray-600 hover:text-pink-600">
                                            Update
                                        </button>
                                    </form>
                                    <script>
                                        function incrementQuantity{{ $index }}() {
                                            const input = document.getElementById('quantity{{ $index }}');
                                            input.value = parseInt(input.value) + 1;
                                        }
                                        
                                        function decrementQuantity{{ $index }}() {
                                            const input = document.getElementById('quantity{{ $index }}');
                                            if (parseInt(input.value) > 0) {
                                                input.value = parseInt(input.value) - 1;
                                            }
                                        }
                                    </script>
                                </td>
                                <td class="px-4 py-4 text-sm">₱{{ number_format($item['subtotal'], 2) }}</td>
                                <td class="px-4 py-4 text-right">
                                    <form action="{{ route('cart.remove', $index) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            Clear Cart
                        </button>
                    </form>
                </div>
                <div class="rounded-lg border shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Order Summary</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>₱{{ number_format($cart['subtotal'], 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tax (8%)</span>
                            <span>₱{{ number_format($cart['tax'], 2) }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between font-semibold">
                                <span>Total</span>
                                <span>₱{{ number_format($cart['total'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('checkout.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full mt-4">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
        @else
        <div class="mt-8 text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-12 w-12 mx-auto text-gray-400">
                <circle cx="8" cy="21" r="1" />
                <circle cx="19" cy="21" r="1" />
                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
            </svg>
            <h2 class="mt-4 text-xl font-semibold">Your cart is empty</h2>
            <p class="mt-2 text-gray-600">Looks like you haven't added any cakes to your cart yet.</p>
            <a href="{{ route('cakes.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 mt-4">
                Browse Cakes
            </a>
        </div>
        @endif
    </div>
</section>
@endsection
