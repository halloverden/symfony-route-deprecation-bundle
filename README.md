Route Deprecation Bundle
==============================
The Route Deprecation Bundle provides tools to deprecate routes in your Symfony application. It implements the IETF draft  of [The Deprecation HTTP Header Field](https://datatracker.ietf.org/doc/html/draft-ietf-httpapi-deprecation-header).  

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

You can deprecate any route with the `DeprecatedRoute` annotation.

- `since` (required) - is a `string ("yyyy-mm-dd")` that defines the moment in which a route becomes deprecated. The header `Deprecation` will be set on the response, like so: `Deprecation: @1672531200`.
- `sunset` (optional) - is a `string ("yyyy-mm-dd")` that defines the moment in which a route becomes expired. The header `Sunset` will be set on the response, like so: `Sunset: Mon, 01 Jun 2020 00:00:00 GMT`
- `enforce` (optional) - is a `boolean` that makes the route inaccessible after the `sunset` date (default is `false`). If you try to access a route where this option is set to `true` and the current date is greater than the `sunet` date, a `GoneHttpException` is thrown.
- `name` (optional) - The name of the route to deprecate. If not specified, all routes (on that controller) will be deprecated.
- `deprecationDateTimeFormat` (optional) - The format of the date time used in the Deprecation header. default is `@U` (as per the IETF draft).
- `sunsetDateTimeFormat` (optional) - The format of the date time used in the Sunset header. default is `D, d M Y H:i:s \G\M\T` (as per the Sunset RFC)
- `deprecationLink` (optional) - The link used in the `Link` header for deprecation.
- `sunsetLink` - (optional) - The link used in the `Link` header for sunset.

you can also deprecate any route with route parameters.

Example using the attribute:

```php
<?php

namespace App\Controller;

use HalloVerden\RouteDeprecationBundle\Attribute\DeprecatedRoute;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route(path: self::PATH, name: self::NAME, methods: ['GET'])]
#[DeprecatedRoute(since: '2023-01-01')]
class GetHealthzController extends BaseController {
  const PATH = '/healthz';
  const NAME = 'healthz_get';

  /**
   * @return Response
   */
  public function __invoke(): Response {
    return new Response('ok');
  }

}
```

Example using route parameters in routes.yaml (you can also use xml, php and the route annotation):

```yaml
# config/routes.yaml
lol_healthz:
    path: /healthz
    controller: App\Controller\GetHealthzController
    defaults:
        _deprecated_since: '2024-01-01'
        _deprecated_sunset: '2024-02-01'
        _deprecated_enforce: false
        _deprecated_deprecation_date_time_format: '@U'
        _deprecated_sunset_date_time_format: 'D, d M Y H:i:s \G\M\T'
        _deprecated_deprecation_link: 'https://example.com/deprecation'
        _deprecated_sunset_link: 'https://example.com/sunset'
```

config options (optional):
```yaml
hallo_verden_route_deprecation:
    deprecation:
        dateTimeFormat: '@U'
        link: 'https://example.com/deprecation'
    sunset:
        dateTimeFormat: 'D, d M Y H:i:s \G\M\T'
        link: 'https://example.com/sunset'
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)
