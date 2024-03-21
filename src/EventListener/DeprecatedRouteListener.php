<?php


namespace HalloVerden\RouteDeprecationBundle\EventListener;


use HalloVerden\RouteDeprecationBundle\Attribute\DeprecatedRoute;
use HalloVerden\RouteDeprecationBundle\Helper\DateTimeHelper;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\Clock;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * Regarding the custom http headers returned in case of deprecation, see:
 * https://datatracker.ietf.org/doc/html/draft-ietf-httpapi-deprecation-header
 * https://datatracker.ietf.org/doc/html/rfc8594
 */
final class DeprecatedRouteListener implements EventSubscriberInterface {
  private const HEADER_DEPRECATION = 'Deprecation';
  private const HEADER_SUNSET = 'Sunset';
  private const HEADER_LINK = 'Link';

  public const DEFAULT_DEPRECATION_DATE_TIME_FORMAT = '@U';
  public const DEFAULT_SUNSET_DATE_TIME_FORMAT = 'D, d M Y H:i:s \G\M\T';

  public const ROUTE_PARAM_DEPRECATED_SINCE = '_deprecated_since';
  public const ROUTE_PARAM_DEPRECATED_SUNSET = '_deprecated_sunset';
  public const ROUTE_PARAM_DEPRECATED_ENFORCE = '_deprecated_enforce';
  public const ROUTE_PARAM_DEPRECATED_DEPRECATION_DATE_TIME_FORMAT = '_deprecated_deprecation_date_time_format';
  public const ROUTE_PARAM_DEPRECATED_SUNSET_DATE_TIME_FORMAT = '_deprecated_sunset_date_time_format';
  public const ROUTE_PARAM_DEPRECATED_DEPRECATION_LINK = '_deprecated_deprecation_link';
  public const ROUTE_PARAM_DEPRECATED_SUNSET_LINK = '_deprecated_sunset_link';

  private readonly ClockInterface $clock;
  private ?DeprecatedRoute $deprecatedRoute = null;

  /**
   * DeprecatedRouteListener constructor.
   */
  public function __construct(
    private readonly ?LoggerInterface $logger = null,
    private readonly string $deprecationDateTimeFormat = self::DEFAULT_DEPRECATION_DATE_TIME_FORMAT,
    private readonly string $sunsetDateTimeFormat = self::DEFAULT_SUNSET_DATE_TIME_FORMAT,
    private readonly ?string $deprecationLink = null,
    private readonly ?string $sunsetLink = null,
    ?ClockInterface $clock = null,
  ) {
    $this->clock = $clock ?? Clock::get();
  }

  /**
   * @return \array[][]
   */
  public static function getSubscribedEvents(): array {
    return [
      ControllerEvent::class => 'onKernelController',
      ResponseEvent::class => 'onKernelResponse',
    ];
  }

  /**
   * @param ControllerEvent $event
   *
   * @throws \Exception
   */
  public function onKernelController(ControllerEvent $event): void {
    $this->deprecatedRoute = $this->getDeprecatedRoute($event);
    if (null === $this->deprecatedRoute || !$this->deprecatedRoute->enforce || null === ($sunsetDate = $this->deprecatedRoute->getSunset())) {
      return;
    }

    if (DateTimeHelper::getDateTimeFromString($this->deprecatedRoute->sunset) < $this->clock->now()) {
      throw new GoneHttpException(
        sprintf(
          'This route was deprecated on %s and removed on %s. It is no longer available.',
          $this->deprecatedRoute->getSince()->format('Y-m-d'),
          $sunsetDate->format('Y-m-d')
        )
      );
    }
  }

  /**
   * @param ResponseEvent $event
   * @throws \Exception
   */
  public function onKernelResponse(ResponseEvent $event): void {
    if (null === $this->deprecatedRoute) {
      return;
    }

    $this->logger?->warning('Deprecated route {route} ({method} {uri}) was called', [
      'route' => $event->getRequest()->attributes->get('_route'),
      'method' => $event->getRequest()->getMethod(),
      'uri' => $event->getRequest()->getUri()
    ]);

    $response = $event->getResponse();

    $deprecationDateTimeFormat = $this->deprecatedRoute->deprecationDateTimeFormat ?? $this->deprecationDateTimeFormat;
    $response->headers->set(self::HEADER_DEPRECATION, $this->deprecatedRoute->getSince()->format($deprecationDateTimeFormat));

    if ($deprecationLink = $this->deprecatedRoute->deprecationLink ?? $this->deprecationLink) {
      $response->headers->set(self::HEADER_LINK, $this->createLinkHeaderValue($deprecationLink, 'deprecation'), false);
    }

    if (null !== ($sunsetDate = $this->deprecatedRoute->getSunset())) {
      $sunsetDateTimeFormat = $this->deprecatedRoute->sunsetDateTimeFormat ?? $this->sunsetDateTimeFormat;
      $response->headers->set(self::HEADER_SUNSET, $sunsetDate->format($sunsetDateTimeFormat));

      if ($sunsetLink = $this->deprecatedRoute->sunsetLink ?? $this->sunsetLink) {
        $response->headers->set(self::HEADER_LINK, $this->createLinkHeaderValue($sunsetLink, 'sunset'), false);
      }
    }
  }

  /**
   * @param string $link
   * @param string $rel
   *
   * @return string
   */
  private function createLinkHeaderValue(string $link, string $rel): string {
    return \sprintf('<%s>;rel="%s";type="text/html"', $link, $rel);
  }

  /**
   * @param ControllerEvent $event
   *
   * @return DeprecatedRoute|null
   */
  private function getDeprecatedRoute(ControllerEvent $event): ?DeprecatedRoute {
    /** @var DeprecatedRoute[] $deprecatedRoutes */
    $deprecatedRoutes = $event->getAttributes(DeprecatedRoute::class);

    $routeName = $event->getRequest()->get('_route');

    foreach ($deprecatedRoutes as $deprecatedRoute) {
      if (null === $deprecatedRoute->name || $routeName === $deprecatedRoute->name) {
        return $deprecatedRoute;
      }
    }

    $routeParams = $event->getRequest()->attributes->get('_route_params', []);
    if (!isset($routeParams[self::ROUTE_PARAM_DEPRECATED_SINCE])) {
      return null;
    }

    return new DeprecatedRoute(
      since: $routeParams[self::ROUTE_PARAM_DEPRECATED_SINCE],
      sunset: $routeParams[self::ROUTE_PARAM_DEPRECATED_SUNSET] ?? null,
      enforce: $routeParams[self::ROUTE_PARAM_DEPRECATED_ENFORCE] ?? false,
      name: $routeName,
      deprecationDateTimeFormat: $routeParams[self::ROUTE_PARAM_DEPRECATED_DEPRECATION_DATE_TIME_FORMAT] ?? null,
      sunsetDateTimeFormat: $routeParams[self::ROUTE_PARAM_DEPRECATED_SUNSET_DATE_TIME_FORMAT] ?? null,
      deprecationLink: $routeParams[self::ROUTE_PARAM_DEPRECATED_DEPRECATION_LINK] ?? null,
      sunsetLink: $routeParams[self::ROUTE_PARAM_DEPRECATED_SUNSET_LINK] ?? null,
    );
  }

}
