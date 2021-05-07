<?php


namespace HalloVerden\RouteDeprecationBundle\Annotation;

use HalloVerden\RouteDeprecationBundle\EventSubscriber\DeprecatedRouteSubscriber;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class DeprecatedRoute extends Route {

  /**
   * @Required
   *
   * @var string
   */
  private $name;

  /**
   * String date yyyy-MM-dd
   * @Required
   *
   * @var string
   */
  private $since;

  /**
   * String date yyyy-MM-dd
   *
   * @var string|null
   */
  private $until;

  /**
   * @var bool
   */
  private $enforce;

  public function __construct (
    $data = [],
    $path = null,
    string $name = null,
    array $requirements = [],
    array $options = [],
    array $defaults = [],
    string $host = null,
    array $methods = [],
    array $schemes = [],
    string $condition = null,
    int $priority = null,
    string $locale = null,
    string $format = null,
    bool $utf8 = null,
    bool $stateless = null,
    string $since = null,
    string $until = null,
    bool $enforce = false
  ) {
    var_dump($data);
    $this->name = $data['name'] ?? $name;
    $this->since = $data['since'] ?? $since;
    $this->until = $data['until'] ?? $until;
    $this->enforce = $data['enforce'] ?? $enforce;

    unset($data['since']);
    unset($data['until']);
    unset($data['enforce']);


    if ($this->since) {
      $data['defaults'][DeprecatedRouteSubscriber::DEPRECATION_ATTRIBUTE] = $this->since;
    }
    if($this->until) {
      $data['defaults'][DeprecatedRouteSubscriber::SUNSET_ATTRIBUTE] = $this->until;
    }
    $data['defaults'][DeprecatedRouteSubscriber::ENFORCE_ATTRIBUTE] = $this->enforce;

    parent::__construct($data, $path, $name, $requirements, $options, $defaults, $host, $methods, $schemes, $condition, $priority, $locale, $format, $utf8, $stateless);
  }

}
