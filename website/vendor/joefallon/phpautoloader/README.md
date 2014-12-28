# Joe's PHP Autoloader

Joe's PHP Autoloader is a versatile and easy to use autoloader for PHP 5.3 and greater.
It provides the following features:

*   When autoloading a class, all of the include paths are searched automatically.
*   For maximum legacy supprt, non-namespaced classes are allowed.
*   Normal PSR-0 namespaced classes are supported.
*   Underscore namespaced (e.g. like the Zend Framework 1 or PEAR) classes are supported.
*   This autoloader is fast. No recursive directory searches are performed. 

## Requirements

The only requirement is PHP > 5.3.0. This is due to the use of
[namespaces](http://www.php.net/manual/en/language.namespaces.rationale.php).
Additionally, [Composer](https://getcomposer.org/) can be helpful.

## Installation

The easiest way to install Joe's Autoloader is with
[Composer](https://getcomposer.org/). Create the following `composer.json` file
and run the `php composer.phar install` command to install it.

```json
{
    "require": {
        "joefallon/phpautoloader": "*"
    }
}
```

## Usage

To use Joe's Autoloader, the following initialization steps are needed:

* Add the base directories where classes can be found to the include path.
* Call the `Autoloader::registerAutoload()` method to load the autoloader.
* Start using classes in your code.

```php
use JoeFallon\Autoloader;

// Define the include paths.
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
define('LIB_PATH',  BASE_PATH . '/lib');
define('VEND_PATH', BASE_PATH . '/vendor');

// Set the application include paths for autoloading.
set_include_path(get_include_path() . ':' . LIB_PATH . ':' . BASE_PATH);

// Require the Composer autoloader. Composer will handle its own class autoloading
// using its own autoloader.
require(VEND_PATH . '/autoload.php');

// Initialize Joe's Autoloader. Joe's Autoloader will handle autoloading any classes
// that are not autoloaded using Composer's built-in autoloader.
Autoloader::registerAutoLoad();
```

As long as namespaces are mapped to the folder structure within the directories
defined above, then autoloader will have no problems finding and loading the
classes.

For example, let's assume we want to load the class `Bar` that is within the file
named `Bar.php` contained within a folder `Foo`. Also, let's assume that the class
`Bar` is namespaced `\Foo\Bar`. This would give a file path of `LIB_PATH/Foo/Bar.php`. When
`new Bar();` is executed, the `Bar` class will be loaded (if is wasn't already).

Here is a visual depiction of the above example:

![Joe's Autoloader Example](http://i.imgur.com/7GjiNg2.png)
