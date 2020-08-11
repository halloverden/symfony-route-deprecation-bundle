<?php


namespace App\Tests\EventSubscribers;


use HalloVerden\RouteDeprecationBundle\EventSubscriber\DeprecatedRouteAnnotationSubscriber;
use App\Tests\Mock\CallableMock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DeprecatedRouteAnnotationSubscriberTest extends KernelTestCase {

  private $subscriber;

  protected function setUp():void {
    self::bootKernel();

    $this->subscriber = self::$container
      ->get(DeprecatedRouteAnnotationSubscriber::class);
  }

  /**
   * @param callable $callable
   * @param string $callableType
   * @dataProvider callableProvider
   *
   * @throws \Exception
   */
  public function test_getReflectionMethodFromCallable_callableProvider_doesNotReturnNull(callable $callable, string $callableType){
    $getReflectionMethodFromCallable = self::getMethod('getReflectionMethodFromCallable');
    $reflectionMethod = $getReflectionMethodFromCallable->invokeArgs($this->subscriber,[$callable]);
    $this->assertNotNull($reflectionMethod, "test_getReflectionMethodFromCallable failed with input: ".$callableType);
  }

  /**
   * All possible callables.
   * [callable, callable type]
   *
   * @return \Generator
   */
  public function callableProvider() {
    /** @var callable $staticArray */
    $staticArray = array('App\Tests\Mock\CallableMock','staticPing');
    yield([$staticArray, 'Static class method call in an array']);          #0

    /** @var callable $objectArray */
    $objectArray = array(new CallableMock(), 'ping');
    yield([$objectArray, 'Object method call']);                            #1

    /** @var callable $invokerObject */
    $invokerObject = new CallableMock();
    yield([$invokerObject, 'Object implementing __invoke']);                #2

    /** @var callable $staticString */
    $staticString = 'App\Tests\Mock\CallableMock::staticPing';
    yield([$staticString, 'Static class method call in a single string']);  #3

    /** @var callable $closure */
    $closure = function($a) { return $a * 2; };
    yield([$closure, 'Closure']);                                           #4
  }

  /**
   * @param $name
   * @return \ReflectionMethod
   * @throws \ReflectionException
   */
  protected static function getMethod($name) {
    $class = new \ReflectionClass(DeprecatedRouteAnnotationSubscriber::class);
    $method = $class->getMethod($name);
    $method->setAccessible(true);
    return $method;
  }

}
