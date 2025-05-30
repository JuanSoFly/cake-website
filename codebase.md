# .editorconfig

```
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{yml,yaml}]
indent_size = 2

[docker-compose.yml]
indent_size = 4

```

# .gitattributes

```
* text=auto eol=lf

*.blade.php diff=html
*.css diff=css
*.html diff=html
*.md diff=markdown
*.php diff=php

/.github export-ignore
CHANGELOG.md export-ignore
.styleci.yml export-ignore

```

# .gitignore

```
/.phpunit.cache
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/storage/pail
/vendor
.env
.env.backup
.env.production
.phpactor.json
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/auth.json
/.fleet
/.idea
/.nova
/.vscode
/.zed

```

# app\Http\Controllers\CakeController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Cake;
use Illuminate\Http\Request;

class CakeController extends Controller
{
     /**
     * Display a listing of the cakes.
     */
    public function index()
    {
        $cakes = Cake::all();
        return view('cakes.index', compact('cakes'));
    }

    /**
     * Display the specified cake.
     */
    public function show(Cake $cake)
    {
        return view('cakes.show', compact('cake'));
    }
}

```

# app\Http\Controllers\CartController.php

```php
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

```

# app\Http\Controllers\CheckoutController.php

```php
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
            'payment_method' => 'required|in:gcash,maya,cash_on_delivery'
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

```

# app\Http\Controllers\ContactController.php

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function submit(Request $request)
    {
        // Validate the form data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        // In a real application, you would send an email here
        // Mail::to('info@sweetdelights.com')->send(new ContactFormSubmission($validated));

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Thank you for your message! We will get back to you soon.');
    }
}

```

# app\Http\Controllers\Controller.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Cake;

abstract class Controller
{
    public function index()
    {
        // Get featured cakes for the homepage
        $cakes = Cake::where('is_featured', true)->take(6)->get();

        // Sample testimonial data
        $testimonials = [
            [
                'name' => 'Sarah Johnson',
                'initials' => 'SJ',
                'comment' => 'The birthday cake you made for my daughter was absolutely perfect! Not only was it beautiful, but it tasted amazing too. Everyone at the party was impressed.',
                'rating' => 5,
            ],
            [
                'name' => 'Michael Chen',
                'initials' => 'MC',
                'comment' => 'We ordered our wedding cake from Sweet Delights and couldn\'t be happier. The design process was fun, and the final cake exceeded our expectations. Thank you!',
                'rating' => 5,
            ],
            [
                'name' => 'Emily Rodriguez',
                'initials' => 'ER',
                'comment' => 'I\'ve tried many bakeries, but Sweet Delights is by far the best. Their cakes are moist, flavorful, and the decorations are always on point. My go-to for all celebrations!',
                'rating' => 5,
            ],
        ];

        return view('home', compact('cakes', 'testimonials'));
    }
}

```

# app\Http\Controllers\HomeController.php

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
}

```

# app\Models\Cake.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cake extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'is_featured'
    ];

    /**
     * Get the formatted price with currency symbol.
     */
    public function getFormattedPriceAttribute()
    {
        return '₱' . number_format($this->price, 2);
    }

    /**
     * Get the orders that contain this cake.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

```

# app\Models\Order.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'status',
        'subtotal',
        'tax',
        'total',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'delivery_date',
        'delivery_time',
        'special_instructions',
        'payment_method',
        'payment_status',
        'transaction_id'
    ];

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber()
    {
        $prefix = 'SD'; // Sweet Delights
        $timestamp = now()->format('YmdHis');
        $random = rand(100, 999);
        return $prefix . $timestamp . $random;
    }

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

```

# app\Models\OrderItem.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'cake_id',
        'quantity',
        'price',
        'subtotal',
        'customization'
    ];

    /**
     * Get the order that owns the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the cake that is ordered.
     */
    public function cake()
    {
        return $this->belongsTo(Cake::class);
    }
}

```

# app\Models\User.php

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

```

# app\page.tsx

```tsx
import Link from "next/link"
import Image from "next/image"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Cake, ChevronRight, Heart, MapPin, Phone, Star } from "lucide-react"

export default function Home() {
  return (
    <div className="flex flex-col min-h-screen">
      <header className="sticky top-0 z-10 bg-white border-b">
        <div className="container flex items-center justify-between h-16 px-4 md:px-6">
          <Link href="/" className="flex items-center gap-2 text-xl font-bold text-pink-600">
            <Cake className="w-6 h-6" />
            <span>Sweet Delights</span>
          </Link>
          <nav className="hidden md:flex items-center gap-6">
            <Link href="#" className="text-sm font-medium hover:underline underline-offset-4">
              Home
            </Link>
            <Link href="#cakes" className="text-sm font-medium hover:underline underline-offset-4">
              Our Cakes
            </Link>
            <Link href="#about" className="text-sm font-medium hover:underline underline-offset-4">
              About Us
            </Link>
            <Link href="#testimonials" className="text-sm font-medium hover:underline underline-offset-4">
              Testimonials
            </Link>
            <Link href="#contact" className="text-sm font-medium hover:underline underline-offset-4">
              Contact
            </Link>
          </nav>
          <Button className="hidden md:inline-flex bg-pink-600 hover:bg-pink-700">Order Now</Button>
          <Button variant="outline" size="icon" className="md:hidden">
            <span className="sr-only">Toggle menu</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              strokeLinejoin="round"
              className="h-6 w-6"
            >
              <line x1="4" x2="20" y1="12" y2="12" />
              <line x1="4" x2="20" y1="6" y2="6" />
              <line x1="4" x2="20" y1="18" y2="18" />
            </svg>
          </Button>
        </div>
      </header>
      <main className="flex-1">
        <section className="w-full py-12 md:py-24 lg:py-32 bg-pink-50">
          <div className="container px-4 md:px-6">
            <div className="grid gap-6 lg:grid-cols-2 lg:gap-12 items-center">
              <div className="flex flex-col justify-center space-y-4">
                <div className="space-y-2">
                  <h1 className="text-3xl font-bold tracking-tighter sm:text-5xl xl:text-6xl/none text-pink-800">
                    Delicious Cakes for Every Occasion
                  </h1>
                  <p className="max-w-[600px] text-gray-600 md:text-xl">
                    Handcrafted with love and the finest ingredients. Our cakes make your special moments unforgettable.
                  </p>
                </div>
                <div className="flex flex-col gap-2 min-[400px]:flex-row">
                  <Button className="bg-pink-600 hover:bg-pink-700">Order Now</Button>
                  <Button variant="outline">View Menu</Button>
                </div>
              </div>
              <div className="mx-auto w-full max-w-[500px] lg:max-w-none relative">
                <Image
                  src="/placeholder.svg?height=600&width=600"
                  alt="Beautiful tiered cake with floral decorations"
                  width={600}
                  height={600}
                  className="mx-auto aspect-square rounded-xl object-cover"
                  priority
                />
              </div>
            </div>
          </div>
        </section>

        <section id="cakes" className="w-full py-12 md:py-24 lg:py-32">
          <div className="container px-4 md:px-6">
            <div className="flex flex-col items-center justify-center space-y-4 text-center">
              <div className="space-y-2">
                <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                  Our Signature Cakes
                </h2>
                <p className="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                  Explore our collection of handcrafted cakes made with premium ingredients and love.
                </p>
              </div>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
              {[
                {
                  name: "Chocolate Delight",
                  description: "Rich chocolate layers with ganache and chocolate shavings",
                  price: "$45",
                },
                {
                  name: "Strawberry Dream",
                  description: "Light vanilla cake with fresh strawberries and cream",
                  price: "$48",
                },
                {
                  name: "Red Velvet",
                  description: "Classic red velvet with cream cheese frosting",
                  price: "$50",
                },
                {
                  name: "Lemon Bliss",
                  description: "Tangy lemon cake with lemon curd and buttercream",
                  price: "$46",
                },
                {
                  name: "Carrot Cake",
                  description: "Spiced carrot cake with walnuts and cream cheese frosting",
                  price: "$42",
                },
                {
                  name: "Wedding Special",
                  description: "Elegant tiered cake customized for your special day",
                  price: "From $120",
                },
              ].map((cake, index) => (
                <Card key={index} className="overflow-hidden">
                  <div className="relative">
                    <Image
                      src={`/placeholder.svg?height=300&width=400&text=${cake.name}`}
                      alt={cake.name}
                      width={400}
                      height={300}
                      className="object-cover w-full h-48"
                    />
                    <Button
                      size="icon"
                      variant="ghost"
                      className="absolute top-2 right-2 rounded-full bg-white/80 hover:bg-white/90"
                    >
                      <Heart className="h-5 w-5 text-pink-600" />
                      <span className="sr-only">Add to favorites</span>
                    </Button>
                  </div>
                  <CardContent className="p-4">
                    <div className="flex justify-between items-start">
                      <div>
                        <h3 className="font-semibold text-lg">{cake.name}</h3>
                        <p className="text-sm text-gray-600 mt-1">{cake.description}</p>
                      </div>
                      <div className="text-pink-600 font-bold">{cake.price}</div>
                    </div>
                    <Button className="w-full mt-4 bg-pink-600 hover:bg-pink-700">Order Now</Button>
                  </CardContent>
                </Card>
              ))}
            </div>
            <div className="flex justify-center mt-8">
              <Button variant="outline" className="flex items-center gap-2">
                View All Cakes <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </section>

        <section id="about" className="w-full py-12 md:py-24 lg:py-32 bg-pink-50">
          <div className="container px-4 md:px-6">
            <div className="grid gap-6 lg:grid-cols-2 lg:gap-12 items-center">
              <div className="mx-auto w-full max-w-[500px] lg:max-w-none">
                <Image
                  src="/placeholder.svg?height=600&width=600&text=Our+Bakery"
                  alt="Our bakery interior with bakers working"
                  width={600}
                  height={600}
                  className="mx-auto rounded-xl object-cover"
                />
              </div>
              <div className="flex flex-col justify-center space-y-4">
                <div className="space-y-2">
                  <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                    Our Sweet Story
                  </h2>
                  <p className="max-w-[600px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                    Sweet Delights was founded in 2010 with a simple mission: to create delicious, beautiful cakes that
                    bring joy to every celebration.
                  </p>
                </div>
                <div className="space-y-4">
                  <p className="text-gray-600">
                    Our team of passionate bakers combines traditional techniques with innovative flavors to create
                    cakes that are as delightful to look at as they are to eat. We use only the finest ingredients,
                    sourced locally whenever possible.
                  </p>
                  <p className="text-gray-600">
                    From intimate birthday celebrations to grand weddings, we take pride in being part of your special
                    moments. Each cake is crafted with attention to detail and customized to your preferences.
                  </p>
                </div>
                <div>
                  <Button className="bg-pink-600 hover:bg-pink-700">Meet Our Team</Button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="testimonials" className="w-full py-12 md:py-24 lg:py-32">
          <div className="container px-4 md:px-6">
            <div className="flex flex-col items-center justify-center space-y-4 text-center">
              <div className="space-y-2">
                <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                  What Our Customers Say
                </h2>
                <p className="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                  Don't just take our word for it. Here's what our happy customers have to say.
                </p>
              </div>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
              {[
                {
                  name: "Sarah Johnson",
                  comment:
                    "The birthday cake you made for my daughter was absolutely perfect! Not only was it beautiful, but it tasted amazing too. Everyone at the party was impressed.",
                  rating: 5,
                },
                {
                  name: "Michael Chen",
                  comment:
                    "We ordered our wedding cake from Sweet Delights and couldn't be happier. The design process was fun, and the final cake exceeded our expectations. Thank you!",
                  rating: 5,
                },
                {
                  name: "Emily Rodriguez",
                  comment:
                    "I've tried many bakeries, but Sweet Delights is by far the best. Their cakes are moist, flavorful, and the decorations are always on point. My go-to for all celebrations!",
                  rating: 5,
                },
              ].map((testimonial, index) => (
                <Card key={index} className="p-6">
                  <div className="flex flex-col space-y-4">
                    <div className="flex">
                      {Array(testimonial.rating)
                        .fill(0)
                        .map((_, i) => (
                          <Star key={i} className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                        ))}
                    </div>
                    <p className="text-gray-600 italic">"{testimonial.comment}"</p>
                    <div className="flex items-center space-x-2">
                      <div className="rounded-full bg-pink-100 p-1">
                        <span className="text-pink-600 font-bold text-sm">
                          {testimonial.name
                            .split(" ")
                            .map((n) => n[0])
                            .join("")}
                        </span>
                      </div>
                      <span className="font-medium">{testimonial.name}</span>
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          </div>
        </section>

        <section id="contact" className="w-full py-12 md:py-24 lg:py-32 bg-pink-50">
          <div className="container px-4 md:px-6">
            <div className="grid gap-6 lg:grid-cols-2 lg:gap-12 items-center">
              <div className="flex flex-col justify-center space-y-4">
                <div className="space-y-2">
                  <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                    Get in Touch
                  </h2>
                  <p className="max-w-[600px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                    Have questions or want to place an order? We'd love to hear from you!
                  </p>
                </div>
                <div className="space-y-4">
                  <div className="flex items-center gap-2">
                    <MapPin className="h-5 w-5 text-pink-600" />
                    <p>123 Bakery Street, Sweet City, SC 12345</p>
                  </div>
                  <div className="flex items-center gap-2">
                    <Phone className="h-5 w-5 text-pink-600" />
                    <p>(555) 123-4567</p>
                  </div>
                  <div className="flex items-center gap-2">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      className="h-5 w-5 text-pink-600"
                    >
                      <rect width="20" height="16" x="2" y="4" rx="2" />
                      <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                    </svg>
                    <p>info@sweetdelights.com</p>
                  </div>
                </div>
                <div className="space-y-2">
                  <h3 className="text-xl font-bold">Hours</h3>
                  <div className="grid grid-cols-2 gap-2">
                    <div>Monday - Friday</div>
                    <div>8:00 AM - 6:00 PM</div>
                    <div>Saturday</div>
                    <div>9:00 AM - 5:00 PM</div>
                    <div>Sunday</div>
                    <div>10:00 AM - 3:00 PM</div>
                  </div>
                </div>
              </div>
              <div className="mx-auto w-full max-w-[500px] lg:max-w-none">
                <form className="grid gap-4 p-6 bg-white rounded-xl shadow-sm">
                  <div className="grid gap-2">
                    <label htmlFor="name" className="text-sm font-medium">
                      Name
                    </label>
                    <input id="name" placeholder="Your name" className="border rounded-md px-3 py-2" required />
                  </div>
                  <div className="grid gap-2">
                    <label htmlFor="email" className="text-sm font-medium">
                      Email
                    </label>
                    <input
                      id="email"
                      type="email"
                      placeholder="Your email"
                      className="border rounded-md px-3 py-2"
                      required
                    />
                  </div>
                  <div className="grid gap-2">
                    <label htmlFor="message" className="text-sm font-medium">
                      Message
                    </label>
                    <textarea
                      id="message"
                      placeholder="Your message"
                      className="border rounded-md px-3 py-2 min-h-[120px]"
                      required
                    />
                  </div>
                  <Button className="w-full bg-pink-600 hover:bg-pink-700">Send Message</Button>
                </form>
              </div>
            </div>
          </div>
        </section>
      </main>
      <footer className="border-t bg-white">
        <div className="container flex flex-col gap-4 px-4 py-6 md:flex-row md:items-center md:gap-6 md:px-6 md:py-8">
          <div className="flex items-center gap-2 text-xl font-bold text-pink-600">
            <Cake className="w-6 h-6" />
            <span>Sweet Delights</span>
          </div>
          <nav className="flex gap-4 md:gap-6 md:ml-auto">
            <Link href="#" className="text-sm font-medium hover:underline underline-offset-4">
              Privacy Policy
            </Link>
            <Link href="#" className="text-sm font-medium hover:underline underline-offset-4">
              Terms of Service
            </Link>
            <Link href="#" className="text-sm font-medium hover:underline underline-offset-4">
              Careers
            </Link>
          </nav>
          <div className="flex items-center gap-4 md:ml-auto md:gap-2">
            <Button variant="ghost" size="icon" aria-label="Facebook">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="h-5 w-5"
              >
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
              </svg>
            </Button>
            <Button variant="ghost" size="icon" aria-label="Instagram">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="h-5 w-5"
              >
                <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
              </svg>
            </Button>
            <Button variant="ghost" size="icon" aria-label="Twitter">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="h-5 w-5"
              >
                <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z" />
              </svg>
            </Button>
          </div>
        </div>
        <div className="border-t py-4 text-center text-sm text-gray-500">
          © 2025 Sweet Delights. All rights reserved.
        </div>
      </footer>
    </div>
  )
}

```

# app\Providers\AppServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

