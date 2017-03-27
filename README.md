KissMVC
=======

By [Joe Fallon](http://blog.joefallon.net/)

KissMVC is a [Keep-It-Simple-Stupid](http://en.wikipedia.org/wiki/KISS_principle) 
and fast bare-bones MVC framework.

> "Make everything as simple as possible, but not simpler." -- Albert Einstein

> "Simplicity is the ultimate sophistication." -- Leonardo da Vinci

> "Perfection (in design) is achieved not when there is nothing more to add, but rather when there is nothing more to take away." -- Antoine de Saint-Exupery

> "Simplicity is the soul of efficiency." -- Austin Freeman

> "Simplicity is prerequisite for reliability." --Edsger W.Dijkstra

> "Fools ignore complexity; pragmatists suffer it; experts avoid it; geniuses remove it." -- Alan Perlis

KissMVC includes the following features:

*   A standard folder structure for your application.
*   A minimum amount of overhead is imposed by the framework. It is assumed additional 
    functionality can either be created be created by the developer or included via 
    Composer.
*   Routing is kept extremely simple and quick.
*   All publicly accessible assets are located in a single "public" directory for
    maximum safety.
*   KissMVC is extremely simple to fully understand and get up to speed
    with. No more than 20-25 minutes should be required to fully understand
    KissMVC and all of its' code.
*   KissMVC promotes good software engineering and web application development
    practices by promoting the use of database migrations, table gateways,
    models, Controllers, domain specific classes, layouts, views, view partials,
    and all of the other goodies you may like.
*   The amount of framework code is kept to a minimum. We assume you have your
    favorite ORM or logging library and plan to use that.

Installation
------------

Since KissMVC is both a small library and a folder structure for organizing
your project it is not packaged as a [Composer](https://getcomposer.org/)
library. Therefore, to install it go ahead and click the "Download Zip" button on the
right side of the page.

Once it is unzipped, copy the contents of the "website" folder to your Git
(or any VCS) repo and commit it. Don't worry about checking in a large amount
of library code because KissMVC is fully implemented using only five classes.


Framework Architecture
----------------------

KissMVC uses a variation of the
[Front Controller](http://www.oracle.com/technetwork/java/frontcontroller-135648.html)
design pattern. The
[Model-View-Controller](http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93Controller)
architectural pattern is used as well. Here is an overview of the architecture:

![KissMVC Architecture Overview](http://i.imgur.com/yQQARZN.png)

When a user visits a page in our application (e.g. /view-posts) there are
several steps needed to create the page and deliver it to the user. First, unless
the request is for a static file (e.g. *.css, *.jpg, *.js) the index.php file within
the public folder is executed.

The index file is responsible for instantiating the classes that start the application.
Once the classes are instantiated, the `run()` method of the `Application` class is
called. This method verifies that the site is using SSL if it is configured to,
sets the default timezone, and then instantiates the `FrontController` class to route 
the request.

The `FrontController` class splits apart the URL to determine which Controller to
instantiate. Unlike other frameworks, this one uses one Controller per page. Here
is an example of a URL:

```
http://myapplication/page-with-parameters/abc/123/xyz
```

There are several parts to this URL. Several of them are important to the front
Controller. First, `page-with-parameters` specifies the Controller to instantiate
to handle this request. Second, `abc`, `123`, and `xyz` are URL parameters that
get passed to the controller and are available for immediate use. From this
example the Controller `PageWithParametersController` would be instantiated.

All Controllers derive from the base class `Controller`. The base class Controller
provides several useful functions that are useful for most pages
(e.g. `getPageTitle()`). Typically, the model for the Controller will be passed
into the Controller constructor (i.e. this is dependency injection) as a parameter.

After the Controller for that particular page is constructed, two methods are called
in succession. First, `execute()` is called. This is a function is where all logic
needed to determine what to do with the request should be placed. For example,
let's assume a form was posted. The contents of the submit button post variable
would be checked to determine if it is a submission or fresh display of the form.
After the `execute()` function has completed execution, the `renderLayout()` method
is called. The `renderLayout()` method loads the layout.

Once the layout is loaded, the layout will call `renderView()` on the Controller to 
load the view that is specific to that page. It is assumed that each page has a single
view. However, each view can include as many "view partials" as needed for maximum
view code reusability.

Views should have a one-to-one correspondence with the Controllers. Models should also
have a one-to-one correspondence with the Controllers. There are four type of classes
that models interact with besides Controllers. 

The first type are the domain objects. Domain objects are classes containing business 
logic that is shared among many models and other domain objects. The second are 
the entities. Entities are objects that represent a row of a table in a database 
(e.g. post). The third are the table gateways that store and fetch entities from 
the database. The last, and not depicted, are any objects from third party vendor 
code (e.g. monolog).

Table gateways, entities, models, and domain objects are not included in this
framework. Instead it is left to the application programmer to decide on the
best way to implement the persistence layer. Many people recommend 
[Doctrine 2](http://www.doctrine-project.org/projects/orm.html).

Here is an example of the relationship among several example classes in an application:

![KissMVC - Several Stacks](http://i.imgur.com/QXA1vYq.png)

Directory Structure
-------------------

Here is the directory structure of a KissMVC application:

```
WebsiteName
  |
  +--> application
  |     |
  |     +--> config
  |     |       |
  |     |       +--> main.php
  |     |       |
  |     |       +--> routes.php    
  |     |
  |     +--> domain
  |     |
  |     +--> entities
  |     |
  |     +--> layouts
  |     |
  |     +--> models
  |     |
  |     +--> controllers
  |     |
  |     +--> gateways
  |     |
  |     +--> partials
  |     |
  |     +--> views
  |     |
  |     +--> Bootstrapper.php
  |
  +--> db
  |     |
  |     +--> migrations
  |
  +--> lib
  |     |
  |     +--> KissMVC
  |            |
  |            +--> Application.php
  |            |
  |            +--> AutoLoader.php
  |            |
  |            +--> FrontController.php
  |            |
  |            +--> Controller.php
  |            |
  |            +--> ControllerFactoryInterface.php
  |
  +--> public
  |      |
  |      +--> css
  |      |
  |      +--> img
  |      |
  |      +--> js
  |      |
  |      +--> index.php
  |      |
  |      +--> .htaccess
  |
  +--> tests
         |
         +--> classes
         |
         +--> entities
         |
         +--> models
         |
         +--> controllers
         |
         +--> gateways
         |
         +--> config
         |
         +--> lib
         |
         +--> index.php
```

Typically, `WebsiteName` is changed to match the name of the application (e.g. 
MyFaceSpace).

![Folder Structure Overview](http://i.imgur.com/jBn8bxw.png)

*   **WebsiteName/application/domain** - Domain classes are classes that contain
logic that is specific to the problem domain the application serves and that will be used 
by several models. Typically, they will be used by models, other domain classes. 
Additionally, they may call other domain classes and table gateways. They should never 
call models or Controllers.
*   **WebsiteName/application/entities** - Entities are classes that represent a single
row within a database. They may represent a single row from more than one table if
a SQL join is used. Entities may be passed all over the application. They may also include
logic to validate themselves. Typically, they consist of simple bundles of data.
*   **WebsiteName/application/layouts** - A layout represents an overall visual
structure for a page. The call to `renderView()` will be contained within the layout.
*   **WebsiteName/application/models** - Models contain page specific logic and
processing.
*   **WebsiteName/application/controllers** - Controllers act as the middle-man between
the view and the model. They also define the overall page behavior (e.g. redirect
to another page on authorization failure?). Controllers present that data from the
model to the view and also assist with formatting (e.g. converting a number to a proper 
currency format).

![Controllers](http://i.imgur.com/4vSQAK5.png)

*   **WebsiteName/application/gateways** - Table gateways provide an interface
to the persistence layer. Typically, 
[CRUD](http://en.wikipedia.org/wiki/Create,_read,_update_and_delete) 
(i.e. create, retrieve, update, delete) methods are placed in these classes. All 
database interaction must go through the table gateways.
*   **WebsiteName/application/partials** - View partials are reusable chunks
of HTML can can be reused in multiple locations on single web page or across many
web pages.
*   **WebsiteName/application/views** - The view contains the HTML for a single page.
The view does not contain the layout (e.g. body tag or container). It typically
has a one-to-one correspondence with the Controllers (i.e. one view per controller class).
*   **WebsiteName/application/Bootstrapper.php** - The `Bootstrapper` class
contains a single method called `bootstrap()` where all of the application specific 
initialization is placed. For example, database connection creation code can be placed 
here. Typically, all of the initialized objects within the Bootstrapper class are stored 
in the registry for easy access anywhere in the application. 

![Controllers](http://i.imgur.com/cmXjQAo.png)

*   **WebsiteName/application/config/main.php** - Main application config. Database 
credentials are kept in this file along with any application specific application 
configuration except for the routes.
*   **WebsiteName/application/config/routes.php** - The routes file contains single 
method that returns a Controller based on the first URL parameter after the domain. 
If no URL parameter is given, then the default (index) Controller is returned.

![Application Configuration](http://i.imgur.com/3sUuTr1.png)

*   **WebsiteName/db** - All database scripts are contained here.
*   **WebsiteName/db/migrations** - The migrations folder contains all of your migration
files.

![Database Scripts and Migrations](http://i.imgur.com/Qzug1Fl.png)

*   **WebsiteName/lib** - Lib contains the framework library and any other third-party
libraries that do not have Composer support.
*   **WebsiteName/public** - The public folder should be the only folder that is
accessible to the public and should be set as the document root of the site.
*   **WebsiteName/public/css** - CSS Files
*   **WebsiteName/public/img** - Image Files
*   **WebsiteName/public/js** - JavaScript Files
*   **WebsiteName/public/index.php** - All web requests that are not for static files
are routed through this file.

![Document Root](http://i.imgur.com/0ArGVvY.png)

*   **WebsiteName/tests** - All unit and integration tests go here.
*   **WebsiteName/tests/domain** - Tests for domain classes.
*   **WebsiteName/tests/entities** - Tests for entity classes.
*   **WebsiteName/tests/models** - Tests for models.
*   **WebsiteName/tests/controllers** - Tests for Controllers.
*   **WebsiteName/tests/gateways** - Tests for table gateways.
*   **WebsiteName/tests/config** - Test configuration.
*   **WebsiteName/tests/lib** - Test specific libraries.
*   **WebsiteName/tests/index.php** - Test runner.

![Document Root](http://i.imgur.com/JkyqXRa.png)

Routing
-------

Here is an example `routes.php` file:

```php
function routeToController($route)
{
    switch($route)
    {
        case 'default':
            return IndexControllerFactory::create();
        case 'page-with-parameters':
            return PageWithParametersControllerFactory::create();
        case 'view-items':
            return ViewItemsControllerFactory::create();
        default:
            return null;
    }
}
```

Controllers
----------

Here is an example default Controller with no URL parameters:

```php
use KissMVC\Controller;

class IndexController extends Controller
{
    public function  __construct()
    {
        parent::__construct();

        $this->setPageTitle('Index');
        $this->setLayout('default.php');
        $this->setViewFileName('index.php');
    }

    public function execute() { }

    public function getMessage()
    {
        return 'Hello, World!';
    }
}
```

Here is an example Controller that uses URL parameters:

```php
use KissMVC\Controller;

class PageWithParametersController extends Controller
{
    public function  __construct()
    {
        parent::__construct();

        $this->setPageTitle('Page with Parameters');
        $this->setLayout('default.php');
        $this->setViewFileName('page-with-parameters.php');
    }

    public function execute() { }
}
```

Controller Factories
-------------------

Here is an example Controller factory:

```php
use KissMVC\ControllerFactoryInterface;

class IndexControllerFactory implements ControllerFactoryInterface
{
    public static function create()
    {
        return new IndexController();
    }

}
```

Layouts
-------

Here is an example layout:

```
Default Layout

<?php $this->renderView(); ?>
```

Views
-----

Here is an example view:

```
<?php /* @var $this IndexController */ ?>
<pre>

Main View:

<?php echo $this->getMessage(); ?>

</pre>

<ul>
    <li>
        <a href="<?= $this->getBaseUrl(); ?>/page-with-parameters/abc/123/xyz">
            Page with Parameters
        </a>
    </li>
</ul>



<?php $this->renderPartial('test.php', array('data' => 'View Partial Data')); ?>
```

View Partials
-------------

Here is an example view partial:

```
<pre>

View Partial:

<?php echo '$data[data] = ' . $data['data']; ?>

</pre>
```

Nginx Config
-------------

Here is an example nginx configuration. It works identically to the Zend Framework 1 way of
handling requests.

```
server {
    #listen   80; ## listen for ipv4; this line is default and implied
    #listen   [::]:80 default ipv6only=on; ## listen for ipv6

    root /var/www/KissMVC/website/public;
    index index.php index.html index.htm;

    server_name kissmvc.lemp16.joefallon.net;
    autoindex off;

    access_log /var/log/nginx/development-access.log;
    error_log  /var/log/nginx/development-error.log;

    location ~ /\. { access_log off; log_not_found off; deny all; }
    location ~ ~$  { access_log off; log_not_found off; deny all; }

    location = /favicon.ico {
        try_files $uri =204;
    }

    location / {
        try_files $uri /index.php?$args;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param APPLICATION_ENV development;
    }
}
```
