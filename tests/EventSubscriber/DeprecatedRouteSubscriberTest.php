<?php

namespace HalloVerden\RouteDeprecationBundle\Tests\EventSubscriber;

use HalloVerden\RouteDeprecationBundle\EventSubscriber\DeprecatedRouteSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

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
        "_deprecated_since" => "2020-01-01",
        "_deprecated_until" => "2020-06-01",
        "_enforce_deprecation" => true
      ]),
      HttpKernelInterface::MASTER_REQUEST
    );

    $subscriber = new DeprecatedRouteSubscriber($this->createMock(LoggerInterface::class));
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
        "_deprecated_since" => "2020-01-01",
        "_enforce_deprecation" => false
      ]),
      HttpKernelInterface::MASTER_REQUEST,
      $response
    );

    $subscriber = new DeprecatedRouteSubscriber($this->createMock(LoggerInterface::class));
    $subscriber->onKernelResponse($responseEvent);

    $this->assertTrue($response->headers->has(DeprecatedRouteSubscriber::DEPRECATION_HEADER));
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_doesNotAddSunsetHeader_ifRequestDoesNotHaveDeprecatedUntilAttribute() {
    $response = new Response();
    $responseEvent = new ResponseEvent(
      $this->createMock(Kernel::class),
      new Request([], [], [
        "_deprecated_since" => "2020-01-01",
        "_enforce_deprecation" => false
      ]),
      HttpKernelInterface::MASTER_REQUEST,
      $response
    );

    $subscriber = new DeprecatedRouteSubscriber($this->createMock(LoggerInterface::class));
    $subscriber->onKernelResponse($responseEvent);

    $this->assertFalse($response->headers->has(DeprecatedRouteSubscriber::SUNSET_HEADER));
  }

  /**
   * @throws \Exception
   */
  public function testOnKernelResponse_addsSunsetHeader_ifRequestHasDeprecatedUntilAttribute() {
    $response = new Response();
    $responseEvent = new ResponseEvent(
      $this->createMock(Kernel::class),
      new Request([], [], [
        "_deprecated_since" => "2020-01-01",
        "_deprecated_until" => "2020-06-01",
        "_enforce_deprecation" => false
      ]),
      HttpKernelInterface::MASTER_REQUEST,
      $response
    );

    $subscriber = new DeprecatedRouteSubscriber($this->createMock(LoggerInterface::class));
    $subscriber->onKernelResponse($responseEvent);

    $this->assertTrue($response->headers->has(DeprecatedRouteSubscriber::SUNSET_HEADER));
  }
}