```

# app\Services\CartServices.php

```php
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

```

# artisan

```
#!/usr/bin/env php
<?php

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the command...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$status = $app->handleCommand(new ArgvInput);

exit($status);

```

# bootstrap\app.php

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

```

# bootstrap\cache\.gitignore

```
*
!.gitignore

```

# bootstrap\cache\packages.php

```php
<?php return array (
  'laravel/pail' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Pail\\PailServiceProvider',
    ),
  ),
  'laravel/sail' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Sail\\SailServiceProvider',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
  'nunomaduro/termwind' => 
  array (
    'providers' => 
    array (
      0 => 'Termwind\\Laravel\\TermwindServiceProvider',
    ),
  ),
);
```

# bootstrap\cache\services.php

```php
<?php return array (
  'providers' => 
  array (
    0 => 'Illuminate\\Auth\\AuthServiceProvider',
    1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    2 => 'Illuminate\\Bus\\BusServiceProvider',
    3 => 'Illuminate\\Cache\\CacheServiceProvider',
    4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
    6 => 'Illuminate\\Cookie\\CookieServiceProvider',
    7 => 'Illuminate\\Database\\DatabaseServiceProvider',
    8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
    10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    11 => 'Illuminate\\Hashing\\HashServiceProvider',
    12 => 'Illuminate\\Mail\\MailServiceProvider',
    13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
    14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    17 => 'Illuminate\\Queue\\QueueServiceProvider',
    18 => 'Illuminate\\Redis\\RedisServiceProvider',
    19 => 'Illuminate\\Session\\SessionServiceProvider',
    20 => 'Illuminate\\Translation\\TranslationServiceProvider',
    21 => 'Illuminate\\Validation\\ValidationServiceProvider',
    22 => 'Illuminate\\View\\ViewServiceProvider',
    23 => 'Laravel\\Pail\\PailServiceProvider',
    24 => 'Laravel\\Sail\\SailServiceProvider',
    25 => 'Laravel\\Tinker\\TinkerServiceProvider',
    26 => 'Carbon\\Laravel\\ServiceProvider',
    27 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    28 => 'Termwind\\Laravel\\TermwindServiceProvider',
    29 => 'App\\Providers\\AppServiceProvider',
  ),
  'eager' => 
  array (
    0 => 'Illuminate\\Auth\\AuthServiceProvider',
    1 => 'Illuminate\\Cookie\\CookieServiceProvider',
    2 => 'Illuminate\\Database\\DatabaseServiceProvider',
    3 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
    4 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
    5 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
    6 => 'Illuminate\\Notifications\\NotificationServiceProvider',
    7 => 'Illuminate\\Pagination\\PaginationServiceProvider',
    8 => 'Illuminate\\Session\\SessionServiceProvider',
    9 => 'Illuminate\\View\\ViewServiceProvider',
    10 => 'Laravel\\Pail\\PailServiceProvider',
    11 => 'Carbon\\Laravel\\ServiceProvider',
    12 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    13 => 'Termwind\\Laravel\\TermwindServiceProvider',
    14 => 'App\\Providers\\AppServiceProvider',
  ),
  'deferred' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastManager' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Factory' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Contracts\\Broadcasting\\Broadcaster' => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
    'Illuminate\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\Dispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Contracts\\Bus\\QueueingDispatcher' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\BatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'Illuminate\\Bus\\DatabaseBatchRepository' => 'Illuminate\\Bus\\BusServiceProvider',
    'cache' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.store' => 'Illuminate\\Cache\\CacheServiceProvider',
    'cache.psr6' => 'Illuminate\\Cache\\CacheServiceProvider',
    'memcached.connector' => 'Illuminate\\Cache\\CacheServiceProvider',
    'Illuminate\\Cache\\RateLimiter' => 'Illuminate\\Cache\\CacheServiceProvider',
    'Illuminate\\Foundation\\Console\\AboutCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\ForgetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ClearCompiledCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Auth\\Console\\ClearResetsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\DbCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\PruneCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\ShowCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\WipeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DownCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentDecryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnvironmentEncryptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Concurrency\\Console\\InvokeSerializedClosureCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\KeyGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\OptimizeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\OptimizeClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\PackageDiscoverCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\PruneStaleTagsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\FlushFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ForgetFailedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\ListenCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\MonitorCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneBatchesCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\PruneFailedJobsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RestartCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\RetryBatchCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\WorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RouteListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\DumpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Seeds\\SeedCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleFinishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleRunCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleClearCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleTestCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleWorkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Console\\Scheduling\\ScheduleInterruptCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\ShowModelCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StorageLinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StorageUnlinkCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\UpCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewCacheCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewClearCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ApiInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\BroadcastingInstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Cache\\Console\\CacheTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\CastMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ChannelListCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ChannelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ClassMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ComponentMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConfigPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ConsoleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Routing\\Console\\ControllerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\DocsCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EnumMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventGenerateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\EventMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ExceptionMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Factories\\FactoryMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\InterfaceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\JobMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\JobMiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\LangPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ListenerMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\MailMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Routing\\Console\\MiddlewareMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ModelMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\NotificationMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Notifications\\Console\\NotificationTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ObserverMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\PolicyMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ProviderMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\FailedTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\TableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Queue\\Console\\BatchesTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RequestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ResourceMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\RuleMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ScopeMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Seeds\\SeederMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Session\\Console\\SessionTableCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ServeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\StubPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\TestMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\TraitMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\VendorPublishCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Foundation\\Console\\ViewMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.repository' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'migration.creator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Migrations\\Migrator' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\MigrateCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\FreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\InstallCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\RefreshCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\ResetCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\RollbackCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\StatusCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Database\\Console\\Migrations\\MigrateMakeCommand' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'composer' => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
    'Illuminate\\Concurrency\\ConcurrencyManager' => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
    'hash' => 'Illuminate\\Hashing\\HashServiceProvider',
    'hash.driver' => 'Illuminate\\Hashing\\HashServiceProvider',
    'mail.manager' => 'Illuminate\\Mail\\MailServiceProvider',
    'mailer' => 'Illuminate\\Mail\\MailServiceProvider',
    'Illuminate\\Mail\\Markdown' => 'Illuminate\\Mail\\MailServiceProvider',
    'auth.password' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    'auth.password.broker' => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
    'Illuminate\\Contracts\\Pipeline\\Hub' => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    'pipeline' => 'Illuminate\\Pipeline\\PipelineServiceProvider',
    'queue' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.connection' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.failer' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.listener' => 'Illuminate\\Queue\\QueueServiceProvider',
    'queue.worker' => 'Illuminate\\Queue\\QueueServiceProvider',
    'redis' => 'Illuminate\\Redis\\RedisServiceProvider',
    'redis.connection' => 'Illuminate\\Redis\\RedisServiceProvider',
    'translator' => 'Illuminate\\Translation\\TranslationServiceProvider',
    'translation.loader' => 'Illuminate\\Translation\\TranslationServiceProvider',
    'validator' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'validation.presence' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'Illuminate\\Contracts\\Validation\\UncompromisedVerifier' => 'Illuminate\\Validation\\ValidationServiceProvider',
    'Laravel\\Sail\\Console\\InstallCommand' => 'Laravel\\Sail\\SailServiceProvider',
    'Laravel\\Sail\\Console\\PublishCommand' => 'Laravel\\Sail\\SailServiceProvider',
    'command.tinker' => 'Laravel\\Tinker\\TinkerServiceProvider',
  ),
  'when' => 
  array (
    'Illuminate\\Broadcasting\\BroadcastServiceProvider' => 
    array (
    ),
    'Illuminate\\Bus\\BusServiceProvider' => 
    array (
    ),
    'Illuminate\\Cache\\CacheServiceProvider' => 
    array (
    ),
    'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider' => 
    array (
    ),
    'Illuminate\\Concurrency\\ConcurrencyServiceProvider' => 
    array (
    ),
    'Illuminate\\Hashing\\HashServiceProvider' => 
    array (
    ),
    'Illuminate\\Mail\\MailServiceProvider' => 
    array (
    ),
    'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider' => 
    array (
    ),
    'Illuminate\\Pipeline\\PipelineServiceProvider' => 
    array (
    ),
    'Illuminate\\Queue\\QueueServiceProvider' => 
    array (
    ),
    'Illuminate\\Redis\\RedisServiceProvider' => 
    array (
    ),
    'Illuminate\\Translation\\TranslationServiceProvider' => 
    array (
    ),
    'Illuminate\\Validation\\ValidationServiceProvider' => 
    array (
    ),
    'Laravel\\Sail\\SailServiceProvider' => 
    array (
    ),
    'Laravel\\Tinker\\TinkerServiceProvider' => 
    array (
    ),
  ),
);
```

# bootstrap\providers.php

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
];

```

# composer.json

```json
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10.1"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pail": "^1.2.2",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.41",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "phpunit/phpunit": "^11.5.3"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite"
        ],
        "test": [
            "@php artisan config:clear --ansi",
            "@php artisan test"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}

```

# config\app.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];

```

# config\auth.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];

```

# config\cache.php

```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache store that will be used by the
    | framework. This connection is utilized if another isn't explicitly
    | specified when running a cache operation inside the application.
    |
    */

    'default' => env('CACHE_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "array", "database", "file", "memcached",
    |                    "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, and DynamoDB cache
    | stores, there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

];

```

# config\database.php

```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'cake_website'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];

```

# config\filesystems.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];

```

# config\logging.php

```php
<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];

```

# config\mail.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];

```

# config\queue.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue supports a variety of backends via a single, unified
    | API, giving you convenient access to each backend using identical
    | syntax for each. The default queue connection is defined below.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for every queue backend
    | used by your application. An example configuration is provided for
    | each backend supported by Laravel. You're also free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control how and where failed jobs are stored. Laravel ships with
    | support for storing failed jobs in a simple file or in a database.
    |
    | Supported drivers: "database-uuids", "dynamodb", "file", "null"
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

];

```

# config\services.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

```

# config\session.php

```php
<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option determines the default session driver that is utilized for
    | incoming requests. Laravel supports a variety of storage options to
    | persist session data. Database storage is a great default choice.
    |
    | Supported: "file", "cookie", "database", "apc",
    |            "memcached", "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires. If you want them
    | to expire immediately when the browser is closed then you may
    | indicate that via the expire_on_close configuration option.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all of your session data
    | should be encrypted before it's stored. All encryption is performed
    | automatically by Laravel and you may use the session like normal.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When utilizing the "file" session driver, the session files are placed
    | on disk. The default storage location is defined here; however, you
    | are free to provide another location where they should be stored.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" or "redis" session drivers, you may specify a
    | connection that should be used to manage these sessions. This should
    | correspond to a connection in your database configuration options.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table to
    | be used to store sessions. Of course, a sensible default is defined
    | for you; however, you're welcome to change this to another table.
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Cache Store
    |--------------------------------------------------------------------------
    |
    | When using one of the framework's cache driven session backends, you may
    | define the cache store which should be used to store the session data
    | between requests. This must match one of your defined cache stores.
    |
    | Affects: "apc", "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery
    |--------------------------------------------------------------------------
    |
    | Some session drivers must manually sweep their storage location to get
    | rid of old sessions from storage. Here are the chances that it will
    | happen on a given request. By default, the odds are 2 out of 100.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the session cookie that is created by
    | the framework. Typically, you should not need to change this value
    | since doing so does not grant a meaningful security improvement.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Typically, this will be the root path of
    | your application, but you're free to change this when necessary.
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | This value determines the domain and subdomains the session cookie is
    | available to. By default, the cookie will be available to the root
    | domain and all subdomains. Typically, this shouldn't be changed.
    |
    */

    'domain' => env('SESSION_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection. This will keep
    | the cookie from being sent to you when it can't be done securely.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol. It's unlikely you should disable this option.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks. By default, we
    | will set this value to "lax" to permit secure cross-site requests.
    |
    | See: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value
    |
    | Supported: "lax", "strict", "none", null
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will tie the cookie to the top-level site for
    | a cross-site context. Partitioned cookies are accepted by the browser
    | when flagged "secure" and the Same-Site attribute is set to "none".
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];

```

# database\.gitignore

```
*.sqlite*

```

# database\factories\UserFactory.php

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

```

# database\migrations\0001_01_01_000000_create_users_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

```

# database\migrations\0001_01_01_000001_create_cache_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};

```

# database\migrations\0001_01_01_000002_create_jobs_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};

```

# database\migrations\2025_04_27_231359_create_cakes_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cakes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cakes');
    }
};

```

# database\migrations\2025_04_27_231400_create_orders_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('total', 10, 2);
            
            // Customer information
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('delivery_address');
            $table->date('delivery_date');
            $table->string('delivery_time');
            $table->text('special_instructions')->nullable();
            
            // Payment information
            $table->string('payment_method');
            $table->string('payment_status')->default('pending');
            $table->string('transaction_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

```

# database\migrations\2025_04_27_231518_create_order_items_table.php

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('cake_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 8, 2);
            $table->decimal('subtotal', 10, 2);
            $table->text('customization')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

```

# database\seeders\CakeSeeder.php

```php
<?php

namespace Database\Seeders;

use App\Models\Cake;
use Illuminate\Database\Seeder;

class CakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cakes = [
            [
                'name' => 'Chocolate Dedication Cake!',
                'description' => 'Enjoy our bestselling chocolate-rich and candy-colorful cake',
                'price' => 450.00,
                'image' => 'bday-cake.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Supreme Caramel-Mocha Dedication Cake!',
                'description' => 'Give a supreme gift for a supreme mom 💝 Show your love for mom ',
                'price' => 599.00,
                'image' => 'Supreme Caramel-Mocha Dedication Cake!.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Fudyy Brownies',
                'description' => 'Gift Mom the perfect choco-licious treat with our Fudgy Brownies!',
                'price' => 169.00,
                'image' => 'fudyy brownies.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Ensaimada',
                'description' => 'Enjoy our Cheesy Ensaimada! Topped with lots of delicious cheese and butter-cream – truly a cheesy-sweet treat! ',
                'price' => 35.00,
                'image' => 'ensaimada.jpg',
                'is_featured' => false, 
            ],
            [
                'name' => 'Bundle 3',
                'description' => 'Choose Regular black forest cake or chocolate dedication cake',
                'price' => 799.00,
                'image' => 'bundle.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Dedication cake',
                'description' => 'Top up the birthday fun with the NEW Princess and Cars Theme Toppers that can be added on any Red Ribbon Dedication Cake!',
                'price' => 675.00,
                'image' => 'dedication cakes.jpg',
                'is_featured' => true,
            ],
            [
                'name' => 'Black Forest',
                'description' => 'A delectable Black Forest cake to make your holiday even sweeter!',
                'price' => 569.00,
                'image' => 'black forest.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Red Velvet',
                'description' => ' A delectable Red Velvet Bliss cake to make your holiday even sweeter!',
                'price' => 569.00,
                'image' => 'red velvet.jpg',
                'is_featured' => false,
            ],
            [
                'name' => 'Chicken Empanada',
                'description' => 'Chicken Empanada: Taste Home in Every Bite! Grab yours',
                'price' => 55.00,
                'image' => 'empanada.jpg',
                'is_featured' => false,
            ],
        ];

        foreach ($cakes as $cake) {
            Cake::create($cake);
        }
    }
}

```

# database\seeders\DatabaseSeeder.php

```php
<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}

```

# package.json

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "axios": "^1.8.2",
        "concurrently": "^9.0.1",
        "laravel-vite-plugin": "^1.2.0",
        "tailwindcss": "^4.0.0",
        "vite": "^6.2.4"
    }
}

```

# phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>

```

# public\.htaccess

```
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

```

# public\favicon.ico

```ico

```

# public\images\bakery.jpg

This is a binary file of the type: Image

# public\images\cakes\bday-cake.jpg

This is a binary file of the type: Image

# public\images\cakes\black forest.jpg

This is a binary file of the type: Image

# public\images\cakes\bundle.jpg

This is a binary file of the type: Image

# public\images\cakes\dedication cakes.jpg

This is a binary file of the type: Image

# public\images\cakes\empanada.jpg

This is a binary file of the type: Image

# public\images\cakes\ensaimada.jpg

This is a binary file of the type: Image

# public\images\cakes\fudyy brownies.jpg

This is a binary file of the type: Image

# public\images\cakes\mango roll.jpg

This is a binary file of the type: Image

# public\images\cakes\red velvet.jpg

This is a binary file of the type: Image

# public\images\cakes\Supreme Caramel-Mocha Dedication Cake!.jpg

This is a binary file of the type: Image

# public\images\hero-cake.jpg

This is a binary file of the type: Image

# public\index.php

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

```

# public\robots.txt

```txt
User-agent: *
Disallow:

```

# README.md

```md
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development/)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

