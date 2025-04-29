<?php

namespace App\Http\Controllers;

use App\Models\Cake;
use App\Services\CartServices;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartServices $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the cart.
     */
    public function index()
    {
        $cart = $this->cartService->getCart();
        return view('cart.index', compact('cart'));
    }

    /**
     * Add a cake to the cart.
     */
    public function addToCart(Request $request, Cake $cake)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'customization' => 'nullable|string'
        ]);

        $this->cartService->addToCart(
            $cake, 
            $validated['quantity'], 
            $validated['customization'] ?? null
        );

        return redirect()->route('cart.index')->with('success', $cake->name . ' added to your cart!');
    }

    /**
     * Update the quantity of a cake in the cart.
     */
    public function updateCartItem(Request $request, $index)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $this->cartService->updateCartItem($index, $validated['quantity']);

        return redirect()->route('cart.index')->with('success', 'Cart updated successfully!');
    }

    /**
     * Remove a cake from the cart.
     */
    public function removeCartItem($index)
    {
        $this->cartService->removeCartItem($index);
        return redirect()->route('cart.index')->with('success', 'Item removed from cart!');
    }

    /**
     * Clear the entire cart.
     */
    public function clearCart()
    {
        $this->cartService->clearCart();
        return redirect()->route('cart.index')->with('success', 'Cart cleared!');
    }
}
