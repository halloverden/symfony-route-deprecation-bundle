<?php


namespace HalloVerden\RouteDeprecationBundle\EventListener;


use HalloVerden\RouteDeprecationBundle\Helper\DateTimeHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Regarding the custom http headers returned in case of deprecation, see:
 * https://tools.ietf.org/html/draft-dalal-deprecation-header-02
 * https://tools.ietf.org/html/draft-wilde-sunset-header-11
 */
class DeprecatedRouteListener implements EventSubscriberInterface {
  const DEPRECATION_ATTRIBUTE = '_deprecated_since';
  const DEPRECATION_HEADER = 'Deprecation';

  const SUNSET_ATTRIBUTE = '_sunset_at';
  const SUNSET_HEADER = 'Sunset';

  const ENFORCE_ATTRIBUTE = '_enforce_sunset';

  const HTTP_DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

  /**
   * DeprecatedRouteListener constructor.
   */
  public function __construct(private readonly LoggerInterface $logger, private readonly RouterInterface $router) {
  }

  /**
   * @return \array[][]
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::CONTROLLER => [
        ['onKernelController', 0]
      ],
      KernelEvents::RESPONSE => [
        ['onKernelResponse', 0]
      ]
    ];
  }

  /**
   * @param ControllerEvent $event
   *
   * @throws \Exception
   */
  public function onKernelController(ControllerEvent $event) {
    $route = $this->getRoute($event->getRequest());
    if (null === $route) {
      return;
    }

    if (!$deprecatedSince = $this->getDeprecatedSince($route)) {
      return;
    }

    if (!$sunsetDate = $this->getSunsetDate($route)) {
      return;
    }

    if (true === $this->getEnforce($route) && $sunsetDate < new \DateTime()) {
      throw new GoneHttpException(sprintf('This route was deprecated on %s and removed on %s. It is no longer available.', $deprecatedSince->format('Y-m-d'),$sunsetDate->format('Y-m-d')));
    }
  }

  /**
   * @param ResponseEvent $event
   * @throws \Exception
   */
  public function onKernelResponse(ResponseEvent $event) {
    $route = $this->getRoute($event->getRequest());
    if (null === $route) {
      return;
    }

    $deprecatedSince = $this->getDeprecatedSince($route);
    if (null === $deprecatedSince) {
      return;
    }

    $sunsetDate = $this->getSunsetDate($route);

    $this->logger->warning(\sprintf('Deprecated route %s was called', $route->getPath()), [
      'deprecatedSince' => $deprecatedSince,
      'sunsetDate' => $sunsetDate,
      'route' => $route->__serialize()
    ]);

    $response = $event->getResponse();

    $response->headers->set(self::DEPRECATION_HEADER, $deprecatedSince->format(self::HTTP_DATE_FORMAT));

    if (null !== $sunsetDate) {
      $response->headers->set(self::SUNSET_HEADER, $sunsetDate->format(self::HTTP_DATE_FORMAT));
    }
  }


  /**
   * @param Request $request
   *
   * @return Route|null
   */
  private function getRoute(Request $request): ?Route {
    $routeName = $request->attributes->get('_route');
    if (null === $routeName) {
      return null;
    }

    return $this->router->getRouteCollection()->get($routeName);
  }

  /**
   * @param Route $route
   *
   * @return \DateTimeInterface|null
   * @throws \Exception
   */
  private function getDeprecatedSince(Route $route): ?\DateTimeInterface {
    return $route->hasDefault(self::DEPRECATION_ATTRIBUTE) ? DateTimeHelper::getDateTimeFromString($route->getDefault(self::DEPRECATION_ATTRIBUTE)) : null;
  }

  /**
   * @param Route $route
   *
   * @return \DateTimeInterface|null
   * @throws \Exception
   */
  private function getSunsetDate(Route $route): ?\DateTimeInterface {
    return $route->hasDefault(self::SUNSET_ATTRIBUTE) ? DateTimeHelper::getDateTimeFromString($route->getDefault(self::SUNSET_ATTRIBUTE)) : null;
  }

  /**
   * @param Route $route
   *
   * @return bool
   */
  private function getEnforce(Route $route): bool {
    return $route->getDefault(self::ENFORCE_ATTRIBUTE) ?? false;
  }

}
