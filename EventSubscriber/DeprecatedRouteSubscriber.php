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
  const HTTP_DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

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
    $deprecationDate = new \DateTime($deprecatedSince);
    $response->headers->set(self::DEPRECATION_HEADER, $deprecationDate->format(self::HTTP_DATE_FORMAT));

    // check to see if onKernelController set a sunset attribute
    if (!$deprecatedUntil = $event->getRequest()->attributes->get(self::SUNSET_ATTRIBUTE)) {
      return;
    }
    // set date in the sunset response header
    $sunsetDate = new \DateTime($deprecatedUntil);
    $response->headers->set(self::SUNSET_HEADER, $sunsetDate->format(self::HTTP_DATE_FORMAT));

    //default is false
    if ($event->getRequest()->attributes->get(self::ENFORCE_ATTRIBUTE) &&  new \DateTime($deprecatedUntil) < new \DateTime()) {
      throw new GoneHttpException(sprintf('This route was deprecated from %s until %s. It is no longer available.', $deprecatedSince, $deprecatedUntil));
    }
    $this->logger->warning(sprintf('Route %s was called. It has been deprecated since %s and will be removed on %s.', $event->getRequest()->getRequestUri(), $deprecatedSince, $deprecatedUntil));
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'onKernelResponse'
    ];
  }

}
