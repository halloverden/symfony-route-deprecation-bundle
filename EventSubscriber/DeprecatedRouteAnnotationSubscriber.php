<?php


namespace HalloVerden\RouteDeprecationBundle\EventSubscriber;


use HalloVerden\RouteDeprecationBundle\Annotation\DeprecatedRoute as DeprecatedRouteAnnotation;
use Doctrine\Common\Annotations\Reader;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
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
class DeprecatedRouteAnnotationSubscriber implements EventSubscriberInterface {
  const DEPRECATION_HEADER = 'Deprecation';
  const SUNSET_HEADER = 'Sunset';

  /**
   * @var Reader
   */
  private $annotationReader;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * DeprecatedRouteAnnotationSubscriber constructor.
   * @param Reader          $annotationReader
   * @param LoggerInterface $logger
   */
  public function __construct(Reader $annotationReader, LoggerInterface $logger) {
    $this->annotationReader = $annotationReader;
    $this->logger = $logger;
  }

  /**
   * @param ControllerEvent $event
   *
   * @throws \Exception
   */
  public function onKernelController(ControllerEvent $event): void {
    if (!$event->isMasterRequest()) {
      return;
    }
    $callableController = $event->getController();
    if(is_callable($callableController)) {
      $method = self::getReflectionMethodFromCallable($callableController);
      if ($method) {
        $this->handleAnnotation($method, $event->getRequest());
      }
    }
  }

  /**
   * @param ResponseEvent $event
   */
  public function onKernelResponse(ResponseEvent $event) {
    // check to see if onKernelController marked this as a deprecated request
    if (!$deprecationDate = $event->getRequest()->attributes->get(self::DEPRECATION_HEADER)) {
      return;
    }
    $response = $event->getResponse();
    // set date in the deprecation response header
    $response->headers->set(self::DEPRECATION_HEADER, $deprecationDate);

    // check to see if onKernelController set a sunset attribute
    if (!$sunsetDate = $event->getRequest()->attributes->get(self::SUNSET_HEADER)) {
      return;
    }
    // set date in the sunset response header
    $response->headers->set(self::SUNSET_HEADER, $sunsetDate);
  }

  /**
   * @param ReflectionMethod $method
   * @param Request $request
   *
   * @throws \Exception
   */
  private function handleAnnotation(ReflectionMethod $method, Request $request): void {
    $classAnnotation = $this->annotationReader->getClassAnnotation($method->getDeclaringClass(), DeprecatedRouteAnnotation::class);
    $methodAnnotation = $this->annotationReader->getMethodAnnotation($method, DeprecatedRouteAnnotation::class);
    $route = $request->get('_route');

    if ($classAnnotation instanceof DeprecatedRouteAnnotation &&
        $this->isRequestMatchingRouteName($route, $classAnnotation) &&
        (!$methodAnnotation instanceof DeprecatedRouteAnnotation || !$this->isRequestMatchingRouteName($route, $methodAnnotation))){
      $this->handleDeprecation($classAnnotation, $request);
    }
    if ($methodAnnotation instanceof DeprecatedRouteAnnotation && $this->isRequestMatchingRouteName($route, $methodAnnotation)){
      $this->handleDeprecation($methodAnnotation, $request);
    }
  }

  /**
   * @param DeprecatedRouteAnnotation $annotation
   * @param Request                   $request
   *
   * @throws \Exception
   */
  private function handleDeprecation(DeprecatedRouteAnnotation $annotation, Request $request): void {
    $request->attributes->set(self::DEPRECATION_HEADER, sprintf('date="%s"', $annotation->getHttpSince()));
    $request->attributes->set(self::SUNSET_HEADER, sprintf('date="%s"', $annotation->getHttpUntil()));

    if ($annotation->getUntil() < new \DateTime() && $annotation->isEnforce() === true) {
      throw new GoneHttpException(sprintf('This route was deprecated from %s until %s. It is no longer available.', $annotation->getHttpSince(), $annotation->getHttpUntil()));
    }
    if ($annotation->getSince() < new \DateTime()) {
      $this->logger->warning(sprintf('Route %s was called. It has been deprecated since %s and will be removed on %s.', $request->getRequestUri(), $annotation->getHttpSince(), $annotation->getHttpUntil()));
    }
  }

  /**
   * @param callable $callable
   *
   * @return ReflectionMethod
   * @throws \Exception
   */
  private function getReflectionMethodFromCallable(callable $callable) {
    try {
      if (\is_array($callable)) {
        $method = new \ReflectionMethod($callable[0], $callable[1]);
      } elseif (\is_object($callable) && \is_callable([$callable, '__invoke'])) {
        $method = new \ReflectionMethod($callable, '__invoke');
      } else if(is_string($callable) && (strpos($callable, '::') !== false)) {
        $callable = explode('::', $callable);
        $method = new \ReflectionMethod($callable[0], $callable[1]);
      } else {
        $method = new \ReflectionFunction($callable);
      }
    } catch (ReflectionException $e) {
      $this->logger->error('ReflectionException occurred while handling callable for DeprecationRouteAnnotation', [$callable, $e]);
      return null;
    }
    return $method;
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::CONTROLLER => 'onKernelController',
      KernelEvents::RESPONSE => 'onKernelResponse',
    ];
  }

  /**
   * @param string                    $route
   * @param DeprecatedRouteAnnotation $methodAnnotation
   * @return bool
   */
  private static function isRequestMatchingRouteName($route, $methodAnnotation): bool {
    return strpos($route, $methodAnnotation->getName()) !== false;
  }

}