```

# resources\css\app.css

```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';
}
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  :root {
    --background: 0 0% 100%;
    --foreground: 240 10% 3.9%;
    --card: 0 0% 100%;
    --card-foreground: 240 10% 3.9%;
    --popover: 0 0% 100%;
    --popover-foreground: 240 10% 3.9%;
    --primary: 346.8 77.2% 49.8%;
    --primary-foreground: 355.7 100% 97.3%;
    --secondary: 240 4.8% 95.9%;
    --secondary-foreground: 240 5.9% 10%;
    --muted: 240 4.8% 95.9%;
    --muted-foreground: 240 3.8% 46.1%;
    --accent: 240 4.8% 95.9%;
    --accent-foreground: 240 5.9% 10%;
    --destructive: 0 84.2% 60.2%;
    --destructive-foreground: 0 0% 98%;
    --border: 240 5.9% 90%;
    --input: 240 5.9% 90%;
    --ring: 346.8 77.2% 49.8%;
    --radius: 0.5rem;
  }

  .dark {
    --background: 20 14.3% 4.1%;
    --foreground: 0 0% 95%;
    --card: 24 9.8% 10%;
    --card-foreground: 0 0% 95%;
    --popover: 0 0% 9%;
    --popover-foreground: 0 0% 95%;
    --primary: 346.8 77.2% 49.8%;
    --primary-foreground: 355.7 100% 97.3%;
    --secondary: 240 3.7% 15.9%;
    --secondary-foreground: 0 0% 98%;
    --muted: 0 0% 15%;
    --muted-foreground: 240 5% 64.9%;
    --accent: 12 6.5% 15.1%;
    --accent-foreground: 0 0% 98%;
    --destructive: 0 62.8% 30.6%;
    --destructive-foreground: 0 85.7% 97.3%;
    --border: 240 3.7% 15.9%;
    --input: 240 3.7% 15.9%;
    --ring: 346.8 77.2% 49.8%;
  }
}

```

# resources\js\app.js

```js
import './bootstrap';

```

# resources\js\bootstrap.js

```js
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

```

# resources\views\cakes\index.blade.php

```php
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
                        src="{{ asset('images/cakes/' . $cake->image) }}"
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

```

# resources\views\cakes\show.blade.php

```php
@extends('layouts.app')

@section('content')
<section class="w-full py-12 md:py-24 lg:py-32">
    <div class="container px-4 md:px-6">
        <div class="grid gap-6 lg:grid-cols-2 lg:gap-12 items-start">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-xl">
                    <img
                        src="{{ asset('images/cakes/' . $cake->image) }}"
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

```

# resources\views\cart\index.blade.php

```php
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

```

# resources\views\checkout\confirmation.blade.php

```php
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

```

# resources\views\checkout\index.blade.php

```php
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
                                id="payment_method_gcash"
                                name="payment_method"
                                value="gcash"
                                {{ old('payment_method', 'gcash') == 'gcash' ? 'checked' : '' }}
                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_gcash" class="text-sm font-medium">
                                GCASH
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="payment_method_maya"
                                name="payment_method"
                                value="maya"
                                {{ old('payment_method') == 'maya' ? 'checked' : '' }}
                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_maya" class="text-sm font-medium">
                                MAYA
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

```

# resources\views\coming-soon.blade.php

```php
@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-[60vh]">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-pink-800 mb-4">{{ $page }} Page Coming Soon</h1>
        <p class="text-gray-600 mb-6">We're working hard to bring you this feature. Please check back later!</p>
        <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
            Return to Home
        </a>
    </div>
</div>
@endsection

```

# resources\views\home.blade.php

```php
@extends('layouts.app')

@section('content')
    <section class="w-full py-12 md:py-24 lg:py-32 bg-pink-50">
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
                        src="{{ asset('images/hero-cake.jpg') }}"
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
                        Our Signature Cakes
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
                            Our Sweet Story
                        </h2>
                        <p class="max-w-[600px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                            Sweet Delights was founded in 2010 with a simple mission: to create delicious, beautiful cakes that
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

