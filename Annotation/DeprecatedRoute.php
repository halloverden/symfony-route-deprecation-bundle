<?php


namespace HalloVerden\RouteDeprecationBundle\Annotation;

use HalloVerden\RouteDeprecationBundle\EventSubscriber\DeprecatedRouteSubscriber;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class DeprecatedRoute extends Route {

  /**
   * @Required
   *
   * @var string
   */
  private $name;

  /**
   * ISO-8601 string date yyyy-mm-ddThh:mm:ss+00:00
   * @Required
   *
   * @var string
   */
  private $since;

  /**
   * ISO-8601 string date yyyy-mm-ddThh:mm:ss+00:00
   * @Required
   *
   * @var string
   */
  private $until;

  /**
   * @var bool
   */
  private $enforce = false;

  /**
   * DeprecatedRoute constructor.
   * @param array $data
   *
   * @throws \Exception
   */
  public function __construct(array $data) {
    $this->name = $data['name'];
    $this->since = $data['since'];
    $this->until = $data['until'];

    unset($data['since']);
    unset($data['until']);

    if (isset($data['enforce'])) {
      $this->enforce = $data['enforce'];
      unset($data['enforce']);
    }
    if ($this->since) {
      $this->checkDateValidity($this->since);
      $data['defaults'][DeprecatedRouteSubscriber::DEPRECATION_ATTRIBUTE] = $this->since;
    }
    if($this->until) {
      $this->checkDateValidity($this->until);
      $data['defaults'][DeprecatedRouteSubscriber::SUNSET_ATTRIBUTE] = $this->until;
    }
    $data['defaults'][DeprecatedRouteSubscriber::ENFORCE_ATTRIBUTE] = $this->enforce;

    parent::__construct($data);
  }

  /**
   * @param string $date
   *
   * @throws \Exception
   */
  private static function checkDateValidity(string $date){
    //if no exception is thrown, date is valid
    DeprecatedRouteSubscriber::extractDate($date);
  }

}
