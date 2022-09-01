<?php

namespace HalloVerden\RouteDeprecationBundle\Tests\EventSubscriber;

use HalloVerden\RouteDeprecationBundle\EventListener\DeprecatedRouteListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class DeprecatedRouteSubscriberTest
 *
 * @package HalloVerden\RouteDeprecationBundle\Tests\EventSubscriber
 */
class DeprecatedRouteSubscriberTest extends TestCase {
  /**
   * @throws \Exception
   */
  public function testOnKernelController_throwsGoneHttpException_ifEnforcedAndSunsetIsReached() {
    $controllerEvent = new ControllerEvent(
      $this->createMock(Kernel::class),
      function () {},
      new Request([], [], [
        "_route" => "test",
      ]),
      HttpKernelInterface::MAIN_REQUEST
    );

    $routeCollection = new RouteCollection();
    $routeCollection->add('test', new Route('/', [
      "_deprecated_since" => "2020-01-01",
      "_deprecated_until" => "2020-06-01",
      "_enforce_deprecation" => true
    ]));

    $router = $this->createMock(RouterInterface::class);
    $router->method('getRouteCollection')->willReturn($routeCollection);

    $subscriber = new DeprecatedRouteListener($this->createMock(LoggerInterface::class), $router);
    $this->expectException(GoneHttpException::class);
    $subscriber->onKernelController($controllerEvent);
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_addsDeprecationHeader_ifRequestHasDeprecatedSinceAttribute() {
    $response = new Response();
    $responseEvent = new ResponseEvent(
      $this->createMock(Kernel::class),
      new Request([], [], [
        "_route" => "test",
      ]),
      HttpKernelInterface::MASTER_REQUEST,
      $response
    );

    $routeCollection = new RouteCollection();
    $routeCollection->add('test', new Route('/', [
      "_deprecated_since" => "2020-01-01",
      "_enforce_deprecation" => false
    ]));

    $router = $this->createMock(RouterInterface::class);
    $router->method('getRouteCollection')->willReturn($routeCollection);

    $subscriber = new DeprecatedRouteListener($this->createMock(LoggerInterface::class), $router);
    $subscriber->onKernelResponse($responseEvent);

    $this->assertTrue($response->headers->has(DeprecatedRouteListener::DEPRECATION_HEADER));
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_doesNotAddSunsetHeader_ifRequestDoesNotHaveDeprecatedUntilAttribute() {
    $response = new Response();
    $responseEvent = new ResponseEvent(
      $this->createMock(Kernel::class),
      new Request([], [], [
        "_route" => "test",
      ]),
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );

    $routeCollection = new RouteCollection();
    $routeCollection->add('test', new Route('/', [
      "_deprecated_since" => "2020-01-01",
      "_enforce_deprecation" => false
    ]));

    $router = $this->createMock(RouterInterface::class);
    $router->method('getRouteCollection')->willReturn($routeCollection);

    $subscriber = new DeprecatedRouteListener($this->createMock(LoggerInterface::class), $router);
    $subscriber->onKernelResponse($responseEvent);

    $this->assertFalse($response->headers->has(DeprecatedRouteListener::SUNSET_HEADER));
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_addsSunsetHeader_ifRequestHasDeprecatedUntilAttribute() {
    $response = new Response();
    $responseEvent = new ResponseEvent(
      $this->createMock(Kernel::class),
      new Request([], [], [
        "_route" => "test",
      ]),
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );

    $routeCollection = new RouteCollection();
    $routeCollection->add('test', new Route('/', [
      "_deprecated_since" => "2020-01-01",
      "_deprecated_until" => "2020-06-01",
      "_enforce_deprecation" => false
    ]));

    $router = $this->createMock(RouterInterface::class);
    $router->method('getRouteCollection')->willReturn($routeCollection);

    $subscriber = new DeprecatedRouteListener($this->createMock(LoggerInterface::class), $router);
    $subscriber->onKernelResponse($responseEvent);

    $this->assertTrue($response->headers->has(DeprecatedRouteListener::SUNSET_HEADER));
  }
}
