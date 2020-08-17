Route Deprecation Bundle
==============================
The Route Deprecation Bundle provides annotations to deprecate routes in your Symfony application. It implements the IETF draft  of [The Deprecation HTTP Header Field](https://tools.ietf.org/id/draft-dalal-deprecation-header-03.html).  

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require halloverden/symfony-route-deprecation-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require halloverden/symfony-route-deprecation-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    HalloVerden\RouteDeprecationBundle\HalloVerdenRouteDeprecationBundle::class => ['all' => true],
];
```

## Usage

You can deprecate a route in any route definition (annotation, yaml, xml, php, what have you) by passing three values to the `defaults` option:
 
- `_deprecated_since` is a `string ("dd-mm-yyyy")` that defines the moment in which a route becomes deprecated. The header `Deprecation` will be set on the response, like so:
 `Deprecation: date="Wed, 01 Jan 2020 00:00:00 GMT"`.
 
- `_deprecated_until` is a `string ("dd-mm-yyyy")` that defines the moment in which a route becomes expired. The header `Sunset` will be set on the response, like so:
  `Sunset: date="Mon, 01 Jun 2020 00:00:00 GMT"`.
  
- `_enforce_deprecation` is a `boolean` that makes the route inaccessible after the `_deprecated_until` date. If you try to access a route where this option is set to `true` and the current date is greater than the `_deprecated_until` date, a `GoneHttpException` is thrown.

You can deprecate a method / endpoint in a controller, or the controller itself (to deprecate all methods / endpoints in the controller).

Example using annotations:

```php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/", defaults={"_deprecated_since"="01-01-2020", "_deprecated_until"="01-06-2020", "_enforce_deprecation"=false)
 */
class TestController extends AbstractController {
  /**
   * @Route("/test", methods={"GET"}, name="test", defaults={"_deprecated_since"="01-01-2020", "_deprecated_until"="01-06-2020", "_enforce_deprecation"=true)
   */
  public function test() {
    // Controller method stuff
  }
}
```

### @DeprecatedRoute annotation
The bundle also ships with a handy new annotation, called `@DeprecatedRoute`, and is used like so:

```php
namespace App\Controller;

use HalloVerden\RouteDeprecationBundle\Annotation\DeprecatedRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @DeprecatedRoute("/", since="01-01-2020", until="01-06-2020", enforce=false)
 */
class TestController extends AbstractController {
  /**
   * @DeprecatedRoute("/test", methods={"GET"}, name="test", since="01-01-2020", until="01-06-2020", enforce=true)
   */
  public function test() {
    // Controller method stuff
  }
}
```

It's just a convenience annotation that behind the curtains makes use of `Symfony\Component\Routing\Annotation\Route`, but it reads nice. It can be mixed with the `@Route` annotation. 

---

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)
