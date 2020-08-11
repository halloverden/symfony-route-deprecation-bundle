<?php


namespace HalloVerden\RouteDeprecationBundle\Annotation;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class DeprecatedRoute extends Route {
  const HTTP_DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

  /**
   * @Required
   *
   * @var string
   */
  private $name;

  /**
   * String date dd-MM-yyyy
   * @Required
   *
   * @var string
   */
  private $since;

  /**
   * String date dd-MM-yyyy
   * @Required
   *
   * @var string
   */
  private $until;

  /**
   * @var bool
   */
  private $enforce = false;

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

    parent::__construct($data);
  }

  /**
   * @return \DateTime
   * @throws \Exception
   */
  public function getSince(): \DateTime {
    return new \DateTime($this->since);
  }

  /**
   * @return \DateTime
   * @throws \Exception
   */
  public function getUntil(): \DateTime {
    return new \DateTime($this->until);
  }

  /**
   * @return bool
   */
  public function isEnforce(): bool {
    return $this->enforce;
  }

  /**
   * @return string
   * @throws \Exception
   */
  public function getHttpSince(): string {
    return $this->getSince()->format(self::HTTP_DATE_FORMAT);
  }

  /**
   * @return string
   * @throws \Exception
   */
  public function getHttpUntil(): string {
    return $this->getUntil()->format(self::HTTP_DATE_FORMAT);
  }

  /**
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

}