```

# resources\views\layouts\app.blade.php

```php
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
                {{-- 1. Add x-data and the event listener to the header --}}
        <header
            x-data="{ isMobileMenuOpen: false }"
            @toggle-mobile-menu.window="isMobileMenuOpen = !isMobileMenuOpen"
            class="sticky top-0 z-30 bg-white border-b" {{-- Increased z-index --}}
        >
            <div class="container flex items-center justify-between h-16 px-4 md:px-6 relative"> {{-- Added relative positioning if menu is absolute --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-pink-600">
                    {{-- Logo SVG --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M8.37 12.37a2.5 2.5 0 0 0-3.326.602c-.47.692-.348 1.635.252 2.236l1.544 1.544c.603.603 1.55.726 2.24.254a2.5 2.5 0 0 0 .601-3.328"></path><path d="m14.5 12.5 2.25 2.25"></path><path d="m11.5 9.5 2.25 2.25"></path><path d="M8.5 6.5 13 11"></path><path d="M20 14c0 4.418-4.477 8-10 8-3.41 0-6.42-1.33-8-3.5"></path><path d="M5.5 6.5 8 9"></path><path d="M3 3v4"></path><path d="M7 3H3"></path><path d="M14 10V4a2 2 0 0 0-2-2H8"></path><path d="M4 15H2"></path></svg>
                    <span>Sweet Delights</span>
                </a>

                {{-- Desktop Navigation --}}
                <nav class="hidden md:flex items-center gap-6">
                    <a href="{{ route('home') }}" class="text-sm font-medium hover:underline underline-offset-4">Home</a>
                    <a href="#cakes" class="text-sm font-medium hover:underline underline-offset-4">Our Cakes</a>
                    <a href="#about" class="text-sm font-medium hover:underline underline-offset-4">About Us</a>
                    <a href="#testimonials" class="text-sm font-medium hover:underline underline-offset-4">Testimonials</a>
                    <a href="#contact" class="text-sm font-medium hover:underline underline-offset-4">Contact</a>
                </nav>

                {{-- Desktop Order Button --}}
                <a href="{{ route('order') }}" class="hidden md:inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                    Order Now
                </a>

                {{-- 4. Update Mobile Menu Toggle Button --}}
                <button
                    @click="$dispatch('toggle-mobile-menu')"
                    class="md:hidden inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 w-10"
                    aria-controls="mobile-menu"
                    :aria-expanded="isMobileMenuOpen.toString()"
                    aria-label="Toggle navigation menu"
                >
                    {{-- Hamburger Icon (shown when menu is closed) --}}
                    <svg x-show="!isMobileMenuOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <line x1="4" x2="20" y1="12" y2="12" />
                        <line x1="4" x2="20" y1="6" y2="6" />
                        <line x1="4" x2="20" y1="18" y2="18" />
                    </svg>
                    {{-- Close 'X' Icon (shown when menu is open) --}}
                    <svg x-show="isMobileMenuOpen" x-cloak xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                         <line x1="18" y1="6" x2="6" y2="18" />
                         <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>

                {{-- 3. Add the Mobile Menu itself --}}
                <nav
                    id="mobile-menu"
                    x-show="isMobileMenuOpen"
                    x-cloak
                    @click.outside="isMobileMenuOpen = false" {{-- Optional: close menu when clicking outside --}}
                    class="md:hidden absolute top-full left-0 right-0 z-20 bg-white border-b border-t p-4 space-y-2"
                    aria-label="Mobile navigation"
                    >
                    <a href="{{ route('home') }}" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Home</a>
                    <a href="#cakes" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Our Cakes</a>
                    <a href="#about" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">About Us</a>
                    <a href="#testimonials" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Testimonials</a>
                    <a href="#contact" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Contact</a>
                    <a href="{{ route('order') }}" @click="isMobileMenuOpen = false" class="mt-2 block w-full text-center rounded-md text-sm font-medium bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 flex items-center justify-center">
                        Order Now
                    </a>
                </nav>
            </div> {{-- End .container --}}
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
                    <a href="https://www.facebook.com/AKennesu02" target="_blank" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 w-10" aria-label="Facebook">
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

```

# routes\console.php

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

```

# routes\web.php

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CakeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Cake routes
Route::get('/cakes', [CakeController::class, 'index'])->name('cakes.index');
Route::get('/cakes/{cake}', [CakeController::class, 'show'])->name('cakes.show');

// Cart routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{cake}', [CartController::class, 'addToCart'])->name('cart.add');
Route::patch('/cart/update/{index}', [CartController::class, 'updateCartItem'])->name('cart.update');
Route::delete('/cart/remove/{index}', [CartController::class, 'removeCartItem'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clearCart'])->name('cart.clear');

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/checkout/confirmation/{orderNumber}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

// Placeholder routes for other pages
Route::get('/menu', function () {
    return redirect()->route('cakes.index');
})->name('menu');

Route::get('/order', function () {
    return redirect()->route('cakes.index');
})->name('order');

Route::get('/team', function () {
    return view('coming-soon', ['page' => 'Team']);
})->name('team');

```

# storage\app\.gitignore

```
*
!private/
!public/
!.gitignore

```

# storage\app\private\.gitignore

```
*
!.gitignore

```

# storage\app\public\.gitignore

```
*
!.gitignore

```

# storage\framework\.gitignore

```
compiled.php
config.php
down
events.scanned.php
maintenance.php
routes.php
routes.scanned.php
schedule-*
services.json

```

# storage\framework\cache\.gitignore

```
*
!data/
!.gitignore

```

# storage\framework\cache\data\.gitignore

```
*
!.gitignore

```

# storage\framework\sessions\.gitignore

```
*
!.gitignore

```

# storage\framework\testing\.gitignore

```
*
!.gitignore

```

# storage\framework\views\.gitignore

```
*
!.gitignore

```

# storage\framework\views\0af2a6aea70a34caf99d5a932eed9a0e.php

```php
<svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    <?php echo e($attributes); ?>

>
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
</svg>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/icons/sun.blade.php ENDPATH**/ ?>
```

# storage\framework\views\0b422e89e3f96fae69ec6e265c08037c.php

```php
<?php $__env->startSection('content'); ?>
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
        
        <?php if(session('error')): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
            <span class="block sm:inline"><?php echo e(session('error')); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="mt-8 grid gap-8 md:grid-cols-2">
            <div>
                <div class="rounded-lg border shadow-sm p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-4">
                        <?php $__currentLoopData = $cart['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex gap-4">
                            <img
                                src="<?php echo e(asset('images/cakes/' . $item['image'])); ?>"
                                alt="<?php echo e($item['name']); ?>"
                                class="aspect-square rounded-md object-cover h-16 w-16"
                            />
                            <div class="flex-1">
                                <h3 class="font-medium"><?php echo e($item['name']); ?></h3>
                                <div class="flex justify-between mt-1">
                                    <span class="text-sm text-gray-600">Qty: <?php echo e($item['quantity']); ?></span>
                                    <span class="text-sm font-medium">$<?php echo e(number_format($item['subtotal'], 2)); ?></span>
                                </div>
                                <?php if($item['customization']): ?>
                                <p class="text-sm text-gray-600 mt-1"><?php echo e($item['customization']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="border-t mt-4 pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>₱<?php echo e(number_format($cart['subtotal'], 2)); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tax (8%)</span>
                            <span>₱<?php echo e(number_format($cart['tax'], 2)); ?></span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between font-semibold">
                                <span>Total</span>
                                <span>₱<?php echo e(number_format($cart['total'], 2)); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?php echo e(route('cart.index')); ?>" class="text-sm text-pink-600 hover:text-pink-800">
                        &larr; Return to cart
                    </a>
                </div>
            </div>
            <div>
                <form action="<?php echo e(route('checkout.process')); ?>" method="POST" class="rounded-lg border shadow-sm p-6 space-y-4">
                    <?php echo csrf_field(); ?>
                    <h2 class="text-xl font-semibold mb-4">Delivery Information</h2>
                    
                    <div class="grid gap-2">
                        <label for="customer_name" class="text-sm font-medium">
                            Full Name
                        </label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            value="<?php echo e(old('customer_name')); ?>"
                            class="w-full rounded-md border border-input px-3 py-2 <?php $__errorArgs = ['customer_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            required
                        />
                        <?php $__errorArgs = ['customer_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    
                    <div class="grid gap-2">
                        <label for="customer_email" class="text-sm font-medium">
                            Email Address
                        </label>
                        <input
                            type="email"
                            id="customer_email"
                            name="customer_email"
                            value="<?php echo e(old('customer_email')); ?>"
                            class="w-full rounded-md border border-input px-3 py-2 <?php $__errorArgs = ['customer_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            required
                        />
                        <?php $__errorArgs = ['customer_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    
                    <div class="grid gap-2">
                        <label for="customer_phone" class="text-sm font-medium">
                            Phone Number
                        </label>
                        <input
                            type="tel"
                            id="customer_phone"
                            name="customer_phone"
                            value="<?php echo e(old('customer_phone')); ?>"
                            class="w-full rounded-md border border-input px-3 py-2 <?php $__errorArgs = ['customer_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        />
                        <?php $__errorArgs = ['customer_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    
                    <div class="grid gap-2">
                        <label for="delivery_address" class="text-sm font-medium">
                            Delivery Address
                        </label>
                        <textarea
                            id="delivery_address"
                            name="delivery_address"
                            class="w-full min-h-[80px] rounded-md border border-input px-3 py-2 <?php $__errorArgs = ['delivery_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            required
                        ><?php echo e(old('delivery_address')); ?></textarea>
                        <?php $__errorArgs = ['delivery_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                                value="<?php echo e(old('delivery_date', date('Y-m-d', strtotime('+2 days')))); ?>"
                                min="<?php echo e(date('Y-m-d')); ?>"
                                class="w-full rounded-md border border-input px-3 py-2 <?php $__errorArgs = ['delivery_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                required
                            />
                            <?php $__errorArgs = ['delivery_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        
                        <div class="grid gap-2">
                            <label for="delivery_time" class="text-sm font-medium">
                                Delivery Time
                            </label>
                            <select
                                id="delivery_time"
                                name="delivery_time"
                                class="w-full rounded-md border border-input px-3 py-2 <?php $__errorArgs = ['delivery_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                required
                            >
                                <option value="">Select a time</option>
                                <option value="9:00 AM - 12:00 PM" <?php echo e(old('delivery_time') == '9:00 AM - 12:00 PM' ? 'selected' : ''); ?>>9:00 AM - 12:00 PM</option>
                                <option value="12:00 PM - 3:00 PM" <?php echo e(old('delivery_time') == '12:00 PM - 3:00 PM' ? 'selected' : ''); ?>>12:00 PM - 3:00 PM</option>
                                <option value="3:00 PM - 6:00 PM" <?php echo e(old('delivery_time') == '3:00 PM - 6:00 PM' ? 'selected' : ''); ?>>3:00 PM - 6:00 PM</option>
                            </select>
                            <?php $__errorArgs = ['delivery_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                        ><?php echo e(old('special_instructions')); ?></textarea>
                    </div>
                    
                    <h2 class="text-xl font-semibold mt-6 mb-4">Payment Method</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="payment_method_gcash"
                                name="payment_method"
                                value="gcash"
                                <?php echo e(old('payment_method', 'gcash') == 'gcash' ? 'checked' : ''); ?>

                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_gcash" class="text-sm font-medium">
                                GCASH
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="payment_method_maya"
                                name="payment_method"
                                value="maya"
                                <?php echo e(old('payment_method') == 'maya' ? 'checked' : ''); ?>

                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_maya" class="text-sm font-medium">
                                MAYA
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input
                                type="radio"
                                id="payment_method_cash_on_delivery"
                                name="payment_method"
                                value="cash_on_delivery"
                                <?php echo e(old('payment_method') == 'cash_on_delivery' ? 'checked' : ''); ?>

                                class="h-4 w-4 border-gray-300 text-pink-600 focus:ring-pink-600"
                            />
                            <label for="payment_method_cash_on_delivery" class="text-sm font-medium">
                                Cash on Delivery
                            </label>
                        </div>
                        
                        <?php $__errorArgs = ['payment_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cake-website\resources\views/checkout/index.blade.php ENDPATH**/ ?>
```

# storage\framework\views\1c29912c36e1789f3dc876df79dd1765.php

```php
<svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    <?php echo e($attributes); ?>

>
    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
</svg>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/icons/computer-desktop.blade.php ENDPATH**/ ?>
```

# storage\framework\views\2f6eb7980101b666dd025f50f90e3245.php

```php
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'Sweet Delights')); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased">
    <div class="flex flex-col min-h-screen">
                
        <header
            x-data="{ isMobileMenuOpen: false }"
            @toggle-mobile-menu.window="isMobileMenuOpen = !isMobileMenuOpen"
            class="sticky top-0 z-30 bg-white border-b" 
        >
            <div class="container flex items-center justify-between h-16 px-4 md:px-6 relative"> 
                <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2 text-xl font-bold text-pink-600">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M8.37 12.37a2.5 2.5 0 0 0-3.326.602c-.47.692-.348 1.635.252 2.236l1.544 1.544c.603.603 1.55.726 2.24.254a2.5 2.5 0 0 0 .601-3.328"></path><path d="m14.5 12.5 2.25 2.25"></path><path d="m11.5 9.5 2.25 2.25"></path><path d="M8.5 6.5 13 11"></path><path d="M20 14c0 4.418-4.477 8-10 8-3.41 0-6.42-1.33-8-3.5"></path><path d="M5.5 6.5 8 9"></path><path d="M3 3v4"></path><path d="M7 3H3"></path><path d="M14 10V4a2 2 0 0 0-2-2H8"></path><path d="M4 15H2"></path></svg>
                    <span>Sweet Delights</span>
                </a>

                
                <nav class="hidden md:flex items-center gap-6">
                    <a href="<?php echo e(route('home')); ?>" class="text-sm font-medium hover:underline underline-offset-4">Home</a>
                    <a href="#cakes" class="text-sm font-medium hover:underline underline-offset-4">Our Cakes</a>
                    <a href="#about" class="text-sm font-medium hover:underline underline-offset-4">About Us</a>
                    <a href="#testimonials" class="text-sm font-medium hover:underline underline-offset-4">Testimonials</a>
                    <a href="#contact" class="text-sm font-medium hover:underline underline-offset-4">Contact</a>
                </nav>

                
                <a href="<?php echo e(route('order')); ?>" class="hidden md:inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                    Order Now
                </a>

                
                <button
                    @click="$dispatch('toggle-mobile-menu')"
                    class="md:hidden inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 w-10"
                    aria-controls="mobile-menu"
                    :aria-expanded="isMobileMenuOpen.toString()"
                    aria-label="Toggle navigation menu"
                >
                    
                    <svg x-show="!isMobileMenuOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <line x1="4" x2="20" y1="12" y2="12" />
                        <line x1="4" x2="20" y1="6" y2="6" />
                        <line x1="4" x2="20" y1="18" y2="18" />
                    </svg>
                    
                    <svg x-show="isMobileMenuOpen" x-cloak xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                         <line x1="18" y1="6" x2="6" y2="18" />
                         <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>

                
                <nav
                    id="mobile-menu"
                    x-show="isMobileMenuOpen"
                    x-cloak
                    @click.outside="isMobileMenuOpen = false" 
                    class="md:hidden absolute top-full left-0 right-0 z-20 bg-white border-b border-t p-4 space-y-2"
                    aria-label="Mobile navigation"
                    >
                    <a href="<?php echo e(route('home')); ?>" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Home</a>
                    <a href="#cakes" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Our Cakes</a>
                    <a href="#about" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">About Us</a>
                    <a href="#testimonials" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Testimonials</a>
                    <a href="#contact" @click="isMobileMenuOpen = false" class="block text-sm font-medium hover:underline underline-offset-4 py-1">Contact</a>
                    <a href="<?php echo e(route('order')); ?>" @click="isMobileMenuOpen = false" class="mt-2 block w-full text-center rounded-md text-sm font-medium bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 flex items-center justify-center">
                        Order Now
                    </a>
                </nav>
            </div> 
        </header>
        
        <main class="flex-1">
            <?php echo $__env->yieldContent('content'); ?>
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
                    <a href="https://www.facebook.com/AKennesu02" target="_blank" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input hover:bg-accent hover:text-accent-foreground h-10 w-10" aria-label="Facebook">
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
                © <?php echo e(date('Y')); ?> Sweet Delights. All rights reserved.
            </div>
        </footer>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\cake-website\resources\views/layouts/app.blade.php ENDPATH**/ ?>
```

# storage\framework\views\6e272e485d067aa7f6c15e0b3424f118.php

```php
<?php $__env->startSection('title', __('Not Found')); ?>
<?php $__env->startSection('code', '404'); ?>
<?php $__env->startSection('message', __('Not Found')); ?>

<?php echo $__env->make('errors::minimal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Exceptions/views/404.blade.php ENDPATH**/ ?>
```

# storage\framework\views\6e073533ba10b70c2dde23e33fee0157.php

```php
<script>

    (function () {
        const darkStyles = document.querySelector('style[data-theme="dark"]')?.textContent
        const lightStyles = document.querySelector('style[data-theme="light"]')?.textContent

        const removeStyles = () => {
            document.querySelector('style[data-theme="dark"]')?.remove()
            document.querySelector('style[data-theme="light"]')?.remove()
        }

        removeStyles()

        setDarkClass = () => {
            removeStyles()

            const isDark = localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)

            isDark ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')

            if (isDark) {
                document.head.insertAdjacentHTML('beforeend', `<style data-theme="dark">${darkStyles}</style>`)
            } else {
                document.head.insertAdjacentHTML('beforeend', `<style data-theme="light">${lightStyles}</style>`)
            }
        }

        setDarkClass()

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', setDarkClass)
    })();
</script>

<div
    class="relative"
    x-data="{
        menu: false,
        theme: localStorage.theme,
        darkMode() {
            this.theme = 'dark'
            localStorage.theme = 'dark'
            setDarkClass()
        },
        lightMode() {
            this.theme = 'light'
            localStorage.theme = 'light'
            setDarkClass()
        },
        systemMode() {
            this.theme = undefined
            localStorage.removeItem('theme')
            setDarkClass()
        },
    }"
    @click.outside="menu = false"
>
    <button
        x-cloak
        class="block rounded p-1 hover:bg-gray-100 dark:hover:bg-gray-800"
        :class="theme ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-600 hover:text-gray-500 focus:text-gray-500 dark:hover:text-gray-500 dark:focus:text-gray-500'"
        @click="menu = ! menu"
    >
        <?php if (isset($component)) { $__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.sun','data' => ['class' => 'block h-5 w-5 dark:hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.sun'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-5 w-5 dark:hidden']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7)): ?>
<?php $attributes = $__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7; ?>
<?php unset($__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7)): ?>
<?php $component = $__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7; ?>
<?php unset($__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.moon','data' => ['class' => 'hidden h-5 w-5 dark:block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.moon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'hidden h-5 w-5 dark:block']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745)): ?>
<?php $attributes = $__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745; ?>
<?php unset($__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745)): ?>
<?php $component = $__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745; ?>
<?php unset($__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745); ?>
<?php endif; ?>
    </button>

    <div
        x-show="menu"
        class="absolute right-0 z-10 flex origin-top-right flex-col rounded-md bg-white shadow-xl ring-1 ring-gray-900/5 dark:bg-gray-800"
        style="display: none"
        @click="menu = false"
    >
        <button
            class="flex items-center gap-3 px-4 py-2 hover:rounded-t-md hover:bg-gray-100 dark:hover:bg-gray-700"
            :class="theme === 'light' ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'"
            @click="lightMode()"
        >
            <?php if (isset($component)) { $__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.sun','data' => ['class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.sun'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7)): ?>
<?php $attributes = $__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7; ?>
<?php unset($__attributesOriginalbfde029a2e31d1ec96b5017ff81a67a7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7)): ?>
<?php $component = $__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7; ?>
<?php unset($__componentOriginalbfde029a2e31d1ec96b5017ff81a67a7); ?>
<?php endif; ?>
            Light
        </button>
        <button
            class="flex items-center gap-3 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
            :class="theme === 'dark' ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'"
            @click="darkMode()"
        >
            <?php if (isset($component)) { $__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.moon','data' => ['class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.moon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745)): ?>
<?php $attributes = $__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745; ?>
<?php unset($__attributesOriginal6dda8ad3ea7f20f6c0a87e7037386745); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745)): ?>
<?php $component = $__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745; ?>
<?php unset($__componentOriginal6dda8ad3ea7f20f6c0a87e7037386745); ?>
<?php endif; ?>
            Dark
        </button>
        <button
            class="flex items-center gap-3 px-4 py-2 hover:rounded-b-md hover:bg-gray-100 dark:hover:bg-gray-700"
            :class="theme === undefined ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'"
            @click="systemMode()"
        >
            <?php if (isset($component)) { $__componentOriginala52e607cb40b8eec566206ff9f3ca13c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala52e607cb40b8eec566206ff9f3ca13c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.computer-desktop','data' => ['class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.computer-desktop'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala52e607cb40b8eec566206ff9f3ca13c)): ?>
<?php $attributes = $__attributesOriginala52e607cb40b8eec566206ff9f3ca13c; ?>
<?php unset($__attributesOriginala52e607cb40b8eec566206ff9f3ca13c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala52e607cb40b8eec566206ff9f3ca13c)): ?>
<?php $component = $__componentOriginala52e607cb40b8eec566206ff9f3ca13c; ?>
<?php unset($__componentOriginala52e607cb40b8eec566206ff9f3ca13c); ?>
<?php endif; ?>
            System
        </button>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/theme-switcher.blade.php ENDPATH**/ ?>
```

# storage\framework\views\7a800864caf4e7568158a47b88a79fbb.php

```php
<?php $__env->startSection('content'); ?>
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
            <?php $__currentLoopData = $cakes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cake): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
                <div class="relative">
                    <img
                        src="<?php echo e(asset('images/cakes/' . $cake->image)); ?>"
                        alt="<?php echo e($cake->name); ?>"
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
                            <h3 class="font-semibold text-lg"><?php echo e($cake->name); ?></h3>
                            <p class="text-sm text-gray-600 mt-1"><?php echo e($cake->description); ?></p>
                        </div>
                        <div class="text-pink-600 font-bold"><?php echo e($cake->formatted_price); ?></div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="<?php echo e(route('cakes.show', $cake)); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 flex-1">
                            View Details
                        </a>
                        <form action="<?php echo e(route('cart.add', $cake)); ?>" method="POST" class="flex-1">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full">
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cake-website\resources\views/cakes/index.blade.php ENDPATH**/ ?>
```

# storage\framework\views\7c8d871038cf969f9afbb0f9db9307c9.php

```php
<div class="hidden overflow-x-auto sm:col-span-1 lg:block">
    <div
        class="h-[35.5rem] scrollbar-hidden trace text-sm text-gray-400 dark:text-gray-300"
    >
        <div class="mb-2 inline-block rounded-full bg-red-500/20 px-3 py-2 dark:bg-red-500/20 sm:col-span-1">
            <button
                @click="includeVendorFrames = !includeVendorFrames"
                class="inline-flex items-center font-bold leading-5 text-red-500"
            >
                <span x-show="includeVendorFrames">Collapse</span>
                <span
                    x-cloak
                    x-show="!includeVendorFrames"
                    >Expand</span
                >
                <span class="ml-1">vendor frames</span>

                <div class="flex flex-col ml-1 -mt-2" x-cloak x-show="includeVendorFrames">
                    <?php if (isset($component)) { $__componentOriginal707ceba27255eae48fdb0f3529710ddf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal707ceba27255eae48fdb0f3529710ddf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.chevron-down','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.chevron-down'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal707ceba27255eae48fdb0f3529710ddf)): ?>
<?php $attributes = $__attributesOriginal707ceba27255eae48fdb0f3529710ddf; ?>
<?php unset($__attributesOriginal707ceba27255eae48fdb0f3529710ddf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal707ceba27255eae48fdb0f3529710ddf)): ?>
<?php $component = $__componentOriginal707ceba27255eae48fdb0f3529710ddf; ?>
<?php unset($__componentOriginal707ceba27255eae48fdb0f3529710ddf); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal14b1cc5db95fcca4a0f06445821cff39 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal14b1cc5db95fcca4a0f06445821cff39 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.chevron-up','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.chevron-up'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal14b1cc5db95fcca4a0f06445821cff39)): ?>
<?php $attributes = $__attributesOriginal14b1cc5db95fcca4a0f06445821cff39; ?>
<?php unset($__attributesOriginal14b1cc5db95fcca4a0f06445821cff39); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal14b1cc5db95fcca4a0f06445821cff39)): ?>
<?php $component = $__componentOriginal14b1cc5db95fcca4a0f06445821cff39; ?>
<?php unset($__componentOriginal14b1cc5db95fcca4a0f06445821cff39); ?>
<?php endif; ?>
                </div>

                <div class="flex flex-col ml-1 -mt-2" x-cloak x-show="! includeVendorFrames">
                    <?php if (isset($component)) { $__componentOriginal14b1cc5db95fcca4a0f06445821cff39 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal14b1cc5db95fcca4a0f06445821cff39 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.chevron-up','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.chevron-up'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal14b1cc5db95fcca4a0f06445821cff39)): ?>
<?php $attributes = $__attributesOriginal14b1cc5db95fcca4a0f06445821cff39; ?>
<?php unset($__attributesOriginal14b1cc5db95fcca4a0f06445821cff39); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal14b1cc5db95fcca4a0f06445821cff39)): ?>
<?php $component = $__componentOriginal14b1cc5db95fcca4a0f06445821cff39; ?>
<?php unset($__componentOriginal14b1cc5db95fcca4a0f06445821cff39); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal707ceba27255eae48fdb0f3529710ddf = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal707ceba27255eae48fdb0f3529710ddf = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.icons.chevron-down','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::icons.chevron-down'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal707ceba27255eae48fdb0f3529710ddf)): ?>
<?php $attributes = $__attributesOriginal707ceba27255eae48fdb0f3529710ddf; ?>
<?php unset($__attributesOriginal707ceba27255eae48fdb0f3529710ddf); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal707ceba27255eae48fdb0f3529710ddf)): ?>
<?php $component = $__componentOriginal707ceba27255eae48fdb0f3529710ddf; ?>
<?php unset($__componentOriginal707ceba27255eae48fdb0f3529710ddf); ?>
<?php endif; ?>
                </div>
            </button>
        </div>

        <div class="mb-12 space-y-2">
            <?php $__currentLoopData = $exception->frames(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $frame): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(! $frame->isFromVendor()): ?>
                    <?php
                        $vendorFramesCollapsed = $exception->frames()->take($loop->index)->reverse()->takeUntil(fn ($frame) => ! $frame->isFromVendor());
                    ?>

                    <div x-show="! includeVendorFrames">
                        <?php if($vendorFramesCollapsed->isNotEmpty()): ?>
                            <div class="text-gray-500">
                                <?php echo e($vendorFramesCollapsed->count()); ?> vendor frame<?php echo e($vendorFramesCollapsed->count() > 1 ? 's' : ''); ?> collapsed
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <button
                    class="w-full text-left dark:border-gray-900"
                    x-show="<?php echo e($frame->isFromVendor() ? 'includeVendorFrames' : 'true'); ?>"
                    @click="index = <?php echo e($loop->index); ?>"
                >
                    <div
                        x-bind:class="
                            index === <?php echo e($loop->index); ?>

                                ? 'rounded-r-md bg-gray-100 dark:bg-gray-800 border-l dark:border dark:border-gray-700 border-l-red-500 dark:border-l-red-500'
                                : 'hover:bg-gray-100/75 dark:hover:bg-gray-800/75'
                        "
                    >
                        <div class="scrollbar-hidden overflow-x-auto border-l-2 border-transparent p-2">
                            <div class="nowrap text-gray-900 dark:text-gray-300">
                                <span class="inline-flex items-baseline">
                                    <span class="text-gray-900 dark:text-gray-300"><?php echo e($frame->source()); ?></span>
                                    <span class="font-mono text-xs">:<?php echo e($frame->line()); ?></span>
                                </span>
                            </div>
                            <div class="text-gray-500 dark:text-gray-400">
                                <?php echo e($exception->frames()->get($loop->index + 1)?->callable()); ?>

                            </div>
                        </div>
                    </div>
                </button>

                <?php if(! $frame->isFromVendor() && $exception->frames()->slice($loop->index + 1)->reject(fn ($frame) => $frame->isFromVendor())->isEmpty()): ?>
                    <?php if($exception->frames()->slice($loop->index + 1)->count()): ?>
                        <div x-show="! includeVendorFrames">
                            <div class="text-gray-500">
                                <?php echo e($exception->frames()->slice($loop->index + 1)->count()); ?> vendor
                                frame<?php echo e($exception->frames()->slice($loop->index + 1)->count() > 1 ? 's' : ''); ?> collapsed
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/trace.blade.php ENDPATH**/ ?>
```

# storage\framework\views\9eb7543b6ff770d40690d41ece16a4a9.php

```php
<section
    <?php echo e($attributes->merge(['class' => "@container flex flex-col p-6 sm:p-12 bg-white dark:bg-gray-900/80 text-gray-900 dark:text-gray-100 rounded-lg default:col-span-full default:lg:col-span-6 default:row-span-1 dark:ring-1 dark:ring-gray-800 shadow-xl"])); ?>

