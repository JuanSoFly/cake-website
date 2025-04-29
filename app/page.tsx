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
