<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    protected $cartService;

    public function __construct(CartServices $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Show the checkout form.
     */
    public function index()
    {
        $cart = $this->cartService->getCart();
        
        // Redirect to cart if it's empty
        if (empty($cart['items'])) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }
        
        return view('checkout.index', compact('cart'));
    }

    /**
     * Process the checkout.
     */
    public function process(Request $request)
    {
        $cart = $this->cartService->getCart();
        
        // Validate cart is not empty
        if (empty($cart['items'])) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }
        
        // Validate checkout form
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'delivery_address' => 'required|string',
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_time' => 'required|string',
            'special_instructions' => 'nullable|string',
            'payment_method' => 'required|in:credit_card,paypal,cash_on_delivery'
        ]);
        
        // Create order
        try {
            DB::beginTransaction();
            
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $cart['subtotal'],
                'tax' => $cart['tax'],
                'total' => $cart['total'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'delivery_address' => $validated['delivery_address'],
                'delivery_date' => $validated['delivery_date'],
                'delivery_time' => $validated['delivery_time'],
                'special_instructions' => $validated['special_instructions'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending'
            ]);
            
            // Create order items
            foreach ($cart['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'cake_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'customization' => $item['customization']
                ]);
            }
            
            // In a real application, you would process payment here
            // For this example, we'll just simulate a successful payment
            $order->payment_status = 'paid';
            $order->transaction_id = 'SIMULATED_' . uniqid();
            $order->save();
            
            DB::commit();
            
            // Clear the cart
            $this->cartService->clearCart();
            
            // Redirect to order confirmation
            return redirect()->route('checkout.confirmation', $order->order_number);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'An error occurred while processing your order. Please try again.');
        }
    }

    /**
     * Show the order confirmation.
     */
    public function confirmation($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        return view('checkout.confirmation', compact('order'));
    }
}
