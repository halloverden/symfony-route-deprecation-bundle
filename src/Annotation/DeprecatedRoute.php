<?php


namespace HalloVerden\RouteDeprecationBundle\Annotation;

use HalloVerden\RouteDeprecationBundle\EventListener\DeprecatedRouteListener;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD"})
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class DeprecatedRoute extends Route {
  private ?string $since;
  private ?string $until;
  private bool $enforce;

  /**
   * DeprecatedRoute constructor.
   *
   * @throws \Exception
   */
  public function __construct(
    $data = [],
    $path = null,
    string $name = null,
    array $requirements = [],
    array $options = [],
    array $defaults = [],
    string $host = null,
    $methods = [],
    $schemes = [],
    string $condition = null,
    int $priority = null,
    string $locale = null,
    string $format = null,
    bool $utf8 = null,
    bool $stateless = null,
    string $env = null,
    string $since = null,
    string $until = null,
    bool $enforce = false
  ) {
    if ($since = $data['since'] ?? $since) {
      $this->since = $defaults[DeprecatedRouteListener::DEPRECATION_ATTRIBUTE] = $since;
    }

    if ($until = $data['until'] ?? $until) {
      $this->until = $defaults[DeprecatedRouteListener::SUNSET_ATTRIBUTE] = $until;
    }

    if ($enforce = $data['enforce'] ?? $enforce) {
      $this->enforce = $defaults[DeprecatedRouteListener::ENFORCE_ATTRIBUTE] = $enforce;
    }

    if (Kernel::MAJOR_VERSION >= 6) {
      $path ??= $data;
      parent::__construct($path, $name, $requirements, $options, $defaults, $host, $methods, $schemes, $condition, $priority, $locale, $format, $utf8, $stateless, $env);
    } else {
      parent::__construct($data, $path, $name, $requirements, $options, $defaults, $host, $methods, $schemes, $condition, $priority, $locale, $format, $utf8, $stateless, $env);
    }


  }

  /**
   * @return string|null
   */
  public function getSince(): ?string {
    return $this->since;
  }

  /**
   * @return string|null
   */
  public function getUntil(): ?string {
    return $this->until;
  }

  /**
   * @return bool
   */
  public function isEnforce(): bool {
    return $this->enforce;
  }

}
