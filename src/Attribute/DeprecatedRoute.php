<?php

namespace HalloVerden\RouteDeprecationBundle\Attribute;

use HalloVerden\RouteDeprecationBundle\Helper\DateTimeHelper;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class DeprecatedRoute {

  /**
   * DeprecatedRoute constructor.
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
