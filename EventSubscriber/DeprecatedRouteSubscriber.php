<?php


namespace HalloVerden\RouteDeprecationBundle\EventSubscriber;


use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class DeprecatedRouteAnnotationSubscriber
 * @package App\EventSubscriber
 *
 * Regarding the custom http headers returned in case of deprecation, see:
 * https://tools.ietf.org/html/draft-dalal-deprecation-header-02
 * https://tools.ietf.org/html/draft-wilde-sunset-header-11
 */
class DeprecatedRouteSubscriber implements EventSubscriberInterface {
  const DEPRECATION_ATTRIBUTE = '_deprecated_since';
  const DEPRECATION_HEADER = 'Deprecation';
  const SUNSET_ATTRIBUTE = '_deprecated_until';
  const SUNSET_HEADER = 'Sunset';
  const ENFORCE_ATTRIBUTE = '_enforce_deprecation';
  const DATE_FORMAT_ERROR_MESSAGE = 'Wrong date format, review documentation for the list of accepted formats';

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * DeprecatedRouteAnnotationSubscriber constructor.
   * @param LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * @param ResponseEvent $event
   * @throws \Exception
   */
  public function onKernelResponse(ResponseEvent $event) {
    if (!$deprecatedSince = $event->getRequest()->attributes->get(self::DEPRECATION_ATTRIBUTE)) {
      return;
    }
    $response = $event->getResponse();
    // set date in the deprecation response header
    $deprecationDate = $this->extractDate($deprecatedSince);
    $response->headers->set(self::DEPRECATION_HEADER, $deprecationDate->format(\DateTimeInterface::RFC7231));

    // check to see if onKernelController set a sunset attribute
    if (!$deprecatedUntil = $event->getRequest()->attributes->get(self::SUNSET_ATTRIBUTE)) {
      return;
    }
    // set date in the sunset response header
    $sunsetDate = $this->extractDate($deprecatedUntil);
    $response->headers->set(self::SUNSET_HEADER, $sunsetDate->format(\DateTimeInterface::RFC7231));

    //default is false
    if (true === $event->getRequest()->attributes->get(self::ENFORCE_ATTRIBUTE) &&  new \DateTime($deprecatedUntil) < new \DateTime()) {
      throw new GoneHttpException(sprintf('This route was deprecated from %s until %s. It is no longer available.', $deprecatedSince, $deprecatedUntil));
    }
    $this->logger->warning(sprintf('Route %s was called. It has been deprecated since %s and will be removed on %s.', $event->getRequest()->getRequestUri(), $deprecatedSince, $deprecatedUntil));
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'onKernelResponse'
    ];
  }

  /**
   * @param string $inputDate
   *
   * @return \DateTime
   * @throws \Exception
   */
  private function extractDate($inputDate): \DateTime {
    $utcTimeZone = new \DateTimeZone('UTC');
    $dateTime = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $inputDate);
    //for other accepted formats, time must be set to midnight
    if (false === $dateTime) {
      $dateTime = \DateTime::createFromFormat('Y-m-d', $inputDate, $utcTimeZone);
      if (false === $dateTime) {
        $dateTime = \DateTime::createFromFormat('Y-m-dP', $inputDate);
      }
      if(false !== $dateTime){
        $dateTime->setTime(0,0);
      }
    }
    //if no format worked, throw an exception
    if (false === $dateTime) {
      throw new \Exception(self::DATE_FORMAT_ERROR_MESSAGE);
    }
    return $dateTime->setTimezone($utcTimeZone);
  }

}
