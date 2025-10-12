KissMVC
=======

**A Keep-It-Simple-Stupid PHP MVC Framework**

By [Joe Fallon](https://www.joefallon.net/)

KissMVC is a lightweight, fast, bare-bones MVC framework for PHP 7.4+ that
follows the KISS principle. Use it as a skeleton to build modern web
applications with minimal overhead and maximum clarity.

> "Make everything as simple as possible, but not simpler."  
> — Albert Einstein

> "Simplicity is prerequisite for reliability."  
> — Edsger W. Dijkstra

> "Fools ignore complexity; pragmatists suffer it; experts avoid it; geniuses
> remove it."  
> — Alan Perlis

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Architecture Overview](#architecture-overview)
- [Core Concepts](#core-concepts)
  - [Routing](#routing)
  - [Controllers](#controllers)
  - [Controller Factories](#controller-factories)
  - [Views and Layouts](#views-and-layouts)
  - [Models and Domain Logic](#models-and-domain-logic)
- [Configuration](#configuration)
- [Adding a New Page](#adding-a-new-page)
- [Server Configuration](#server-configuration)
  - [Nginx](#nginx)
  - [Apache](#apache)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Features

- **Minimal overhead**: Five core classes, zero bloat.
- **Standard folder structure**: Organized by responsibility (MVC pattern).
- **Simple routing**: One URL segment maps to one controller. Easy to trace.
- **Secure by default**: All public assets live in a single `public/`
  directory; application code is not web-accessible.
- **Fast learning curve**: 20-25 minutes to understand the entire framework.
- **Best practices**: Promotes migrations, gateways, models, controllers,
  layouts, views, and partials.
- **Composer-friendly**: Bring your own ORM, logger, or libraries.
- **PHP 7.4+ ready**: Strict types, typed properties, and modern syntax.

---

## Requirements

- **PHP 7.4 or higher** (8.0+ recommended)
- **Composer** for dependency management
- **Web server**: Nginx, Apache, or PHP's built-in server
- **Optional**: Database (MySQL, PostgreSQL, SQLite, etc.)

---

## Quick Start

```bash
# 1. Clone or download the repository
git clone https://github.com/yourusername/KissMVC.git myapp
cd myapp/website

# 2. Install dependencies
composer install

# 3. Configure your application
cp application/config/main.php application/config/main.local.php
# Edit main.local.php with your database credentials and settings

# 4. Start the built-in PHP server (for local development)
php -S localhost:8000 -t public

# 5. Open your browser
# Visit: http://localhost:8000
```

You should see the default "Hello, World!" page.

---

## Installation

KissMVC is both a small framework library **and** a folder structure for
organizing your application. It is not distributed as a standalone Composer
package; instead, you clone or download the entire skeleton.

### Step-by-step installation:

1. **Download or clone** this repository.
2. **Copy the `website/` folder** into your project repository.
3. **Rename `website/`** to match your application name (optional).
4. **Run `composer install`** inside the folder to install dependencies.
5. **Configure your web server** to point the document root to `public/`.
6. **Edit configuration files** in `application/config/` to match your
   environment.

---

## Project Structure

```
YourAppName/
│
├── application/             # Application code (not web-accessible)
│   ├── Bootstrapper.php     # App initialization (DB, services, etc.)
│   ├── config/
│   │   ├── main.php         # Main configuration (DB, paths, timezone)
│   │   └── routes.php       # Route definitions (URL → Controller map)
│   ├── Controllers/         # Page controllers (one per page)
│   │   ├── IndexController.php
│   │   ├── IndexControllerFactory.php
│   │   ├── PageWithParametersController.php
│   │   └── PageWithParametersControllerFactory.php
│   ├── domain/              # Business logic classes (shared across models)
│   ├── entities/            # Data objects representing DB rows
│   ├── gateways/            # Table gateways (CRUD for DB tables)
│   ├── layouts/             # Page layout templates (e.g. default.php)
│   ├── models/              # Page-specific models (orchestrate domain logic)
│   ├── partials/            # Reusable view snippets (e.g. header, footer)
│   └── views/               # Page-specific view templates
│
├── db/
│   └── migrations/          # Database migration scripts
│
├── lib/
│   └── KissMVC/             # Framework core (5 classes)
│       ├── Application.php
│       ├── Controller.php
│       ├── ControllerFactoryInterface.php
│       └── FrontController.php
│
├── public/                  # Web-accessible directory (document root)
│   ├── index.php            # Front controller entry point
│   ├── .htaccess            # Apache rewrite rules (optional)
│   ├── css/                 # Stylesheets
│   ├── img/                 # Images
│   └── js/                  # JavaScript files
│
├── tests/                   # Unit and integration tests
│   ├── index.php            # Test runner (optional)
│   ├── config/              # Test configuration
│   ├── controllers/         # Controller tests
│   ├── domain/              # Domain class tests
│   ├── entities/            # Entity tests
│   ├── gateways/            # Gateway tests
│   ├── lib/                 # Test-specific libraries
│   └── models/              # Model tests
│
├── vendor/                  # Composer dependencies (gitignored)
├── composer.json            # Composer dependencies
├── composer.lock            # Locked dependency versions
└── README.md                # This file
```

---

## Architecture Overview

KissMVC uses the **Front Controller** pattern combined with **MVC**
(Model-View-Controller). Here's how a request flows through the system:

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Request Flow                                │
└─────────────────────────────────────────────────────────────────────┘

    ┌───────────────┐
    │ User Browser  │
    └───────────────┘
            │
            │  HTTP Request: /page-with-parameters/abc/123
            ▼
   ┌─────────────────┐
   │  Web Server     │  (Nginx/Apache)
   │  (Document Root │   Routes all non-static requests to:
   │   = public/)    │   public/index.php
   └────────┬────────┘
            │
            ▼
   ┌─────────────────┐
   │  public/        │  1. Require Composer autoloader
   │  index.php      │  2. Define constants (BASE_PATH, APP_PATH)
   └────────┬────────┘  3. Load config: Application::loadConfiguration()
            │           4. Run: Application::run()
            ▼
   ┌─────────────────┐
   │  Application    │  - Check SSL requirement
   │  ::run()        │  - Set timezone
   └────────┬────────┘  - Instantiate FrontController
            │
            ▼
   ┌─────────────────┐
   │ FrontController │  - Parse URL segments
   │ ::routeRequest()│  - Call routeToController($segment)
   └────────┬────────┘  - Get Controller instance (or null → 404)
            │
            ▼
   ┌─────────────────┐
   │  routes.php     │  Returns a Controller based on route name.
   │  function       │  Example: 'default' → IndexControllerFactory::create()
   │  routeToCtrl()  │
   └────────┬────────┘
            │
            ▼
   ┌─────────────────┐
   │ ControllerFctry │  Factory instantiates the controller with
   │ ::create()      │  dependencies (models, services, etc.).
   └────────┬────────┘
            │
            ▼
   ┌─────────────────┐
   │   Controller    │  - setRequestParameters([...])
   │   (concrete)    │  - execute()  ← Page-specific logic here
   └────────┬────────┘  - renderLayout()
            │
            ▼
   ┌─────────────────┐
   │   Layout        │  - Includes header, footer, wrapper HTML
   │   (e.g.         │  - Calls $this->renderView()
   │   default.php)  │
   └────────┬────────┘
            │
            ▼
   ┌─────────────────┐
   │   View          │  - Page-specific HTML template
   │   (e.g.         │  - Accesses controller public methods/helpers
   │   index.php)    │  - May include partials
   └────────┬────────┘
            │
            ▼
      HTML Response → User Browser
```

### Key takeaways:

- **One controller per page**: Simple, predictable routing.
- **Factory pattern**: Controllers are instantiated via factories for clean
  dependency injection.
- **Separation of concerns**: Models handle business logic, views handle
  presentation, controllers coordinate.

---

## Core Concepts

### Routing

Routes are defined in `application/config/routes.php`. The router maps a
single URL segment to a controller.

**Example URL:**

```
http://myapp.com/page-with-parameters/abc/123/xyz
                 ^^^^^^^^^^^^^^^^^^^^^^ ^^^^^^^^^^^
                 Route name             Parameters
```

- **Route name**: `page-with-parameters`
- **Parameters**: `['abc', '123', 'xyz']`

**routes.php:**

```php
function routeToController(string $route): ?Controller
{
    static $routes = null;

    if ($routes === null) {
        $routes = [
            'default' => [IndexControllerFactory::class, 'create'],
            'page-with-parameters' => [PageWithParametersControllerFactory::class, 'create'],
        ];
    }

    if (!isset($routes[$route])) {
        return null; // 404
    }

    $factory = $routes[$route];
    return is_callable($factory) ? call_user_func($factory) : null;
}
```

**To add a new route:**

1. Create a controller class (e.g. `AboutController`).
2. Create a factory class (e.g. `AboutControllerFactory`).
3. Add an entry to the `$routes` array:
   ```php
   'about' => [AboutControllerFactory::class, 'create'],
   ```

---

### Controllers

Controllers are page-specific classes that:

- Configure page metadata (title, layout, view).
- Orchestrate models and services in `execute()`.
- Provide helper methods for views (e.g. `getMessage()`).

**Example: IndexController.php**

```php
<?php
declare(strict_types=1);

namespace Application\Controllers;

use KissMVC\Controller;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->setPageTitle('Home');
        $this->setLayout('default.php');
        $this->setView('index.php');
    }

    public function execute(): void
    {
        parent::execute(); // Intentional no-op; silences IDE warnings

        // Fetch data, call models, prepare for view
    }

    public function getMessage(): string
    {
        return 'Hello, World!';
    }
}
```

**Controller lifecycle:**

1. **Instantiation** (via factory)
2. **setRequestParameters(...)** (FrontController injects URL params)
3. **execute()** (your business logic runs here)
4. **renderLayout()** (layout is included; layout calls renderView())

---

### Controller Factories

Factories provide a single place to wire up dependencies for controllers.
They implement `ControllerFactoryInterface`.

**Example: IndexControllerFactory.php**

```php
<?php
declare(strict_types=1);

namespace Application\Controllers;

use KissMVC\ControllerFactoryInterface;

class IndexControllerFactory implements ControllerFactoryInterface
{
    public static function create()
    {
        // Optionally inject dependencies:
        // $model = new IndexModel($someService);
        // return new IndexController($model);

        return new IndexController();
    }
}
```

---

### Views and Layouts

**Layouts** wrap views with common HTML structure (header, footer, nav).

**Example: application/layouts/default.php**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($this->getPageTitle() ?? 'App') ?></title>
    <?php foreach ($this->getCssFiles() as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
</head>
<body>
    <div class="container">
        <?php $this->renderView(); ?>
    </div>

    <?php foreach ($this->getJavaScriptFiles() as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
```

**Views** contain page-specific HTML.

**Example: application/views/index.php**

```php
<?php /* @var $this \Application\Controllers\IndexController */ ?>

<h1>Welcome to KissMVC</h1>

<p><?= htmlspecialchars($this->getMessage()) ?></p>

<ul>
    <li>
        <a href="/page-with-parameters/abc/123/xyz">
            Page with Parameters
        </a>
    </li>
</ul>

<?php 
// Include a partial
$this->renderPartial('test.php', ['data' => 'Example Data']); 
?>
```

**Partials** are reusable snippets.

**Example: application/partials/test.php**

```php
<div class="alert">
    <p>Partial says: <?= htmlspecialchars($data['data'] ?? '') ?></p>
</div>
```

---

### Models and Domain Logic

KissMVC does **not** include a model or ORM layer. You are free to use:

- **Doctrine ORM**
- **Eloquent**
- **PDO** (raw SQL)
- **Custom table gateways** and entities

**Recommended structure:**

```
┌──────────────┐
│  Controller  │  Orchestrates the page lifecycle
└──────┬───────┘
       │ calls
       ▼
┌──────────────┐
│    Model     │  Page-specific business logic
└──────┬───────┘
       │ calls
       ▼
┌──────────────┐      ┌──────────────┐
│   Domain     │◄─────│   Gateway    │  Interacts with DB
│   Objects    │      │  (CRUD)      │
└──────────────┘      └──────┬───────┘
                             │
                             ▼
                        ┌──────────┐
                        │ Entities │  Represent DB rows
                        └──────────┘
```

**Example domain structure:**

- **Entities**: `User`, `Post`, `Comment` (data objects)
- **Gateways**: `UserGateway`, `PostGateway` (database access)
- **Domain**: `UserAuthenticator`, `PostValidator` (business rules)
- **Models**: `LoginModel`, `PostListModel` (page orchestration)

Place these in their respective `application/` subdirectories.

---

## Configuration

Configuration lives in `application/config/main.php`. It returns an array of
settings consumed by `Application::loadConfiguration()`.

**Example: application/config/main.php**

```php
<?php
declare(strict_types=1);

$basePath = dirname(dirname(__DIR__));

return [
    'environment' => getenv('APPLICATION_ENV') ?: 'development',

    'db' => [
        'name' => getenv('DB_NAME') ?: 'myapp',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],

    'secret_key' => getenv('SECRET_KEY') ?: 'change-me-in-production',
    'ssl_required' => filter_var(getenv('SSL_REQUIRED') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',

    'views_directory' => $basePath . '/application/views',
    'partials_directory' => $basePath . '/application/partials',
    'layouts_directory' => $basePath . '/application/layouts',
];
```

**Environment variables** (set in `.env`, server config, or shell):

```bash
export APPLICATION_ENV=production
export DB_NAME=myapp_prod
export DB_HOST=prod-db.example.com
export DB_USER=app_user
export DB_PASS=secure_password
export SECRET_KEY=a-long-random-string
export SSL_REQUIRED=true
export APP_TIMEZONE=America/New_York
```

---

## Adding a New Page

Follow these steps to add a new page (e.g. "About Us"):

### 1. Create the controller

**File: `application/Controllers/AboutController.php`**

```php
<?php
declare(strict_types=1);

namespace Application\Controllers;

use KissMVC\Controller;

class AboutController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle('About Us');
        $this->setLayout('default.php');
        $this->setView('about.php');
    }

    public function execute(): void
    {
        parent::execute();
        // Add page-specific logic here
    }

    public function getTeamMembers(): array
    {
        return ['Alice', 'Bob', 'Charlie'];
    }
}
```

### 2. Create the factory

**File: `application/Controllers/AboutControllerFactory.php`**

```php
<?php
declare(strict_types=1);

namespace Application\Controllers;

use KissMVC\ControllerFactoryInterface;

class AboutControllerFactory implements ControllerFactoryInterface
{
    public static function create()
    {
        return new AboutController();
    }
}
```

### 3. Add the route

**File: `application/config/routes.php`**

```php
use Application\Controllers\AboutControllerFactory;

// Inside the $routes array:
$routes = [
    'default' => [IndexControllerFactory::class, 'create'],
    'about' => [AboutControllerFactory::class, 'create'], // ← Add this
];
```

### 4. Create the view

**File: `application/views/about.php`**

```php
<?php /* @var $this \Application\Controllers\AboutController */ ?>

<h1>About Us</h1>

<h2>Team Members:</h2>
<ul>
    <?php foreach ($this->getTeamMembers() as $member): ?>
        <li><?= htmlspecialchars($member) ?></li>
    <?php endforeach; ?>
</ul>
```

### 5. Test

Visit: `http://localhost:8000/about`

---

## Server Configuration

### Nginx

**File: `/etc/nginx/sites-available/myapp`**

```nginx
server {
    listen 80;
    server_name myapp.local;

    root /var/www/myapp/public;
    index index.php index.html;

    access_log /var/log/nginx/myapp-access.log;
    error_log  /var/log/nginx/myapp-error.log;

    # Deny access to hidden files
    location ~ /\. { deny all; }

    # Serve static files directly
    location / {
        try_files $uri /index.php?$args;
    }

    # PHP-FPM handler
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # Optional: set environment variables
        fastcgi_param APPLICATION_ENV production;
    }
}
```

**Enable the site:**

```bash
sudo ln -s /etc/nginx/sites-available/myapp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### Apache

**File: `public/.htaccess`** (included by default)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**VirtualHost configuration:**

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot /var/www/myapp/public

    <Directory /var/www/myapp/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Optional: set environment variables
    SetEnv APPLICATION_ENV production

    ErrorLog ${APACHE_LOG_DIR}/myapp-error.log
    CustomLog ${APACHE_LOG_DIR}/myapp-access.log combined
</VirtualHost>
```

**Enable the site:**

```bash
sudo a2enmod rewrite
sudo a2ensite myapp
sudo systemctl reload apache2
```

---

## Development Workflow

### Local development with PHP's built-in server

```bash
cd website
php -S localhost:8000 -t public
```

Visit: `http://localhost:8000`

### Running tests

```bash
# Install dependencies
composer install

# Run PHPUnit (if configured)
vendor/bin/phpunit --colors=always

# Lint all PHP files
for f in $(find . -name "*.php"); do php -l "$f"; done
```

### Using the CI script

The repository includes `scripts/ci-run.sh` for automated linting and testing:

```bash
./scripts/ci-run.sh
```

This script:

- Changes to the repository root
- Runs `composer install`
- Lints all PHP files
- Runs PHPUnit tests (if present)

---

## Testing

Tests live in the `tests/` directory. Structure mirrors `application/`:

```
tests/
├── controllers/   # Controller tests
├── domain/        # Domain class tests
├── entities/      # Entity tests
├── gateways/      # Gateway tests
├── models/        # Model tests
└── config/        # Test configuration
```

**Example test (PHPUnit):**

```php
<?php
use PHPUnit\Framework\TestCase;
use Application\Controllers\IndexController;

class IndexControllerTest extends TestCase
{
    public function testGetMessage()
    {
        $controller = new IndexController();
        $this->assertEquals('Hello, World!', $controller->getMessage());
    }
}
```

---

## Contributing

Contributions are welcome! Please follow these guidelines:

1. **Fork the repository** and create a feature branch.
2. **Follow PSR-12** coding standards.
3. **Add tests** for new functionality.
4. **Document your changes** in code comments and this README if applicable.
5. **Run linting and tests** before submitting:
   ```bash
   ./scripts/ci-run.sh
   ```
6. **Submit a pull request** with a clear description.

---

## License

KissMVC is released under the **MIT License**. See `LICENSE` file for details.

```
Copyright (c) 2025 Joseph Fallon

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```

---

## Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/KissMVC/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/KissMVC/discussions)
- **Documentation**: This README and inline code documentation

---

**Built with ❤️ and the KISS principle.**
