@extends('layouts.app')

@section('content')
<section class="w-full py-12 md:py-24 lg:py-32">
    <div class="container px-4 md:px-6">
        <div class="flex flex-col items-center justify-center space-y-4 text-center">
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                    Checkout
                </h1>
                <p class="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                    Complete your order by providing your delivery and payment details.
                </p>
            </div>
        </div>
        
        @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
        @endif
        
        <div class="mt-8 grid gap-8 md:grid-cols-2">
            <div>
                <div class="rounded-lg border shadow-sm p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-4">
                        @foreach($cart['items'] as $item)
                        <div class="flex gap-4">
                            <img
                                src="{{ asset('images/cakes/' . $item['image']) }}"
                                alt="{{ $item['name'] }}"
                                class="aspect-square rounded-md object-cover h-16 w-16"
                            />
                            <div class="flex-1">
                                <h3 class="font-medium">{{ $item['name'] }}</h3>
                                <div class="flex justify-between mt-1">
                                    <span class="text-sm text-gray-600">Qty: {{ $item['quantity'] }}</span>
                                    <span class="text-sm font-medium">${{ number_format($item['subtotal'], 2) }}</span>
                                </div>
                                @if($item['customization'])
                                <p class="text-sm text-gray-600 mt-1">{{ $item['customization'] }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="border-t mt-4 pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>${{ number_format($cart['subtotal'], 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tax (8%)</span>
                            <span>${{ number_format($cart['tax'], 2) }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between font-semibold">
                                <span>Total</span>
                                <span>${{ number_format($cart['total'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('cart.index') }}" class="text-sm text-pink-600 hover:text-pink-800">
                        &larr; Return to cart
                    </a>
                </div>
            </div>
            <div>
                <form action="{{ route('checkout.process') }}" method="POST" class="rounded-lg border shadow-sm p-6 space-y-4">
                    @csrf
                    <h2 class="text-xl font-semibold mb-4">Delivery Information</h2>
                    
                    <div class="grid gap-2">
                        <label for="customer_name" class="text-sm font-medium">
                            Full Name
                        </label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            value="{{ old('customer_name') }}"
                            class="w-full rounded-md border border-input px-3 py-2 @error('customer_name') border-red-500 @enderror"
                            required
                        />
                        @error('customer_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="grid gap-2">
                        <label for="customer_email" class="text-sm font-medium">
                            Email Address
                        </label>
                        <input
                            type="email"
                            id="customer_email"
                            name="customer_email"
                            value="{{ old('customer_email') }}"
                            class="w-full rounded-md border border-input px-3 py-2 @error('customer_email') border-red-500 @enderror"
                            required
                        />
                        @error('customer_email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="grid gap-2">
                        <label for="customer_phone" class="text-sm font-medium">
                            Phone Number
                        </label>
                        <input
                            type="tel"
                            id="customer_phone"
                            name="customer_phone"
                            value="{{ old('customer_phone') }}"
                            class="w-full rounded-md border border-input px-3 py-2 @error('customer_phone') border-red-500 @enderror"
                        />
                        @error('customer_phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="grid gap-2">
                        <label for="delivery_address" class="text-sm font-medium">
                            Delivery Address
                        </label>
                        <textarea
                            id="delivery_address"
                            name="delivery_address"
                            class="w-full min-h-[80px] rounded-md border border-input px-3 py-2 @error('delivery_address') border-red-500 @enderror"
                            required
                        >{{ old('delivery_address') }}</textarea>
                        @error('delivery_address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <label for="delivery_date" class="text-sm font-medium">
                                Delivery Date
                            </label>
                            <input
                                type="date"
                                id="delivery_date"
                                name="delivery_date"
                                value="{{ old('delivery_date', date('Y-m-d', strtotime('+2 days'))) }}"
                                min="{{ date('Y-m-d') }}"
                                class="w-full rounded-md border border-input px-3 py-2 @error('delivery_date') border-red-500 @enderror"
                                required
                            />
                            @error('delivery_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="grid gap-2">
                            <label for="delivery_time" class="text-sm font-medium">
                                Delivery Time
                            </label>
                            <select
                                id="delivery_time"
                                name="delivery_time"
                                class="w-full rounded-md border border-input px-3 py-2 @error('delivery_time') border-red-500 @enderror"
                                required
                            >
                                <option value="">Select a time</option>
                                <option value="9:00 AM - 12:00 PM" {{ old('delivery_time') == '9:00 AM - 12:00 PM' ? 'selected' : '' }}>9:00 AM - 12:00 PM</option>
                                <option value="12:00 PM - 3:00 PM" {{ old('delivery_time') == '12:00 PM - 3:00 PM' ? 'selected' : '' }}>12:00 PM - 3:00 PM</option>
                                <option value="3:00 PM - 6:00 PM" {{ old('delivery_time') == '3:00 PM - 6:00 PM' ? 'selected' : '' }}>3:00 PM - 6:00 PM</option>
                            </select>
                            @error('delivery_time')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid gap-2">
                        <label for="special_instructions" class="text-sm font-medium">
                            Special Instructions (Optional)
                        </label>
                        <textarea
                            id="special_instructions"
                            name="special_instructions"
                            class="w-full min-h-[80px] rounded-md border border-input px-3 py-2"
                        >{{ old('special_instructions') }}</textarea>
                    </div>
                    
                    <h2 class="text-xl font-semibold mt-6 mb-4">Payment Method</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="payment_method_credit_card"
                                name="payment_method"
                                value="credit_card"
                                {{ old('payment_method', 'credit_card') == 'credit_card' ? 'checked' : '' }}
                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_credit_card" class="text-sm font-medium">
                                Credit Card
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="payment_method_paypal"
                                name="payment_method"
                                value="paypal"
                                {{ old('payment_method') == 'paypal' ? 'checked' : '' }}
                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_paypal" class="text-sm font-medium">
                                PayPal
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="payment_method_cash_on_delivery"
                                name="payment_method"
                                value="cash_on_delivery"
                                {{ old('payment_method') == 'cash_on_delivery' ? 'checked' : '' }}
                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_cash_on_delivery" class="text-sm font-medium">
                                Cash on Delivery
                            </label>
                        </div>
                        
                        @error('payment_method')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full">
                            Place Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
