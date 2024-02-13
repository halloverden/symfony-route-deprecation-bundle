<?php

namespace HalloVerden\RouteDeprecationBundle\Tests\EventSubscriber;

use HalloVerden\RouteDeprecationBundle\Attribute\DeprecatedRoute;
use HalloVerden\RouteDeprecationBundle\EventListener\DeprecatedRouteListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class DeprecatedRouteListenerTest extends TestCase {

  /**
   * @throws \Exception
   */
  public function testOnKernelController_throwsGoneHttpException_ifEnforcedAndSunsetIsReached() {
    $controllerEvent = new ControllerEvent(
      $this->createMock(Kernel::class),
      function () {},
      new Request([], [], ["_route" => "test"]),
      HttpKernelInterface::MAIN_REQUEST
    );

    $deprecatedRoute = new DeprecatedRoute(since: '2024-02-11', sunset: '2024-02-12', enforce: true);
    $controllerEvent->setController(function () {}, [DeprecatedRoute::class => [$deprecatedRoute]]);

    $now = \DateTimeImmutable::createFromFormat('Y-m-d', '2024-02-13');
    $subscriber = new DeprecatedRouteListener(clock: new MockClock($now));

    $this->expectException(GoneHttpException::class);
    $subscriber->onKernelController($controllerEvent);
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_addsDeprecationHeader_ifRequestHasDeprecatedSinceAttribute() {
    $kernel = $this->createMock(Kernel::class);
    $request = new Request([], [], ["_route" => "test"]);
    $controllerEvent = new ControllerEvent($kernel, function () {}, $request, HttpKernelInterface::MAIN_REQUEST);

    $response = new Response();
    $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);


    $deprecatedRoute = new DeprecatedRoute(since: '2024-02-11');
    $controllerEvent->setController(function () {}, [DeprecatedRoute::class => [$deprecatedRoute]]);

    $now = \DateTimeImmutable::createFromFormat('Y-m-d', '2024-02-13');
    $subscriber = new DeprecatedRouteListener(clock: new MockClock($now));

    $subscriber->onKernelController($controllerEvent);
    $subscriber->onKernelResponse($responseEvent);

    $this->assertTrue($response->headers->has('Deprecation'));
    $this->assertEquals('@1707609600', $response->headers->get('Deprecation'));
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_doesNotAddSunsetHeader_ifRequestDoesNotHaveDeprecatedUntilAttribute() {
    $kernel = $this->createMock(Kernel::class);
    $request = new Request([], [], ["_route" => "test"]);
    $controllerEvent = new ControllerEvent($kernel, function () {}, $request, HttpKernelInterface::MAIN_REQUEST);

    $response = new Response();
    $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);


    $deprecatedRoute = new DeprecatedRoute(since: '2024-02-11');
    $controllerEvent->setController(function () {}, [DeprecatedRoute::class => [$deprecatedRoute]]);

    $now = \DateTimeImmutable::createFromFormat('Y-m-d', '2024-02-13');
    $subscriber = new DeprecatedRouteListener(clock: new MockClock($now));

    $subscriber->onKernelController($controllerEvent);
    $subscriber->onKernelResponse($responseEvent);

    $this->assertFalse($response->headers->has('Sunset'));
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_addsSunsetHeader_ifRequestHasDeprecatedUntilAttribute() {
    $kernel = $this->createMock(Kernel::class);
    $request = new Request([], [], ["_route" => "test"]);
    $controllerEvent = new ControllerEvent($kernel, function () {}, $request, HttpKernelInterface::MAIN_REQUEST);

    $response = new Response();
    $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);


    $deprecatedRoute = new DeprecatedRoute(since: '2024-02-11', sunset: '2024-03-12');
    $controllerEvent->setController(function () {}, [DeprecatedRoute::class => [$deprecatedRoute]]);

    $now = \DateTimeImmutable::createFromFormat('Y-m-d', '2024-02-13');
    $subscriber = new DeprecatedRouteListener(clock: new MockClock($now));

    $subscriber->onKernelController($controllerEvent);
    $subscriber->onKernelResponse($responseEvent);

    $this->assertTrue($response->headers->has('Sunset'));
    $this->assertEquals('Tue, 12 Mar 2024 00:00:00 GMT', $response->headers->get('Sunset'));
  }

}
