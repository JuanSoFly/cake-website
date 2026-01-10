# Cake Website

Welcome to Cake Website — an online bakery shop where users can browse our delicious cake gallery, place orders, and get in touch! Built with Laravel (PHP & MySQL), it features a user-friendly interface, admin management, and secure order processing.

![Cake Website](public/images/ocakes-view.png)

## Features

- (+) **Order Cakes:** Seamless workflow for browsing and ordering cakes online.
- [#] **Gallery:** View a collection of our signature cakes with images and descriptions.
- (*) **Admin Panel:** Secure backend for managing cakes, orders, and customers.
- (@) **Contact Form:** Easily reach out for custom orders or inquiries.


## Getting Started

### Prerequisites

- PHP (8.0 or above recommended)
- Composer
- MySQL
- Node.js & npm (for asset compilation, optional)

### Installation

1. **Clone the Repository**
   ```bash
   git clone [https://github.com/JuanSoFly/cake-website.git](https://github.com/JuanSoFly/cake-website.git)
   cd cake-website

```

2. **Install Dependencies**
```bash
composer install
npm install && npm run dev   # optional, for frontend assets

```


3. **Set Up Environment**
* Copy `.env.example` to `.env` and update the database credentials.


```bash
cp .env.example .env
php artisan key:generate

```


4. **Run Migrations**
```bash
php artisan migrate
# Optionally seed the database
# php artisan db:seed

```


5. **Start Development Server**
```bash
php artisan serve

```


Access your site at [http://localhost:8000](http://localhost:8000).

## Usage

* Visit the homepage to view cakes.
* Use the order form to purchase cakes.
* Contact us via the contact page form.
* For admin access, log in at `/admin` (credentials setup via database/seeders).



## Contributing

Your contributions are welcome! Please:

* Fork the repository
* Create a feature branch
* Open a pull request describing your changes

For questions or suggestions, [open an issue](https://github.com/JuanSoFly/cake-website/issues).

## License

This project is open-source and available under the [MIT License](https://opensource.org/licenses/MIT).

---

### Contact

Have a question? Reach out via the contact form on the website or by [creating an issue](https://github.com/JuanSoFly/cake-website/issues).