>
    <?php echo e($slot); ?>

</section>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/card.blade.php ENDPATH**/ ?>
```

# storage\framework\views\045d6871c99b9d53e6ba6621eba812af.php

```php
<?php $__currentLoopData = $exception->frames(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $frame): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div
        class="sm:col-span-2"
        x-show="index === <?php echo e($loop->index); ?>"
    >
        <div class="mb-3">
            <div class="text-md text-gray-500 dark:text-gray-400">
                <div class="mb-2">

                    <?php if(config('app.editor')): ?>
                        <a href="<?php echo e($frame->editorHref()); ?>" class="text-blue-500 hover:underline">
                            <span class="wrap text-gray-900 dark:text-gray-300"><?php echo e($frame->file()); ?></span>
                        </a>
                    <?php else: ?>
                        <span class="wrap text-gray-900 dark:text-gray-300"><?php echo e($frame->file()); ?></span>
                    <?php endif; ?>

                    <span class="font-mono text-xs">:<?php echo e($frame->line()); ?></span>
                </div>
            </div>
        </div>
        <div class="pt-4 text-sm text-gray-500 dark:text-gray-400">
            <pre class="h-[32.5rem] rounded-md dark:bg-gray-800 border dark:border-gray-700"><template x-if="true"><code
                    style="display: none;"
                    id="frame-<?php echo e($loop->index); ?>"
                    class="language-php highlightable-code <?php if($loop->index === $exception->defaultFrame()): ?> default-highlightable-code <?php endif; ?> scrollbar-hidden overflow-y-hidden"
                    data-line-number="<?php echo e($frame->line()); ?>"
                    data-ln-start-from="<?php echo e(max($frame->line() - 5, 1)); ?>"
                ><?php echo e($frame->snippet()); ?></code></template></pre>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/editor.blade.php ENDPATH**/ ?>
```

# storage\framework\views\388ce3f0fb0a1019e347f8a677caab99.php

```php
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" style="margin-bottom: -8px;">
    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
</svg>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/icons/chevron-down.blade.php ENDPATH**/ ?>
```

# storage\framework\views\3716c60fa47b37786b59e244b9ca6b80.php

```php
<?php use \Illuminate\Support\Str; ?>
<?php if (isset($component)) { $__componentOriginal74daf2d0a9c625ad90327a6043d15980 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74daf2d0a9c625ad90327a6043d15980 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.card','data' => ['class' => 'mt-6 overflow-x-auto']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-6 overflow-x-auto']); ?>
    <div>
        <span class="text-xl font-bold lg:text-2xl">Request</span>
    </div>

    <div class="mt-2">
        <span><?php echo e($exception->request()->method()); ?></span>
        <span class="text-gray-500"><?php echo e(Str::start($exception->request()->path(), '/')); ?></span>
    </div>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white">Headers</span>
    </div>

    <dl class="mt-1 grid grid-cols-1 rounded border dark:border-gray-800">
        <?php $__empty_1 = true; $__currentLoopData = $exception->requestHeaders(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center gap-2 <?php echo e($loop->first ? '' : 'border-t'); ?> dark:border-gray-800">
                <span
                    data-tippy-content="<?php echo e($key); ?>"
                    class="lg:text-md w-[8rem] flex-none cursor-pointer truncate border-r px-5 py-3 text-sm dark:border-gray-800 lg:w-[12rem]"
                >
                    <?php echo e($key); ?>

                </span>
                <span
                    class="min-w-0 flex-grow"
                    style="
                        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem));
                    "
                >
                    <pre class="scrollbar-hidden overflow-y-hidden text-xs lg:text-sm"><code class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x"><?php echo e($value); ?></code></pre>
                </span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">No headers data</code></pre>
            </span>
        <?php endif; ?>
    </dl>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white">Body</span>
    </div>

    <div class="mt-1 rounded border dark:border-gray-800">
        <div class="flex items-center">
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x"><?php echo e($exception->requestBody() ?: 'No body data'); ?></code></pre>
            </span>
        </div>
    </div>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $attributes = $__attributesOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $component = $__componentOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__componentOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginal74daf2d0a9c625ad90327a6043d15980 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74daf2d0a9c625ad90327a6043d15980 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.card','data' => ['class' => 'mt-6 overflow-x-auto']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-6 overflow-x-auto']); ?>
    <div>
        <span class="text-xl font-bold lg:text-2xl">Application</span>
    </div>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white"> Routing </span>
    </div>

    <dl class="mt-1 grid grid-cols-1 rounded border dark:border-gray-800">
        <?php $__empty_1 = true; $__currentLoopData = $exception->applicationRouteContext(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center gap-2 <?php echo e($loop->first ? '' : 'border-t'); ?> dark:border-gray-800">
                <span
                    data-tippy-content="<?php echo e($name); ?>"
                    class="lg:text-md w-[8rem] flex-none cursor-pointer truncate border-r px-5 py-3 text-sm dark:border-gray-800 lg:w-[12rem]"
                    ><?php echo e($name); ?></span
                >
                <span
                    class="min-w-0 flex-grow"
                    style="
                        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem));
                    "
                >
                    <pre class="scrollbar-hidden overflow-y-hidden text-xs lg:text-sm"><code class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x"><?php echo e($value); ?></code></pre>
                </span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">No routing data</code></pre>
            </span>
        <?php endif; ?>
    </dl>

    <?php if($routeParametersContext = $exception->applicationRouteParametersContext()): ?>
        <div class="mt-4">
            <span class="text-gray-900 dark:text-white text-sm"> Routing Parameters </span>
        </div>

        <div class="mt-1 rounded border dark:border-gray-800">
            <div class="flex items-center">
                <span
                    class="min-w-0 flex-grow"
                    style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
                >
                    <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x"><?php echo e($routeParametersContext); ?></code></pre>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <span class="font-semibold text-gray-900 dark:text-white"> Database Queries </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            <?php if(count($exception->applicationQueries()) === 100): ?>
                only the first 100 queries are displayed
            <?php endif; ?>
        </span>
    </div>

    <dl class="mt-1 grid grid-cols-1 rounded border dark:border-gray-800">
        <?php $__empty_1 = true; $__currentLoopData = $exception->applicationQueries(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as ['connectionName' => $connectionName, 'sql' => $sql, 'time' => $time]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex items-center gap-2 <?php echo e($loop->first ? '' : 'border-t'); ?> dark:border-gray-800">
                <div class="lg:text-md w-[8rem] flex-none truncate border-r px-5 py-3 text-sm dark:border-gray-800 lg:w-[12rem]">
                    <span><?php echo e($connectionName); ?></span>
                    <span class="hidden text-xs text-gray-500 lg:inline-block">(<?php echo e($time); ?> ms)</span>
                </div>
                <span
                    class="min-w-0 flex-grow"
                    style="
                        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem));
                    "
                >
                    <pre class="scrollbar-hidden overflow-y-hidden text-xs lg:text-sm"><code class="px-5 py-3 overflow-y-hidden scrollbar-hidden max-h-32 overflow-x-scroll scrollbar-hidden-x"><?php echo e($sql); ?></code></pre>
                </span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span
                class="min-w-0 flex-grow"
                style="-webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 3rem), transparent calc(100% - 1rem))"
            >
                <pre class="scrollbar-hidden mx-5 my-3 overflow-y-hidden text-xs lg:text-sm"><code class="overflow-y-hidden scrollbar-hidden overflow-x-scroll scrollbar-hidden-x">No query data</code></pre>
            </span>
        <?php endif; ?>
    </dl>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $attributes = $__attributesOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $component = $__componentOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__componentOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/context.blade.php ENDPATH**/ ?>
```

# storage\framework\views\19439e644be57b0a0bce7b8fbc3eb3d7.php

```php
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?php echo $__env->yieldContent('title'); ?></title>

        <style>
            /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}code{font-family:monospace,monospace;font-size:1em}[hidden]{display:none}html{font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}*,:after,:before{box-sizing:border-box;border:0 solid #e2e8f0}a{color:inherit;text-decoration:inherit}code{font-family:Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace}svg,video{display:block;vertical-align:middle}video{max-width:100%;height:auto}.bg-white{--bg-opacity:1;background-color:#fff;background-color:rgba(255,255,255,var(--bg-opacity))}.bg-gray-100{--bg-opacity:1;background-color:#f7fafc;background-color:rgba(247,250,252,var(--bg-opacity))}.border-gray-200{--border-opacity:1;border-color:#edf2f7;border-color:rgba(237,242,247,var(--border-opacity))}.border-gray-400{--border-opacity:1;border-color:#cbd5e0;border-color:rgba(203,213,224,var(--border-opacity))}.border-t{border-top-width:1px}.border-r{border-right-width:1px}.flex{display:flex}.grid{display:grid}.hidden{display:none}.items-center{align-items:center}.justify-center{justify-content:center}.font-semibold{font-weight:600}.h-5{height:1.25rem}.h-8{height:2rem}.h-16{height:4rem}.text-sm{font-size:.875rem}.text-lg{font-size:1.125rem}.leading-7{line-height:1.75rem}.mx-auto{margin-left:auto;margin-right:auto}.ml-1{margin-left:.25rem}.mt-2{margin-top:.5rem}.mr-2{margin-right:.5rem}.ml-2{margin-left:.5rem}.mt-4{margin-top:1rem}.ml-4{margin-left:1rem}.mt-8{margin-top:2rem}.ml-12{margin-left:3rem}.-mt-px{margin-top:-1px}.max-w-xl{max-width:36rem}.max-w-6xl{max-width:72rem}.min-h-screen{min-height:100vh}.overflow-hidden{overflow:hidden}.p-6{padding:1.5rem}.py-4{padding-top:1rem;padding-bottom:1rem}.px-4{padding-left:1rem;padding-right:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.pt-8{padding-top:2rem}.fixed{position:fixed}.relative{position:relative}.top-0{top:0}.right-0{right:0}.shadow{box-shadow:0 1px 3px 0 rgba(0,0,0,.1),0 1px 2px 0 rgba(0,0,0,.06)}.text-center{text-align:center}.text-gray-200{--text-opacity:1;color:#edf2f7;color:rgba(237,242,247,var(--text-opacity))}.text-gray-300{--text-opacity:1;color:#e2e8f0;color:rgba(226,232,240,var(--text-opacity))}.text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}.text-gray-500{--text-opacity:1;color:#a0aec0;color:rgba(160,174,192,var(--text-opacity))}.text-gray-600{--text-opacity:1;color:#718096;color:rgba(113,128,150,var(--text-opacity))}.text-gray-700{--text-opacity:1;color:#4a5568;color:rgba(74,85,104,var(--text-opacity))}.text-gray-900{--text-opacity:1;color:#1a202c;color:rgba(26,32,44,var(--text-opacity))}.uppercase{text-transform:uppercase}.underline{text-decoration:underline}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.tracking-wider{letter-spacing:.05em}.w-5{width:1.25rem}.w-8{width:2rem}.w-auto{width:auto}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}@-webkit-keyframes spin{0%{transform:rotate(0deg)}to{transform:rotate(1turn)}}@keyframes spin{0%{transform:rotate(0deg)}to{transform:rotate(1turn)}}@-webkit-keyframes ping{0%{transform:scale(1);opacity:1}75%,to{transform:scale(2);opacity:0}}@keyframes ping{0%{transform:scale(1);opacity:1}75%,to{transform:scale(2);opacity:0}}@-webkit-keyframes pulse{0%,to{opacity:1}50%{opacity:.5}}@keyframes pulse{0%,to{opacity:1}50%{opacity:.5}}@-webkit-keyframes bounce{0%,to{transform:translateY(-25%);-webkit-animation-timing-function:cubic-bezier(.8,0,1,1);animation-timing-function:cubic-bezier(.8,0,1,1)}50%{transform:translateY(0);-webkit-animation-timing-function:cubic-bezier(0,0,.2,1);animation-timing-function:cubic-bezier(0,0,.2,1)}}@keyframes bounce{0%,to{transform:translateY(-25%);-webkit-animation-timing-function:cubic-bezier(.8,0,1,1);animation-timing-function:cubic-bezier(.8,0,1,1)}50%{transform:translateY(0);-webkit-animation-timing-function:cubic-bezier(0,0,.2,1);animation-timing-function:cubic-bezier(0,0,.2,1)}}@media (min-width:640px){.sm\:rounded-lg{border-radius:.5rem}.sm\:block{display:block}.sm\:items-center{align-items:center}.sm\:justify-start{justify-content:flex-start}.sm\:justify-between{justify-content:space-between}.sm\:h-20{height:5rem}.sm\:ml-0{margin-left:0}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:pt-0{padding-top:0}.sm\:text-left{text-align:left}.sm\:text-right{text-align:right}}@media (min-width:768px){.md\:border-t-0{border-top-width:0}.md\:border-l{border-left-width:1px}.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}@media (prefers-color-scheme:dark){.dark\:bg-gray-800{--bg-opacity:1;background-color:#2d3748;background-color:rgba(45,55,72,var(--bg-opacity))}.dark\:bg-gray-900{--bg-opacity:1;background-color:#1a202c;background-color:rgba(26,32,44,var(--bg-opacity))}.dark\:border-gray-700{--border-opacity:1;border-color:#4a5568;border-color:rgba(74,85,104,var(--border-opacity))}.dark\:text-white{--text-opacity:1;color:#fff;color:rgba(255,255,255,var(--text-opacity))}.dark\:text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}}
        </style>

        <style>
            body {
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                    <div class="px-4 text-lg text-gray-500 border-r border-gray-400 tracking-wider">
                        <?php echo $__env->yieldContent('code'); ?>
                    </div>

                    <div class="ml-4 text-lg text-gray-500 uppercase tracking-wider">
                        <?php echo $__env->yieldContent('message'); ?>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Exceptions/views/minimal.blade.php ENDPATH**/ ?>
```

# storage\framework\views\21190a330b4eb7c9119fef690b3a8096.php

```php
<?php $__env->startSection('content'); ?>
<section class="w-full py-12 md:py-24 lg:py-32">
    <div class="container px-4 md:px-6">
        <div class="grid gap-6 lg:grid-cols-2 lg:gap-12 items-start">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-xl">
                    <img
                        src="<?php echo e(asset('images/cakes/' . $cake->image)); ?>"
                        alt="<?php echo e($cake->name); ?>"
                        class="aspect-square object-cover w-full"
                    />
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="<?php echo e(asset('images/cakes/' . $cake->image)); ?>"
                            alt="<?php echo e($cake->name); ?> - Thumbnail 1"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="<?php echo e(asset('images/cakes/' . $cake->image)); ?>"
                            alt="<?php echo e($cake->name); ?> - Thumbnail 2"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="<?php echo e(asset('images/cakes/' . $cake->image)); ?>"
                            alt="<?php echo e($cake->name); ?> - Thumbnail 3"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                    <div class="overflow-hidden rounded-lg">
                        <img
                            src="<?php echo e(asset('images/cakes/' . $cake->image)); ?>"
                            alt="<?php echo e($cake->name); ?> - Thumbnail 4"
                            class="aspect-square object-cover w-full cursor-pointer hover:opacity-80 transition-opacity"
                        />
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                        <?php echo e($cake->name); ?>

                    </h1>
                    <p class="text-2xl font-bold text-pink-600">
                        <?php echo e($cake->formatted_price); ?>

                    </p>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="flex items-center">
                            <?php for($i = 0; $i < 5; $i++): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 fill-yellow-400 text-yellow-400">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                            <?php endfor; ?>
                        </div>
                        <span class="ml-2 text-sm text-gray-600">(25 reviews)</span>
                    </div>
                    <p class="text-gray-600">
                        <?php echo e($cake->description); ?>

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
                <form action="<?php echo e(route('cart.add', $cake)); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cake-website\resources\views/cakes/show.blade.php ENDPATH**/ ?>
```

# storage\framework\views\23483e24bdee5cf65578b223b636fc11.php

```php
<?php $__env->startSection('content'); ?>
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
        
        <?php if(session('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
            <span class="block sm:inline"><?php echo e(session('success')); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if(session('error')): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
            <span class="block sm:inline"><?php echo e(session('error')); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if(count($cart['items']) > 0): ?>
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
                            <?php $__currentLoopData = $cart['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-b">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-4">
                                        <img
                                            src="<?php echo e(asset('images/cakes/' . $item['image'])); ?>"
                                            alt="<?php echo e($item['name']); ?>"
                                            class="aspect-square rounded-md object-cover h-16 w-16"
                                        />
                                        <div>
                                            <h3 class="font-medium"><?php echo e($item['name']); ?></h3>
                                            <?php if($item['customization']): ?>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo e($item['customization']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm">₱<?php echo e(number_format($item['price'], 2)); ?></td>
                                <td class="px-4 py-4">
                                    <form action="<?php echo e(route('cart.update', $index)); ?>" method="POST" class="flex items-center">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <div class="flex items-center">
                                            <button type="button" onclick="decrementQuantity<?php echo e($index); ?>()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                                    <path d="M5 12h14" />
                                                </svg>
                                                <span class="sr-only">Decrease quantity</span>
                                            </button>
                                            <input
                                                type="number"
                                                id="quantity<?php echo e($index); ?>"
                                                name="quantity"
                                                min="0"
                                                value="<?php echo e($item['quantity']); ?>"
                                                class="w-12 text-center border-y h-8 px-2 py-1"
                                            />
                                            <button type="button" onclick="incrementQuantity<?php echo e($index); ?>()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
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
                                        function incrementQuantity<?php echo e($index); ?>() {
                                            const input = document.getElementById('quantity<?php echo e($index); ?>');
                                            input.value = parseInt(input.value) + 1;
                                        }
                                        
                                        function decrementQuantity<?php echo e($index); ?>() {
                                            const input = document.getElementById('quantity<?php echo e($index); ?>');
                                            if (parseInt(input.value) > 0) {
                                                input.value = parseInt(input.value) - 1;
                                            }
                                        }
                                    </script>
                                </td>
                                <td class="px-4 py-4 text-sm">₱<?php echo e(number_format($item['subtotal'], 2)); ?></td>
                                <td class="px-4 py-4 text-right">
                                    <form action="<?php echo e(route('cart.remove', $index)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <form action="<?php echo e(route('cart.clear')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
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
                            <span>₱<?php echo e(number_format($cart['subtotal'], 2)); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tax (8%)</span>
                            <span>₱<?php echo e(number_format($cart['tax'], 2)); ?></span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between font-semibold">
                                <span>Total</span>
                                <span>₱<?php echo e(number_format($cart['total'], 2)); ?></span>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo e(route('checkout.index')); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full mt-4">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="mt-8 text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-12 w-12 mx-auto text-gray-400">
                <circle cx="8" cy="21" r="1" />
                <circle cx="19" cy="21" r="1" />
                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
            </svg>
            <h2 class="mt-4 text-xl font-semibold">Your cart is empty</h2>
            <p class="mt-2 text-gray-600">Looks like you haven't added any cakes to your cart yet.</p>
            <a href="<?php echo e(route('cakes.index')); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 mt-4">
                Browse Cakes
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cake-website\resources\views/cart/index.blade.php ENDPATH**/ ?>
```

# storage\framework\views\72325343412f545d0db632fde8e411fe.php

```php
<?php if (isset($component)) { $__componentOriginal74daf2d0a9c625ad90327a6043d15980 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74daf2d0a9c625ad90327a6043d15980 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.card','data' => ['class' => 'mt-6 overflow-x-auto']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-6 overflow-x-auto']); ?>
    <div
        x-data="{
            includeVendorFrames: false,
            index: <?php echo e($exception->defaultFrame()); ?>,
        }"
    >
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-clock>
            <?php if (isset($component)) { $__componentOriginal92c1a431b4816bac5d5a20d0fc1238ab = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal92c1a431b4816bac5d5a20d0fc1238ab = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.trace','data' => ['exception' => $exception]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::trace'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['exception' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($exception)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal92c1a431b4816bac5d5a20d0fc1238ab)): ?>
<?php $attributes = $__attributesOriginal92c1a431b4816bac5d5a20d0fc1238ab; ?>
<?php unset($__attributesOriginal92c1a431b4816bac5d5a20d0fc1238ab); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal92c1a431b4816bac5d5a20d0fc1238ab)): ?>
<?php $component = $__componentOriginal92c1a431b4816bac5d5a20d0fc1238ab; ?>
<?php unset($__componentOriginal92c1a431b4816bac5d5a20d0fc1238ab); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginala2de13eefed6710e7b4064d57c6d0e47 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala2de13eefed6710e7b4064d57c6d0e47 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.editor','data' => ['exception' => $exception]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::editor'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['exception' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($exception)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala2de13eefed6710e7b4064d57c6d0e47)): ?>
<?php $attributes = $__attributesOriginala2de13eefed6710e7b4064d57c6d0e47; ?>
<?php unset($__attributesOriginala2de13eefed6710e7b4064d57c6d0e47); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala2de13eefed6710e7b4064d57c6d0e47)): ?>
<?php $component = $__componentOriginala2de13eefed6710e7b4064d57c6d0e47; ?>
<?php unset($__componentOriginala2de13eefed6710e7b4064d57c6d0e47); ?>
<?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $attributes = $__attributesOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $component = $__componentOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__componentOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/trace-and-editor.blade.php ENDPATH**/ ?>
```

# storage\framework\views\a5f7bc5d8aadb1dbee4651d98f381444.php

```php
<header class="mt-3 px-5 sm:mt-10">
    <div class="py-3 dark:border-gray-900 sm:py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="rounded-full bg-red-500/20 p-4 dark:bg-red-500/20">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-6 w-6 fill-red-500 text-gray-50 dark:text-gray-950"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>

                <span class="text-dark ml-3 text-2xl font-bold dark:text-white sm:text-3xl">
                    <?php echo e($exception->title()); ?>

                </span>
            </div>

            <div class="flex items-center gap-3 sm:gap-6">
                <?php if (isset($component)) { $__componentOriginal9b6ddd2809dd60ece07dfaf1f3ef876f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9b6ddd2809dd60ece07dfaf1f3ef876f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.theme-switcher','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::theme-switcher'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9b6ddd2809dd60ece07dfaf1f3ef876f)): ?>
<?php $attributes = $__attributesOriginal9b6ddd2809dd60ece07dfaf1f3ef876f; ?>
<?php unset($__attributesOriginal9b6ddd2809dd60ece07dfaf1f3ef876f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9b6ddd2809dd60ece07dfaf1f3ef876f)): ?>
<?php $component = $__componentOriginal9b6ddd2809dd60ece07dfaf1f3ef876f; ?>
<?php unset($__componentOriginal9b6ddd2809dd60ece07dfaf1f3ef876f); ?>
<?php endif; ?>
            </div>
        </div>
    </div>
