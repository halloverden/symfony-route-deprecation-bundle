Route Deprecation Bundle
==============================
The Route Deprecation Bundle provides annotations to deprecate routes in your application. It implements the IETF draft  of [The Deprecation HTTP Header Field](https://tools.ietf.org/id/draft-dalal-deprecation-header-03.html).  

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

The `@DeprecatedRoute` annotation extends `Symfony Route`, and lets you mark an endpoint as deprecated and/or expired (and optionally make the endpoint inaccessible) by exposing these options: 
- `since` is a date value `(dd-mm-yyyy)` that defines the moment in which a route becomes deprecated. If the current date is equal or greater than the value of since, the header `Deprecation` will be set on the response, like so:
 `Deprecation: date="Wed, 01 Jan 2020 00:00:00 GMT"`.
 
- `until` is a date value `(dd-mm-yyyy)` that defines the moment in which a route becomes expired. If the current date is equal or greater than the value of until, the header `Sunset` will be set on the response, like so:
  `Sunset: date="Mon, 01 Jun 2020 00:00:00 GMT"`.
  
- `enforce` is a boolean value that defines if the route must become unresponsive after the `until` date. In this case a `GoneHttpException` is thrown with HTTP Status 410 Gone.

```php
namespace App\Controller;

use HalloVerden\RouteDeprecationBundle\Annotation\DeprecatedRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestController extends AbstractController {
  /**
   * @DeprecatedRoute("/test", methods={"GET"}, name="test", since="01-01-2020", until="01-06-2020", enforce=false)
   */
  public function test() {
    // Controller method stuff
  }
}
```
 
The annotation can also be used for the controller:

```php
namespace App\Controller;

use HalloVerden\RouteDeprecationBundle\Annotation\DeprecatedRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @DeprecatedRoute("/", since="01-01-2020", until="01-06-2020", enforce=true)
 */
class TestController extends AbstractController{

  /**
   * @Route("/test")
   */
  public function test() {
    // Controller method stuff
  }
}

```

---
**NOTE**

In case of conflict between the `@DeprecatedRoute` annotations of class and method, the method annotation takes precedence. 

---

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)
