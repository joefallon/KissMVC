Here is a clean, highly scannable, and structured breakdown of the **KissMVC
** repository state. The architecture, request lifecycle, and critical implementation risks have been organized for maximum readability.

---

# KissMVC: Project State & Architecture

## Purpose Overview

**KissMVC** is a lightweight, explicit PHP MVC skeleton/framework designed for **PHP 7.4+
**. It is structured as a "clone-and-modify" codebase rather than a standalone, distributable Composer package.

It explicitly bundles:

* A minimal framework core (`website/lib/KissMVC/`)
* An example application skeleton (`website/src/`)
* A public document root (`website/public/`)
* Structural placeholders for database migrations, tests, assets, and domain logic.

---

## Repository Architecture

The root directory serves as a shallow wrapper around the active application inside the `website/` directory.

```text
.
├── README.md
├── LICENSE
├── .gitignore
├── diff.diff
└── website/
    ├── composer.json
    ├── composer.lock
    ├── db/
    ├── lib/
    ├── public/
    ├── src/
    ├── tests/
    └── vendor/

```

### Composer & Runtime Profile

* **Active Root:** All active application and dependency management happens inside `/website`. There is **no
  ** root-level `composer.json`.
* **Dependencies:** `website/composer.json` defines the project as `joefallon/kissmvc` (type:
  `project`). It enforces **PHP >= 7.4** but currently requires zero external runtime or development packages.
* **Autoloading Strategy (PSR-4):**
* `KissMVC\` $\rightarrow$ Maps to `lib/KissMVC/` (Framework Core)
* `\` (Fallback Root) $\rightarrow$ Maps to `src/` (Application Code)

---

## Main Request Lifecycle

When a web request hits the server, execution follows a strictly sequential, linear pipeline:

```text
[Browser Request] 
       │
       ▼
 1. website/public/index.php (Sets environment, base constants, & loads files)
       │
       ▼
 2. website/src/Config/main.php (Loads configurations into Application registry)
       │
       ▼
 3. Bootstrapper::bootstrap() (Hook for app-layer initialization)
       │
       ▼
 4. Application::run() (Validates SSL/Timezone, hands off to FrontController)
       │
       ▼
 5. FrontController::routeRequest() (Parses URI, invokes matching Controller Factory)
       │
       ▼
 6. Controller::execute() -> renderLayout() (Executes page logic and outputs views)