</header>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/navigation.blade.php ENDPATH**/ ?>
```

# storage\framework\views\b9814a8c50112e8ca1830e4a31a62713.php

```php
<?php if (isset($component)) { $__componentOriginalbbd4eeea836234825f7514ed20d2d52d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbbd4eeea836234825f7514ed20d2d52d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.layout','data' => ['exception' => $exception]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['exception' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($exception)]); ?>
    <div class="renderer container mx-auto lg:px-8">
        <?php if (isset($component)) { $__componentOriginal10cd8b81fdad4ce00a06c99f27003014 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal10cd8b81fdad4ce00a06c99f27003014 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.navigation','data' => ['exception' => $exception]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::navigation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['exception' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($exception)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal10cd8b81fdad4ce00a06c99f27003014)): ?>
<?php $attributes = $__attributesOriginal10cd8b81fdad4ce00a06c99f27003014; ?>
<?php unset($__attributesOriginal10cd8b81fdad4ce00a06c99f27003014); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal10cd8b81fdad4ce00a06c99f27003014)): ?>
<?php $component = $__componentOriginal10cd8b81fdad4ce00a06c99f27003014; ?>
<?php unset($__componentOriginal10cd8b81fdad4ce00a06c99f27003014); ?>
<?php endif; ?>

        <main class="px-6 pb-12 pt-6">
            <div class="container mx-auto">
                <?php if (isset($component)) { $__componentOriginal1e817eb3c41fe3ea9eb0c15213c4b557 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1e817eb3c41fe3ea9eb0c15213c4b557 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.header','data' => ['exception' => $exception]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['exception' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($exception)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1e817eb3c41fe3ea9eb0c15213c4b557)): ?>
<?php $attributes = $__attributesOriginal1e817eb3c41fe3ea9eb0c15213c4b557; ?>
<?php unset($__attributesOriginal1e817eb3c41fe3ea9eb0c15213c4b557); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1e817eb3c41fe3ea9eb0c15213c4b557)): ?>
<?php $component = $__componentOriginal1e817eb3c41fe3ea9eb0c15213c4b557; ?>
<?php unset($__componentOriginal1e817eb3c41fe3ea9eb0c15213c4b557); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal1dc7d865c9b6045c4d68faf8bde572ed = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1dc7d865c9b6045c4d68faf8bde572ed = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.trace-and-editor','data' => ['exception' => $exception]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::trace-and-editor'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['exception' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($exception)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1dc7d865c9b6045c4d68faf8bde572ed)): ?>
<?php $attributes = $__attributesOriginal1dc7d865c9b6045c4d68faf8bde572ed; ?>
<?php unset($__attributesOriginal1dc7d865c9b6045c4d68faf8bde572ed); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1dc7d865c9b6045c4d68faf8bde572ed)): ?>
<?php $component = $__componentOriginal1dc7d865c9b6045c4d68faf8bde572ed; ?>
<?php unset($__componentOriginal1dc7d865c9b6045c4d68faf8bde572ed); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal523928ff754f95aea6faf87444393a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal523928ff754f95aea6faf87444393a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.context','data' => ['exception' => $exception]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::context'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['exception' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($exception)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal523928ff754f95aea6faf87444393a04)): ?>
<?php $attributes = $__attributesOriginal523928ff754f95aea6faf87444393a04; ?>
<?php unset($__attributesOriginal523928ff754f95aea6faf87444393a04); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal523928ff754f95aea6faf87444393a04)): ?>
<?php $component = $__componentOriginal523928ff754f95aea6faf87444393a04; ?>
<?php unset($__componentOriginal523928ff754f95aea6faf87444393a04); ?>
<?php endif; ?>
            </div>
        </main>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbbd4eeea836234825f7514ed20d2d52d)): ?>
<?php $attributes = $__attributesOriginalbbd4eeea836234825f7514ed20d2d52d; ?>
<?php unset($__attributesOriginalbbd4eeea836234825f7514ed20d2d52d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbbd4eeea836234825f7514ed20d2d52d)): ?>
<?php $component = $__componentOriginalbbd4eeea836234825f7514ed20d2d52d; ?>
<?php unset($__componentOriginalbbd4eeea836234825f7514ed20d2d52d); ?>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/show.blade.php ENDPATH**/ ?>
```

# storage\framework\views\c85096c31f616a75c73e53f7756f3b5a.php

```php
<svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    <?php echo e($attributes); ?>

>
    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
</svg>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/icons/moon.blade.php ENDPATH**/ ?>
```

# storage\framework\views\cad9ea3e55f6c947fa1fceafa7e6c657.php

```php
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" style="margin-bottom: -8px;">
  <path fill-rule="evenodd" d="M9.47 6.47a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 1 1-1.06 1.06L10 8.06l-3.72 3.72a.75.75 0 0 1-1.06-1.06l4.25-4.25Z" clip-rule="evenodd" />
</svg>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/icons/chevron-up.blade.php ENDPATH**/ ?>
```

# storage\framework\views\d907e8d7ba811a5f24876627e64bfebe.php

```php
<?php $__env->startSection('content'); ?>
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
                    <h2 class="text-xl font-semibold">Order #<?php echo e($order->order_number); ?></h2>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-pink-100 text-pink-800">
                        <?php echo e(ucfirst($order->status)); ?>

                    </span>
                </div>
                
                <div class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Delivery Information</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm"><?php echo e($order->customer_name); ?></p>
                                <p class="text-sm"><?php echo e($order->customer_email); ?></p>
                                <?php if($order->customer_phone): ?>
                                <p class="text-sm"><?php echo e($order->customer_phone); ?></p>
                                <?php endif; ?>
                                <p class="text-sm"><?php echo e($order->delivery_address); ?></p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Delivery Details</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm">Date: <?php echo e(date('F j, Y', strtotime($order->delivery_date))); ?></p>
                                <p class="text-sm">Time: <?php echo e($order->delivery_time); ?></p>
                                <?php if($order->special_instructions): ?>
                                <p class="text-sm">Special Instructions: <?php echo e($order->special_instructions); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Order Items</h3>
                        <div class="mt-2 space-y-4">
                            <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex gap-4">
                                <img
                                    src="<?php echo e(asset('images/cakes/' . $item->cake->image)); ?>"
                                    alt="<?php echo e($item->cake->name); ?>"
                                    class="aspect-square rounded-md object-cover h-16 w-16"
                                />
                                <div class="flex-1">
                                    <h4 class="font-medium"><?php echo e($item->cake->name); ?></h4>
                                    <div class="flex justify-between mt-1">
                                        <span class="text-sm text-gray-600">Qty: <?php echo e($item->quantity); ?></span>
                                        <span class="text-sm font-medium">$<?php echo e(number_format($item->subtotal, 2)); ?></span>
                                    </div>
                                    <?php if($item->customization): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo e($item->customization); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>₱<?php echo e(number_format($order->subtotal, 2)); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Tax (8%)</span>
                                <span>₱<?php echo e(number_format($order->tax, 2)); ?></span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between font-semibold">
                                    <span>Total</span>
                                    <span>₱<?php echo e(number_format($order->total, 2)); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <h3 class="text-sm font-medium text-gray-500">Payment Information</h3>
                        <div class="mt-2 space-y-1">
                            <p class="text-sm">Method: <?php echo e(ucfirst(str_replace('_', ' ', $order->payment_method))); ?></p>
                            <p class="text-sm">Status: <?php echo e(ucfirst($order->payment_status)); ?></p>
                            <?php if($order->transaction_id): ?>
                            <p class="text-sm">Transaction ID: <?php echo e($order->transaction_id); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-gray-600 mb-4">A confirmation email has been sent to <?php echo e($order->customer_email); ?></p>
                <a href="<?php echo e(route('home')); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                    Return to Home
                </a>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cake-website\resources\views/checkout/confirmation.blade.php ENDPATH**/ ?>
