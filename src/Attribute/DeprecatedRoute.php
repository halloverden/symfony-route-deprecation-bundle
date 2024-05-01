<?php

namespace HalloVerden\RouteDeprecationBundle\Attribute;

use HalloVerden\RouteDeprecationBundle\Helper\DateTimeHelper;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class DeprecatedRoute {
  /**
   * @param string      $since                     Defines the moment in which a route becomes deprecated (will set the `Deprecation` header in the reponse)
   * @param string|null $sunset                    Defines the moment in which a route becomes expired (will set the `Sunset` header in the reponse)
   * @param bool        $enforce                   Makes the route inaccessible after the sunset date (default is false). Will throw a `GoneHttpException` if sunset date is hit
   * @param string|null $name                      Name of the route to deprecate. If not specified, all routes (on that controller) will be deprecated
   * @param string|null $deprecationDateTimeFormat Format of the date time used in the Deprecation header. default is @U (as per the IETF draft)
   * @param string|null $sunsetDateTimeFormat      Format of the date time used in the Sunset header. default is D, d M Y H:i:s \G\M\T (as per the Sunset RFC)
   * @param string|null $deprecationLink           The link used in the `Link` header for deprecation
   * @param string|null $sunsetLink                The link used in the `Link` header for sunset.
   */
  public function __construct(
    public string  $since,
    public ?string $sunset = null,
    public bool    $enforce = false,
    public ?string $name = null,
    public ?string $deprecationDateTimeFormat = null,
    public ?string $sunsetDateTimeFormat = null,
    public ?string $deprecationLink = null,
    public ?string $sunsetLink = null,
  ) {
  }

  /**
   * @return \DateTimeInterface|null
   * @throws \Exception
   */
  public function getSince(): ?\DateTimeInterface {
    return $this->since ? DateTimeHelper::getDateTimeFromString($this->since) : null;
  }

  /**
   * @return \DateTimeInterface|null
   * @throws \Exception
   */
  public function getSunset(): ?\DateTimeInterface {
    return $this->sunset ? DateTimeHelper::getDateTimeFromString($this->sunset) : null;
  }

}