```

### Execution Breakdown:

1. **Bootstrap & Environment Entry:** `website/public/index.php` checks for
   `APPLICATION_ENV`. If missing, it defaults to `production` and disables error display. It defines
   `BASE_PATH` (`website/`) and `APP_PATH` (`website/src/`).
2. **File Ingestion:** Core dependencies are explicitly required in this order:

* `lib/KissMVC/Application.php`
* `src/Bootstrapper.php`
* `vendor/autoload.php`


3. **Configuration Registry:** Settings are pulled from `src/Config/main.php`.
4. **App Initialization:** `Bootstrapper::bootstrap()` executes (currently empty).
5. **Front Execution:** `Application::run()` boots the
   `FrontController`, evaluates global rules (SSL enforcement, timezones), parses the request URI, matches the route, and triggers the target controller.

---

## Core Framework vs. Application Skeleton

### 1. Framework Core (`website/lib/KissMVC/`)

The entire framework engine consists of just four explicit files:

* **`Application.php`:** Acts as the global configuration registry (`setRegistryItem()`,
  `getRegistryItem()`). Evaluates SSL redirect demands (
  `ssl_required`) and sets the application runtime timezone.
* **`FrontController.php`:** The main router. It loads `APP_PATH/Config/routes.php`, parses
  `$_SERVER['REQUEST_URI']` into URL segments, maps the *first* segment to a controller using the global
  `routeToController()` function, passes subsequent segments as parameters, executes the controller, and wraps output in a layout template. It also traps exceptions to show 404/500 pages.
* **`Controller.php`:
  ** The base class for all UI endpoints. Manages layout/view target paths, page titles, CSS/JS asset arrays, and URL parameters. Provides template extraction engines (
  `renderLayout()`, `renderView()`, `renderPartial()`) executing within the context of the controller ($this).
* **`ControllerFactoryInterface.php`:** A strict structural contract specifying a static
  `create(): Controller` factory method. Routing maps to factories instead of direct class instantiation.

### 2. Application Space (`website/src/`)

The application layer contains configuration, controllers, templates, and structural placeholders for business logic.

| Directory / File                               | Description / Current State                                                           |
|------------------------------------------------|---------------------------------------------------------------------------------------|
| `Bootstrapper.php`                             | Currently an empty class structure; meant for registering DB connections or services. |
| `Config/`                                      | Contains `main.php` (global settings) and `routes.php` (routing table mappings).      |
| `Controllers/`                                 | Houses concrete controllers and matching structural factories.                        |
| `Layouts/`, `Views/`, `Partials/`              | PHP template layout wrappers, raw page views, and reusable partial components.        |
| `Domain/`, `Entities/`, `Gateways/`, `Models/` | Purely structural convention placeholders. **Contains no implementation files.**      |

---

## Routing & Page Implementations

Routing is strictly **one segment deep
**. The initial URL segment resolves the controller; all subsequent segments are passed down raw as arrays of parameters.

### Current Route Mapping Table

The global function `routeToController(string $route)` in
`website/src/Config/routes.php` resolves requests using the following logic:

| Request URI               | Resolved Route         | Factory Invocation                              | Target View                      |
|---------------------------|------------------------|-------------------------------------------------|----------------------------------|
| `/` or Empty              | `default`              | `IndexControllerFactory::create()`              | `Views/index.php`                |
| `/page-with-parameters/*` | `page-with-parameters` | `PageWithParametersControllerFactory::create()` | `Views/page-with-parameters.php` |
| *Anything Else*           | `null`                 | None (Triggers `FrontController` 404 handler)   | `Views/404.php`                  |

### URL Parameter Capture Example

A web request targeting `/page-with-parameters/abc/123/xyz` performs the following steps:

1. Resolves to `page-with-parameters` and builds the respective controller.
2. The remaining segments
   `['abc', '123', 'xyz']` are captured and stored inside the controller instance's request parameters array, accessible during
   `execute()`.

---

## Critical Risks & Architectural Ambiguities

> ⚠️ **Critical Bug: Configuration Environment Assignment**
> Inside `website/src/Config/main.php`, the environment setup uses the following syntax:
> ```php
> $config = ['environment'] ?? $environment;
> 
> ```
>
>
> **Problem:** This does *not* assign the
`$environment` variable value to an associative array key. Instead, it creates an indexed array containing the literal string string
`'environment'` at index
`0`. The null coalescing operator here does nothing. This breaks configuration values relying on environment parsing.

> ⚠️ **Broken Test Harness Configuration**
> While
`website/tests/` provides logical subdirectories, the testing capability is currently completely inactive:
> * `website/tests/bootstrap.php` has its path initialization block (`BASE_PATH`, `APP_PATH`,
    `TESTS_PATH`) completely commented out, yet references
    `BASE_PATH` later down the script to find Composer's autoloader.
> * There is no `phpunit.xml` or `phpunit.xml.dist` setup.
> * There is no `phpunit` executable inside `website/vendor/bin` because `composer.json` declares zero
    `dev` dependencies.
>
>

> ⚠️ **Missing Error Templates**
> `FrontController` relies on a fall-back constant to display a
`500.php` page if controller rendering or app execution encounters an unhandled exception. The filesystem inventory confirms
`404.php` exists, but **`500.php` is entirely missing
**. An unhandled application crash will result in secondary template rendering failures.

> ⚠️ **Environment Configuration Conflicts**
> There is a contradictory fallback loop regarding environment defaults:
> * `public/index.php` defaults a missing `APPLICATION_ENV` value to **`production`** and silences errors.
> * `Config/main.php` defaults its internal check to **`development`
    ** if constants or env variables are absent.
> * Because
    `public/index.php` runs first, normal web traffic will default to production rules, while alternative execution contexts might run under development configurations.
>
>

> ⚠️ **Namespace Mismatches in Views**
> Concrete controllers belong to the root
`Controllers` namespace. However, inline docblock documentation annotations within view templates reference
`Application\Controllers\IndexController`. These template comments are stale and do not reflect actual code structure.

---

## Practical Action Plan & Starting Points

### To Extend System Behavior (Adding a Page)

1. Write a concrete controller class extending `KissMVC\Controller` within `website/src/Controllers/`.
2. Configure layout variables, target view templates, and page metadata inside your new controller's constructor.
3. Write a companion factory class implementing `KissMVC\ControllerFactoryInterface`.
4. Inject a new conditional block targeting your factory inside `routeToController()` within
   `website/src/Config/routes.php`.
5. Create the matching presentation template within `website/src/Views/`.

### To Fix System Integrity & Environment Bugs

1. Refactor
   `website/src/Config/main.php` to properly store the environment variable into the array context (e.g.,
   `$config['environment'] = $environment;`).
2. Standardize fallback rules between `public/index.php` and
   `Config/main.php` to avoid environment state confusion.
3. Generate a proper `500.php` template file inside `website/src/Views/`.

### To Stabilize Test Automation

1. Add `phpunit/phpunit` to your `composer.json` under a `require-dev` block and run a composer update.
2. Un-comment and fix the path constant declarations inside `website/tests/bootstrap.php`.
3. Add a foundational `phpunit.xml` configuration file at the application root (`website/`).