```

# storage\framework\views\ead6889e34244d4f3a3f8d677c85c43b.php

```php
<?php $__env->startSection('content'); ?>
    <section class="w-full py-12 md:py-24 lg:py-32 bg-pink-50">
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
                        <a href="<?php echo e(route('order')); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
                            Order Now
                        </a>
                        <a href="<?php echo e(route('menu')); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            View Menu
                        </a>
                    </div>
                </div>
                <div class="mx-auto w-full max-w-[500px] lg:max-w-none relative">
                    <img
                        src="<?php echo e(asset('images/hero-cake.jpg')); ?>"
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
                        Our Signature Cakes
                    </h2>
                    <p class="max-w-[700px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                        Explore our collection of handcrafted cakes made with premium ingredients and love.
                    </p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                <?php $__currentLoopData = $cakes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cake): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden">
                    <div class="relative">
                        <img
                            src="<?php echo e(asset('images/cakes/' . $cake['image'])); ?>"
                            alt="<?php echo e($cake['name']); ?>"
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
                                <h3 class="font-semibold text-lg"><?php echo e($cake['name']); ?></h3>
                                <p class="text-sm text-gray-600 mt-1"><?php echo e($cake['description']); ?></p>
                            </div>
                            <div class="text-pink-600 font-bold"><?php echo e($cake['price']); ?></div>
                        </div>
                        <a href="<?php echo e(route('order', ['cake' => $cake['id']])); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2 w-full mt-4">
                            Order Now
                        </a>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div class="flex justify-center mt-8">
                <a href="<?php echo e(route('menu')); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
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
                        src="<?php echo e(asset('images/bakery.jpg')); ?>"
                        alt="Our bakery interior with bakers working"
                        class="mx-auto rounded-xl object-cover"
                    />
                </div>
                <div class="flex flex-col justify-center space-y-4">
                    <div class="space-y-2">
                        <h2 class="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl text-pink-800">
                            Our Sweet Story
                        </h2>
                        <p class="max-w-[600px] text-gray-600 md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                            Sweet Delights was founded in 2010 with a simple mission: to create delicious, beautiful cakes that
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
                        <a href="<?php echo e(route('team')); ?>" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-pink-600 text-white hover:bg-pink-700 h-10 px-4 py-2">
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
                <?php $__currentLoopData = $testimonials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $testimonial): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6">
                    <div class="flex flex-col space-y-4">
                        <div class="flex">
                            <?php for($i = 0; $i < $testimonial['rating']; $i++): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 fill-yellow-400 text-yellow-400">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-600 italic">"<?php echo e($testimonial['comment']); ?>"</p>
                        <div class="flex items-center space-x-2">
                            <div class="rounded-full bg-pink-100 p-1">
                                <span class="text-pink-600 font-bold text-sm">
                                    <?php echo e($testimonial['initials']); ?>

                                </span>
                            </div>
                            <span class="font-medium"><?php echo e($testimonial['name']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                    <form action="<?php echo e(route('contact.submit')); ?>" method="POST" class="grid gap-4 p-6 bg-white rounded-xl shadow-sm">
                        <?php echo csrf_field(); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\cake-website\resources\views/home.blade.php ENDPATH**/ ?>
```

# storage\framework\views\ed8bb4cb0b6535036fe6ef5eb72f8f71.php

```php
<?php use \Illuminate\Foundation\Exceptions\Renderer\Renderer; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    />

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg viewBox='0 -.11376601 49.74245785 51.31690859' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='m49.626 11.564a.809.809 0 0 1 .028.209v10.972a.8.8 0 0 1 -.402.694l-9.209 5.302v10.509c0 .286-.152.55-.4.694l-19.223 11.066c-.044.025-.092.041-.14.058-.018.006-.035.017-.054.022a.805.805 0 0 1 -.41 0c-.022-.006-.042-.018-.063-.026-.044-.016-.09-.03-.132-.054l-19.219-11.066a.801.801 0 0 1 -.402-.694v-32.916c0-.072.01-.142.028-.21.006-.023.02-.044.028-.067.015-.042.029-.085.051-.124.015-.026.037-.047.055-.071.023-.032.044-.065.071-.093.023-.023.053-.04.079-.06.029-.024.055-.05.088-.069h.001l9.61-5.533a.802.802 0 0 1 .8 0l9.61 5.533h.002c.032.02.059.045.088.068.026.02.055.038.078.06.028.029.048.062.072.094.017.024.04.045.054.071.023.04.036.082.052.124.008.023.022.044.028.068a.809.809 0 0 1 .028.209v20.559l8.008-4.611v-10.51c0-.07.01-.141.028-.208.007-.024.02-.045.028-.068.016-.042.03-.085.052-.124.015-.026.037-.047.054-.071.024-.032.044-.065.072-.093.023-.023.052-.04.078-.06.03-.024.056-.05.088-.069h.001l9.611-5.533a.801.801 0 0 1 .8 0l9.61 5.533c.034.02.06.045.09.068.025.02.054.038.077.06.028.029.048.062.072.094.018.024.04.045.054.071.023.039.036.082.052.124.009.023.022.044.028.068zm-1.574 10.718v-9.124l-3.363 1.936-4.646 2.675v9.124l8.01-4.611zm-9.61 16.505v-9.13l-4.57 2.61-13.05 7.448v9.216zm-36.84-31.068v31.068l17.618 10.143v-9.214l-9.204-5.209-.003-.002-.004-.002c-.031-.018-.057-.044-.086-.066-.025-.02-.054-.036-.076-.058l-.002-.003c-.026-.025-.044-.056-.066-.084-.02-.027-.044-.05-.06-.078l-.001-.003c-.018-.03-.029-.066-.042-.1-.013-.03-.03-.058-.038-.09v-.001c-.01-.038-.012-.078-.016-.117-.004-.03-.012-.06-.012-.09v-21.483l-4.645-2.676-3.363-1.934zm8.81-5.994-8.007 4.609 8.005 4.609 8.006-4.61-8.006-4.608zm4.164 28.764 4.645-2.674v-20.096l-3.363 1.936-4.646 2.675v20.096zm24.667-23.325-8.006 4.609 8.006 4.609 8.005-4.61zm-.801 10.605-4.646-2.675-3.363-1.936v9.124l4.645 2.674 3.364 1.937zm-18.422 20.561 11.743-6.704 5.87-3.35-8-4.606-9.211 5.303-8.395 4.833z' fill='%23ff2d20'/%3E%3C/svg%3E" />

    <link
        href="https://fonts.bunny.net/css?family=figtree:300,400,500,600"
        rel="stylesheet"
    />

    <?php echo Renderer::css(); ?>


    <style>
        <?php $__currentLoopData = $exception->frames(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $frame): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            #frame-<?php echo e($loop->index); ?> .hljs-ln-line[data-line-number='<?php echo e($frame->line()); ?>'] {
                background-color: rgba(242, 95, 95, 0.4);
            }
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </style>
</head>
<body class="bg-gray-200/80 font-sans antialiased dark:bg-gray-950/95">
    <?php echo e($slot); ?>


    <?php echo Renderer::js(); ?>


    <script>
        !function(r,o){"use strict";var e,i="hljs-ln",l="hljs-ln-line",h="hljs-ln-code",s="hljs-ln-numbers",c="hljs-ln-n",m="data-line-number",a=/\r\n|\r|\n/g;function u(e){for(var n=e.toString(),t=e.anchorNode;"TD"!==t.nodeName;)t=t.parentNode;for(var r=e.focusNode;"TD"!==r.nodeName;)r=r.parentNode;var o=parseInt(t.dataset.lineNumber),a=parseInt(r.dataset.lineNumber);if(o==a)return n;var i,l=t.textContent,s=r.textContent;for(a<o&&(i=o,o=a,a=i,i=l,l=s,s=i);0!==n.indexOf(l);)l=l.slice(1);for(;-1===n.lastIndexOf(s);)s=s.slice(0,-1);for(var c=l,u=function(e){for(var n=e;"TABLE"!==n.nodeName;)n=n.parentNode;return n}(t),d=o+1;d<a;++d){var f=p('.{0}[{1}="{2}"]',[h,m,d]);c+="\n"+u.querySelector(f).textContent}return c+="\n"+s}function n(e){try{var n=o.querySelectorAll("code.hljs,code.nohighlight");for(var t in n)n.hasOwnProperty(t)&&(n[t].classList.contains("nohljsln")||d(n[t],e))}catch(e){r.console.error("LineNumbers error: ",e)}}function d(e,n){"object"==typeof e&&r.setTimeout(function(){e.innerHTML=f(e,n)},0)}function f(e,n){var t,r,o=(t=e,{singleLine:function(e){return!!e.singleLine&&e.singleLine}(r=(r=n)||{}),startFrom:function(e,n){var t=1;isFinite(n.startFrom)&&(t=n.startFrom);var r=function(e,n){return e.hasAttribute(n)?e.getAttribute(n):null}(e,"data-ln-start-from");return null!==r&&(t=function(e,n){if(!e)return n;var t=Number(e);return isFinite(t)?t:n}(r,1)),t}(t,r)});return function e(n){var t=n.childNodes;for(var r in t){var o;t.hasOwnProperty(r)&&(o=t[r],0<(o.textContent.trim().match(a)||[]).length&&(0<o.childNodes.length?e(o):v(o.parentNode)))}}(e),function(e,n){var t=g(e);""===t[t.length-1].trim()&&t.pop();if(1<t.length||n.singleLine){for(var r="",o=0,a=t.length;o<a;o++)r+=p('<tr><td class="{0} {1}" {3}="{5}"><div class="{2}" {3}="{5}"></div></td><td class="{0} {4}" {3}="{5}">{6}</td></tr>',[l,s,c,m,h,o+n.startFrom,0<t[o].length?t[o]:" "]);return p('<table class="{0}">{1}</table>',[i,r])}return e}(e.innerHTML,o)}function v(e){var n=e.className;if(/hljs-/.test(n)){for(var t=g(e.innerHTML),r=0,o="";r<t.length;r++){o+=p('<span class="{0}">{1}</span>\n',[n,0<t[r].length?t[r]:" "])}e.innerHTML=o.trim()}}function g(e){return 0===e.length?[]:e.split(a)}function p(e,t){return e.replace(/\{(\d+)\}/g,function(e,n){return void 0!==t[n]?t[n]:e})}r.hljs?(r.hljs.initLineNumbersOnLoad=function(e){"interactive"===o.readyState||"complete"===o.readyState?n(e):r.addEventListener("DOMContentLoaded",function(){n(e)})},r.hljs.lineNumbersBlock=d,r.hljs.lineNumbersValue=function(e,n){if("string"!=typeof e)return;var t=document.createElement("code");return t.innerHTML=e,f(t,n)},(e=o.createElement("style")).type="text/css",e.innerHTML=p(".{0}{border-collapse:collapse}.{0} td{padding:0}.{1}:before{content:attr({2})}",[i,c,m]),o.getElementsByTagName("head")[0].appendChild(e)):r.console.error("highlight.js not detected!"),document.addEventListener("copy",function(e){var n,t=window.getSelection();!function(e){for(var n=e;n;){if(n.className&&-1!==n.className.indexOf("hljs-ln-code"))return 1;n=n.parentNode}}(t.anchorNode)||(n=-1!==window.navigator.userAgent.indexOf("Edge")?u(t):t.toString(),e.clipboardData.setData("text/plain",n),e.preventDefault())})}(window,document);

        hljs.initLineNumbersOnLoad()

        window.addEventListener('load', function() {
            document.querySelectorAll('.renderer').forEach(function(element, index) {
                if (index > 0) {
                    element.remove();
                }
            });

            document.querySelector('.default-highlightable-code').style.display = 'block';

            document.querySelectorAll('.highlightable-code').forEach(function(element) {
                element.style.display = 'block';
            })
        });
    </script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/layout.blade.php ENDPATH**/ ?>
```

# storage\framework\views\fdd44d3d10745a9adcf92c6d988f41ea.php

```php
<?php if (isset($component)) { $__componentOriginal74daf2d0a9c625ad90327a6043d15980 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74daf2d0a9c625ad90327a6043d15980 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'laravel-exceptions-renderer::components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('laravel-exceptions-renderer::card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="md:flex md:items-center md:justify-between md:gap-2">
        <div class="min-w-0">
            <div class="inline-block rounded-full bg-red-500/20 px-3 py-2 max-w-full text-sm font-bold leading-5 text-red-500 truncate lg:text-base dark:bg-red-500/20">
                <span class="hidden md:inline">
                    <?php echo e($exception->class()); ?>

                </span>
                <span class="md:hidden">
                    <?php echo e(implode(' ', array_slice(explode('\\', $exception->class()), -1))); ?>

                </span>
            </div>
            <div class="mt-4 text-lg font-semibold text-gray-900 break-words dark:text-white lg:text-2xl">
                <?php echo e($exception->message()); ?>

            </div>
        </div>

        <div class="hidden text-right shrink-0 md:block md:min-w-64 md:max-w-80">
            <div>
                <span class="inline-block rounded-full bg-gray-200 px-3 py-2 text-sm leading-5 text-gray-900 max-w-full truncate dark:bg-gray-800 dark:text-white">
                    <?php echo e($exception->request()->method()); ?> <?php echo e($exception->request()->httpHost()); ?>

                </span>
            </div>
            <div class="px-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">PHP <?php echo e(PHP_VERSION); ?> — Laravel <?php echo e(app()->version()); ?></span>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $attributes = $__attributesOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__attributesOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74daf2d0a9c625ad90327a6043d15980)): ?>
<?php $component = $__componentOriginal74daf2d0a9c625ad90327a6043d15980; ?>
<?php unset($__componentOriginal74daf2d0a9c625ad90327a6043d15980); ?>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\cake-website\vendor\laravel\framework\src\Illuminate\Foundation\Providers/../resources/exceptions/renderer/components/header.blade.php ENDPATH**/ ?>
```

# storage\logs\.gitignore

```
*
!.gitignore

