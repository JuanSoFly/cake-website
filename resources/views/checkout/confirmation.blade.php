@extends('layouts.app')

@section('content')
<section class="w-full py-12 md:py-24 lg:py-32">
    <div class="container px-4 md:px-6">
        <div class="flex flex-col items-center justify-center space-y-4 text-center">
            <div class="rounded-full bg-green-100 p-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-green-600">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
            </div>
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                    Order Confirmed!
                </h1>
                <p class="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                    Thank you for your order. We've received your request and will begin preparing your delicious cake(s) soon.
                </p>
            </div>
        </div>
        
        <div class="mt-8 mx-auto max-w-3xl">
            <div class="rounded-lg border shadow-sm p-6">
                <div class="flex justify-between items-center border-b pb-4 mb-4">
                    <h2 class="text-xl font-semibold">Order #{{ $order->order_number }}</h2>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-pink-100 text-pink-800">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                
                <div class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Delivery Information</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm">{{ $order->customer_name }}</p>
                                <p class="text-sm">{{ $order->customer_email }}</p>
                                @if($order->customer_phone)
                                <p class="text-sm">{{ $order->customer_phone }}</p>
                                @endif
                                <p class="text-sm">{{ $order->delivery_address }}</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Delivery Details</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm">Date: {{ date('F j, Y', strtotime($order->delivery_date)) }}</p>
                                <p class="text-sm">Time: {{ $order->delivery_time }}</p>
                                @if($order->special_instructions)
                                <p class="text-sm">Special Instructions: {{ $order->special_instructions }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Order Items</h3>
                        <div class="mt-2 space-y-4">
                            @foreach($order->items as $item)
                            <div class="flex gap-4">
                                <img
                                    src="{{ asset('images/cakes/' . $item->cake->image) }}"
                                    alt="{{ $item->cake->name }}"
                                    class="aspect-square rounded-md object-cover h-16 w-16"
                                />
                                <div class="flex-1">
                                    <h4 class="font-medium">{{ $item->cake->name }}</h4>
                                    <div class="flex justify-between mt-1">
                                        <span class="text-sm text-gray-600">Qty: {{ $item->quantity }}</span>
                                        <span class="text-sm font-medium">${{ number_format($item->subtotal, 2) }}</span>
                                    </div>
                                    @if($item->customization)
                                    <p class="text-sm text-gray-600 mt-1">{{ $item->customization }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>₱{{ number_format($order->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Tax (8%)</span>
                                <span>₱{{ number_format($order->tax, 2) }}</span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between font-semibold">
                                    <span>Total</span>
                                    <span>₱{{ number_format($order->total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <h3 class="text-sm font-medium text-gray-500">Payment Information</h3>
                        <div class="mt-2 space-y-1">
                            <p class="text-sm">Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
                            <p class="text-sm">Status: {{ ucfirst($order->payment_status) }}</p>
                            @if($order->transaction_id)
                            <p class="text-sm">Transaction ID: {{ $order->transaction_id }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4">A confirmation email has been sent to {{ $order->customer_email }}</p>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                    Return to Home
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
