<?php

namespace App\Services;

use App\Models\Cake;
use Illuminate\Support\Facades\Session;

class CartServices
{
    /**
     * Get the current cart from the session.
     */
    public function getCart()
    {
        return Session::get('cart', [
            'items' => [],
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0
        ]);
    }

    /**
     * Add a cake to the cart.
     */
    public function addToCart(Cake $cake, int $quantity = 1, ?string $customization = null)
    {
        $cart = $this->getCart();
        $items = $cart['items'];

        // Check if the cake is already in the cart
        $itemIndex = $this->findItemIndex($items, $cake->id, $customization);

        if ($itemIndex !== false) {
            // Update quantity if the cake is already in the cart
            $items[$itemIndex]['quantity'] += $quantity;
            $items[$itemIndex]['subtotal'] = $items[$itemIndex]['quantity'] * $items[$itemIndex]['price'];
        } else {
            // Add new item to cart
            $items[] = [
                'id' => $cake->id,
                'name' => $cake->name,
                'price' => $cake->price,
                'quantity' => $quantity,
                'subtotal' => $cake->price * $quantity,
                'image' => $cake->image,
                'customization' => $customization
            ];
        }

        // Update cart totals
        $cart['items'] = $items;
        $this->updateCartTotals($cart);

        // Save cart to session
        Session::put('cart', $cart);

        return $cart;
    }

    /**
     * Update the quantity of a cake in the cart.
     */
    public function updateCartItem(int $itemIndex, int $quantity)
    {
        $cart = $this->getCart();
        
        if (isset($cart['items'][$itemIndex])) {
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                $this->removeCartItem($itemIndex);
                return $this->getCart();
            }
            
            $cart['items'][$itemIndex]['quantity'] = $quantity;
            $cart['items'][$itemIndex]['subtotal'] = $cart['items'][$itemIndex]['price'] * $quantity;
            
            $this->updateCartTotals($cart);
            Session::put('cart', $cart);
        }
        
        return $cart;
    }

    /**
     * Remove a cake from the cart.
     */
    public function removeCartItem(int $itemIndex)
    {
        $cart = $this->getCart();
        
        if (isset($cart['items'][$itemIndex])) {
            array_splice($cart['items'], $itemIndex, 1);
            $this->updateCartTotals($cart);
            Session::put('cart', $cart);
        }
        
        return $cart;
    }

    /**
     * Clear the entire cart.
     */
    public function clearCart()
    {
        Session::forget('cart');
        return $this->getCart();
    }

    /**
     * Find the index of an item in the cart.
     */
    private function findItemIndex(array $items, int $cakeId, ?string $customization)
    {
        foreach ($items as $index => $item) {
            if ($item['id'] === $cakeId && $item['customization'] === $customization) {
                return $index;
            }
        }
        
        return false;
    }

    /**
     * Update the cart totals.
     */
    private function updateCartTotals(array &$cart)
    {
        $subtotal = 0;
        
        foreach ($cart['items'] as $item) {
            $subtotal += $item['subtotal'];
        }
        
        $cart['subtotal'] = $subtotal;
        $cart['tax'] = $subtotal * 0.08; // 8% tax rate
        $cart['total'] = $subtotal + $cart['tax'];
    }
}