```

# storage\logs\laravel.log

```log
[2025-05-12 11:30:54] local.ERROR: No application encryption key has been specified. {"exception":"[object] (Illuminate\\Encryption\\MissingAppKeyException(code: 0): No application encryption key has been specified. at C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php:83)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Support\\helpers.php(399): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}('')
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(81): tap('', Object(Closure))
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(64): Illuminate\\Encryption\\EncryptionServiceProvider->key(Array)
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(32): Illuminate\\Encryption\\EncryptionServiceProvider->parseKey(Array)
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1010): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}(Object(Illuminate\\Foundation\\Application), Array)
#5 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build(Object(Closure))
#6 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('encrypter', Array, true)
#7 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('encrypter', Array)
#8 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('encrypter', Array)
#9 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1202): Illuminate\\Foundation\\Application->make('encrypter')
#10 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1101): Illuminate\\Container\\Container->resolveClass(Object(ReflectionParameter))
#11 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1052): Illuminate\\Container\\Container->resolveDependencies(Array)
#12 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build('Illuminate\\\\Cook...')
#13 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('Illuminate\\\\Cook...', Array, true)
#14 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('Illuminate\\\\Cook...', Array)
#15 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('Illuminate\\\\Cook...', Array)
#16 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(197): Illuminate\\Foundation\\Application->make('Illuminate\\\\Cook...')
#17 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#18 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#19 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(786): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))
#20 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(750): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))
#21 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(739): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))
#22 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))
#23 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))
#24 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#25 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#26 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#27 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#28 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#29 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#30 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#31 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#32 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#33 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#34 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#35 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#36 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#37 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#38 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#39 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#40 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#41 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#42 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#43 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#44 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))
#45 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1219): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))
#46 C:\\xampp\\htdocs\\cake-website\\public\\index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))
#47 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php(23): require_once('C:\\\\xampp\\\\htdocs...')
#48 {main}
"} 
[2025-05-12 11:31:00] local.ERROR: No application encryption key has been specified. {"exception":"[object] (Illuminate\\Encryption\\MissingAppKeyException(code: 0): No application encryption key has been specified. at C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php:83)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Support\\helpers.php(399): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}('')
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(81): tap('', Object(Closure))
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(64): Illuminate\\Encryption\\EncryptionServiceProvider->key(Array)
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(32): Illuminate\\Encryption\\EncryptionServiceProvider->parseKey(Array)
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1010): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}(Object(Illuminate\\Foundation\\Application), Array)
#5 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build(Object(Closure))
#6 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('encrypter', Array, true)
#7 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('encrypter', Array)
#8 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('encrypter', Array)
#9 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1202): Illuminate\\Foundation\\Application->make('encrypter')
#10 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1101): Illuminate\\Container\\Container->resolveClass(Object(ReflectionParameter))
#11 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1052): Illuminate\\Container\\Container->resolveDependencies(Array)
#12 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build('Illuminate\\\\Cook...')
#13 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('Illuminate\\\\Cook...', Array, true)
#14 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('Illuminate\\\\Cook...', Array)
#15 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('Illuminate\\\\Cook...', Array)
#16 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(257): Illuminate\\Foundation\\Application->make('Illuminate\\\\Cook...')
#17 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(215): Illuminate\\Foundation\\Http\\Kernel->terminateMiddleware(Object(Illuminate\\Http\\Request), Object(Illuminate\\Http\\Response))
#18 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1221): Illuminate\\Foundation\\Http\\Kernel->terminate(Object(Illuminate\\Http\\Request), Object(Illuminate\\Http\\Response))
#19 C:\\xampp\\htdocs\\cake-website\\public\\index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))
#20 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php(23): require_once('C:\\\\xampp\\\\htdocs...')
#21 {main}
"} 
[2025-05-12 11:31:52] local.ERROR: No application encryption key has been specified. {"exception":"[object] (Illuminate\\Encryption\\MissingAppKeyException(code: 0): No application encryption key has been specified. at C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php:83)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Support\\helpers.php(399): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}('')
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(81): tap('', Object(Closure))
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(64): Illuminate\\Encryption\\EncryptionServiceProvider->key(Array)
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(32): Illuminate\\Encryption\\EncryptionServiceProvider->parseKey(Array)
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1010): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}(Object(Illuminate\\Foundation\\Application), Array)
#5 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build(Object(Closure))
#6 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('encrypter', Array, true)
#7 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('encrypter', Array)
#8 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('encrypter', Array)
#9 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1202): Illuminate\\Foundation\\Application->make('encrypter')
#10 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1101): Illuminate\\Container\\Container->resolveClass(Object(ReflectionParameter))
#11 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1052): Illuminate\\Container\\Container->resolveDependencies(Array)
#12 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build('Illuminate\\\\Cook...')
#13 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('Illuminate\\\\Cook...', Array, true)
#14 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('Illuminate\\\\Cook...', Array)
#15 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('Illuminate\\\\Cook...', Array)
#16 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(197): Illuminate\\Foundation\\Application->make('Illuminate\\\\Cook...')
#17 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#18 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#19 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(786): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))
#20 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(750): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))
#21 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(739): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))
#22 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))
#23 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))
#24 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#25 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#26 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#27 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#28 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#29 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#30 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#31 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#32 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#33 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#34 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#35 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#36 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#37 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#38 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#39 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#40 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#41 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#42 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#43 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#44 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))
#45 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1219): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))
#46 C:\\xampp\\htdocs\\cake-website\\public\\index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))
#47 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php(23): require_once('C:\\\\xampp\\\\htdocs...')
#48 {main}
"} 
[2025-05-12 11:31:53] local.ERROR: No application encryption key has been specified. {"exception":"[object] (Illuminate\\Encryption\\MissingAppKeyException(code: 0): No application encryption key has been specified. at C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php:83)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Support\\helpers.php(399): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}('')
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(81): tap('', Object(Closure))
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(64): Illuminate\\Encryption\\EncryptionServiceProvider->key(Array)
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Encryption\\EncryptionServiceProvider.php(32): Illuminate\\Encryption\\EncryptionServiceProvider->parseKey(Array)
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1010): Illuminate\\Encryption\\EncryptionServiceProvider->Illuminate\\Encryption\\{closure}(Object(Illuminate\\Foundation\\Application), Array)
#5 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build(Object(Closure))
#6 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('encrypter', Array, true)
#7 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('encrypter', Array)
#8 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('encrypter', Array)
#9 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1202): Illuminate\\Foundation\\Application->make('encrypter')
#10 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1101): Illuminate\\Container\\Container->resolveClass(Object(ReflectionParameter))
#11 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(1052): Illuminate\\Container\\Container->resolveDependencies(Array)
#12 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(890): Illuminate\\Container\\Container->build('Illuminate\\\\Cook...')
#13 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1077): Illuminate\\Container\\Container->resolve('Illuminate\\\\Cook...', Array, true)
#14 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(821): Illuminate\\Foundation\\Application->resolve('Illuminate\\\\Cook...', Array)
#15 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1057): Illuminate\\Container\\Container->make('Illuminate\\\\Cook...', Array)
#16 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(257): Illuminate\\Foundation\\Application->make('Illuminate\\\\Cook...')
#17 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(215): Illuminate\\Foundation\\Http\\Kernel->terminateMiddleware(Object(Illuminate\\Http\\Request), Object(Illuminate\\Http\\Response))
#18 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1221): Illuminate\\Foundation\\Http\\Kernel->terminate(Object(Illuminate\\Http\\Request), Object(Illuminate\\Http\\Response))
#19 C:\\xampp\\htdocs\\cake-website\\public\\index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))
#20 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php(23): require_once('C:\\\\xampp\\\\htdocs...')
#21 {main}
"} 
[2025-05-12 11:32:47] local.ERROR: There are no commands defined in the "generate" namespace. {"exception":"[object] (Symfony\\Component\\Console\\Exception\\NamespaceNotFoundException(code: 0): There are no commands defined in the \"generate\" namespace. at C:\\xampp\\htdocs\\cake-website\\vendor\\symfony\\console\\Application.php:660)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\symfony\\console\\Application.php(709): Symfony\\Component\\Console\\Application->findNamespace('generate')
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\symfony\\console\\Application.php(284): Symfony\\Component\\Console\\Application->find('generate:key')
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\symfony\\console\\Application.php(193): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php(197): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1234): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))
#5 C:\\xampp\\htdocs\\cake-website\\artisan(16): Illuminate\\Foundation\\Application->handleCommand(Object(Symfony\\Component\\Console\\Input\\ArgvInput))
#6 {main}
"} 
[2025-05-12 11:44:06] local.ERROR: Command "php" is not defined. {"exception":"[object] (Symfony\\Component\\Console\\Exception\\CommandNotFoundException(code: 0): Command \"php\" is not defined. at C:\\xampp\\htdocs\\cake-website\\vendor\\symfony\\console\\Application.php:726)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\symfony\\console\\Application.php(284): Symfony\\Component\\Console\\Application->find('php')
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\symfony\\console\\Application.php(193): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php(197): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1234): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))
#4 C:\\xampp\\htdocs\\cake-website\\artisan(16): Illuminate\\Foundation\\Application->handleCommand(Object(Symfony\\Component\\Console\\Input\\ArgvInput))
#5 {main}
"} 
[2025-05-12 13:43:26] local.ERROR: Vite manifest not found at: C:\xampp\htdocs\cake-website\public\build/manifest.json (View: C:\xampp\htdocs\cake-website\resources\views\layouts\app.blade.php) (View: C:\xampp\htdocs\cake-website\resources\views\layouts\app.blade.php) {"exception":"[object] (Illuminate\\View\\ViewException(code: 0): Vite manifest not found at: C:\\xampp\\htdocs\\cake-website\\public\\build/manifest.json (View: C:\\xampp\\htdocs\\cake-website\\resources\\views\\layouts\\app.blade.php) (View: C:\\xampp\\htdocs\\cake-website\\resources\\views\\layouts\\app.blade.php) at C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Vite.php:934)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException(Object(Illuminate\\View\\ViewException), 1)
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\CompilerEngine.php(74): Illuminate\\View\\Engines\\PhpEngine->evaluatePath('C:\\\\xampp\\\\htdocs...', Array)
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get('C:\\\\xampp\\\\htdocs...', Array)
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(191): Illuminate\\View\\View->getContents()
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(160): Illuminate\\View\\View->renderContents()
#5 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Response.php(78): Illuminate\\View\\View->render()
#6 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Response.php(34): Illuminate\\Http\\Response->setContent(Object(Illuminate\\View\\View))
#7 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(924): Illuminate\\Http\\Response->__construct(Object(Illuminate\\View\\View), 200, Array)
#8 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(891): Illuminate\\Routing\\Router::toResponse(Object(Illuminate\\Http\\Request), Object(Illuminate\\View\\View))
#9 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Routing\\Router->prepareResponse(Object(Illuminate\\Http\\Request), Object(Illuminate\\View\\View))
#10 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}(Object(Illuminate\\Http\\Request))
#11 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#12 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#13 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken.php(87): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#14 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#15 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Middleware\\ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#16 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#17 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Session\\Middleware\\StartSession.php(120): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#18 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Session\\Middleware\\StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest(Object(Illuminate\\Http\\Request), Object(Illuminate\\Session\\Store), Object(Closure))
#19 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Session\\Middleware\\StartSession->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#20 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#21 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#22 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Cookie\\Middleware\\EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#23 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#24 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#25 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#26 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(786): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))
#27 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(750): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))
#28 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(739): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))
#29 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))
#30 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))
#31 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#32 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#33 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#34 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#35 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#36 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#37 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#38 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#39 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#40 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#41 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#42 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#43 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#44 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#45 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#46 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#47 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#48 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#49 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#50 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#51 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))
#52 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1219): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))
#53 C:\\xampp\\htdocs\\cake-website\\public\\index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))
#54 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php(23): require_once('C:\\\\xampp\\\\htdocs...')
#55 {main}

[previous exception] [object] (Illuminate\\View\\ViewException(code: 0): Vite manifest not found at: C:\\xampp\\htdocs\\cake-website\\public\\build/manifest.json (View: C:\\xampp\\htdocs\\cake-website\\resources\\views\\layouts\\app.blade.php) at C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Vite.php:934)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\PhpEngine.php(59): Illuminate\\View\\Engines\\CompilerEngine->handleViewException(Object(Illuminate\\Foundation\\ViteManifestNotFoundException), 2)
#1 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\CompilerEngine.php(74): Illuminate\\View\\Engines\\PhpEngine->evaluatePath('C:\\\\xampp\\\\htdocs...', Array)
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get('C:\\\\xampp\\\\htdocs...', Array)
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(191): Illuminate\\View\\View->getContents()
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(160): Illuminate\\View\\View->renderContents()
#5 C:\\xampp\\htdocs\\cake-website\\storage\\framework\\views\\ead6889e34244d4f3a3f8d677c85c43b.php(248): Illuminate\\View\\View->render()
#6 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Filesystem\\Filesystem.php(123): require('C:\\\\xampp\\\\htdocs...')
#7 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Filesystem\\Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::Illuminate\\Filesystem\\{closure}()
#8 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire('C:\\\\xampp\\\\htdocs...', Array)
#9 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\CompilerEngine.php(74): Illuminate\\View\\Engines\\PhpEngine->evaluatePath('C:\\\\xampp\\\\htdocs...', Array)
#10 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get('C:\\\\xampp\\\\htdocs...', Array)
#11 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(191): Illuminate\\View\\View->getContents()
#12 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(160): Illuminate\\View\\View->renderContents()
#13 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Response.php(78): Illuminate\\View\\View->render()
#14 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Response.php(34): Illuminate\\Http\\Response->setContent(Object(Illuminate\\View\\View))
#15 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(924): Illuminate\\Http\\Response->__construct(Object(Illuminate\\View\\View), 200, Array)
#16 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(891): Illuminate\\Routing\\Router::toResponse(Object(Illuminate\\Http\\Request), Object(Illuminate\\View\\View))
#17 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Routing\\Router->prepareResponse(Object(Illuminate\\Http\\Request), Object(Illuminate\\View\\View))
#18 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}(Object(Illuminate\\Http\\Request))
#19 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#20 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#21 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken.php(87): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#22 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#23 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Middleware\\ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#24 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#25 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Session\\Middleware\\StartSession.php(120): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#26 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Session\\Middleware\\StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest(Object(Illuminate\\Http\\Request), Object(Illuminate\\Session\\Store), Object(Closure))
#27 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Session\\Middleware\\StartSession->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#28 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#29 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#30 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Cookie\\Middleware\\EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#31 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#32 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#33 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#34 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(786): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))
#35 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(750): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))
#36 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(739): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))
#37 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))
#38 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))
#39 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#40 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#41 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#42 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#43 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#44 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#45 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#46 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#47 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#48 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#49 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#50 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#51 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#52 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#53 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#54 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#55 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#56 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#57 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#58 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#59 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))
#60 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1219): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))
#61 C:\\xampp\\htdocs\\cake-website\\public\\index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))
#62 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php(23): require_once('C:\\\\xampp\\\\htdocs...')
#63 {main}

[previous exception] [object] (Illuminate\\Foundation\\ViteManifestNotFoundException(code: 0): Vite manifest not found at: C:\\xampp\\htdocs\\cake-website\\public\\build/manifest.json at C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Vite.php:934)
[stacktrace]
#0 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Vite.php(384): Illuminate\\Foundation\\Vite->manifest('build')
#1 C:\\xampp\\htdocs\\cake-website\\storage\\framework\\views\\2f6eb7980101b666dd025f50f90e3245.php(15): Illuminate\\Foundation\\Vite->__invoke(Object(Illuminate\\Support\\Collection))
#2 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Filesystem\\Filesystem.php(123): require('C:\\\\xampp\\\\htdocs...')
#3 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Filesystem\\Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::Illuminate\\Filesystem\\{closure}()
#4 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire('C:\\\\xampp\\\\htdocs...', Array)
#5 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\CompilerEngine.php(74): Illuminate\\View\\Engines\\PhpEngine->evaluatePath('C:\\\\xampp\\\\htdocs...', Array)
#6 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get('C:\\\\xampp\\\\htdocs...', Array)
#7 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(191): Illuminate\\View\\View->getContents()
#8 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(160): Illuminate\\View\\View->renderContents()
#9 C:\\xampp\\htdocs\\cake-website\\storage\\framework\\views\\ead6889e34244d4f3a3f8d677c85c43b.php(248): Illuminate\\View\\View->render()
#10 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Filesystem\\Filesystem.php(123): require('C:\\\\xampp\\\\htdocs...')
#11 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Filesystem\\Filesystem.php(124): Illuminate\\Filesystem\\Filesystem::Illuminate\\Filesystem\\{closure}()
#12 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\PhpEngine.php(57): Illuminate\\Filesystem\\Filesystem->getRequire('C:\\\\xampp\\\\htdocs...', Array)
#13 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Engines\\CompilerEngine.php(74): Illuminate\\View\\Engines\\PhpEngine->evaluatePath('C:\\\\xampp\\\\htdocs...', Array)
#14 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(208): Illuminate\\View\\Engines\\CompilerEngine->get('C:\\\\xampp\\\\htdocs...', Array)
#15 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(191): Illuminate\\View\\View->getContents()
#16 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\View.php(160): Illuminate\\View\\View->renderContents()
#17 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Response.php(78): Illuminate\\View\\View->render()
#18 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Response.php(34): Illuminate\\Http\\Response->setContent(Object(Illuminate\\View\\View))
#19 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(924): Illuminate\\Http\\Response->__construct(Object(Illuminate\\View\\View), 200, Array)
#20 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(891): Illuminate\\Routing\\Router::toResponse(Object(Illuminate\\Http\\Request), Object(Illuminate\\View\\View))
#21 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Routing\\Router->prepareResponse(Object(Illuminate\\Http\\Request), Object(Illuminate\\View\\View))
#22 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}(Object(Illuminate\\Http\\Request))
#23 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Middleware\\SubstituteBindings.php(50): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#24 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Routing\\Middleware\\SubstituteBindings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#25 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken.php(87): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#26 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#27 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\View\\Middleware\\ShareErrorsFromSession.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#28 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\View\\Middleware\\ShareErrorsFromSession->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#29 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Session\\Middleware\\StartSession.php(120): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#30 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Session\\Middleware\\StartSession.php(63): Illuminate\\Session\\Middleware\\StartSession->handleStatefulRequest(Object(Illuminate\\Http\\Request), Object(Illuminate\\Session\\Store), Object(Closure))
#31 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Session\\Middleware\\StartSession->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#32 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse.php(36): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#33 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#34 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Cookie\\Middleware\\EncryptCookies.php(74): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#35 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Cookie\\Middleware\\EncryptCookies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#36 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#37 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(807): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#38 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(786): Illuminate\\Routing\\Router->runRouteWithinStack(Object(Illuminate\\Routing\\Route), Object(Illuminate\\Http\\Request))
#39 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(750): Illuminate\\Routing\\Router->runRoute(Object(Illuminate\\Http\\Request), Object(Illuminate\\Routing\\Route))
#40 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Routing\\Router.php(739): Illuminate\\Routing\\Router->dispatchToRoute(Object(Illuminate\\Http\\Request))
#41 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(200): Illuminate\\Routing\\Router->dispatch(Object(Illuminate\\Http\\Request))
#42 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(169): Illuminate\\Foundation\\Http\\Kernel->Illuminate\\Foundation\\Http\\{closure}(Object(Illuminate\\Http\\Request))
#43 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#44 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#45 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#46 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#47 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#48 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#49 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#50 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePostSize->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#51 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance.php(109): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#52 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#53 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\HandleCors.php(48): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#54 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\HandleCors->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#55 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#56 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\TrustProxies->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#57 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#58 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#59 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Http\\Middleware\\ValidatePathEncoding.php(26): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#60 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(208): Illuminate\\Http\\Middleware\\ValidatePathEncoding->handle(Object(Illuminate\\Http\\Request), Object(Closure))
#61 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(126): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Http\\Request))
#62 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(175): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))
#63 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Http\\Kernel.php(144): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter(Object(Illuminate\\Http\\Request))
#64 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1219): Illuminate\\Foundation\\Http\\Kernel->handle(Object(Illuminate\\Http\\Request))
#65 C:\\xampp\\htdocs\\cake-website\\public\\index.php(20): Illuminate\\Foundation\\Application->handleRequest(Object(Illuminate\\Http\\Request))
#66 C:\\xampp\\htdocs\\cake-website\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\resources\\server.php(23): require_once('C:\\\\xampp\\\\htdocs...')
#67 {main}
"} 

```

# tailwind.config.js

```js
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./resources/**/*.blade.php", "./resources/**/*.js", "./resources/**/*.vue", "*.{js,ts,jsx,tsx,mdx}"],
    theme: {
      container: {
        center: true,
        padding: "2rem",
        screens: {
          "2xl": "1400px",
        },
      },
      extend: {
        colors: {
          border: "hsl(var(--border))",
          input: "hsl(var(--input))",
          ring: "hsl(var(--ring))",
          background: "hsl(var(--background))",
          foreground: "hsl(var(--foreground))",
          primary: {
            DEFAULT: "hsl(var(--primary))",
            foreground: "hsl(var(--primary-foreground))",
          },
          secondary: {
            DEFAULT: "hsl(var(--secondary))",
            foreground: "hsl(var(--secondary-foreground))",
          },
          destructive: {
            DEFAULT: "hsl(var(--destructive))",
            foreground: "hsl(var(--destructive-foreground))",
          },
          muted: {
            DEFAULT: "hsl(var(--muted))",
            foreground: "hsl(var(--muted-foreground))",
          },
          accent: {
            DEFAULT: "hsl(var(--accent))",
            foreground: "hsl(var(--accent-foreground))",
          },
          popover: {
            DEFAULT: "hsl(var(--popover))",
            foreground: "hsl(var(--popover-foreground))",
          },
          card: {
            DEFAULT: "hsl(var(--card))",
            foreground: "hsl(var(--card-foreground))",
          },
        },
        borderRadius: {
          lg: "var(--radius)",
          md: "calc(var(--radius) - 2px)",
          sm: "calc(var(--radius) - 4px)",
        },
      },
    },
    plugins: [],
  }
  
```

# tests\Feature\ExampleTest.php

```php
<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

```

# tests\TestCase.php

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}

```

# tests\Unit\ExampleTest.php

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}

```

# vite.config.js

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});

```

