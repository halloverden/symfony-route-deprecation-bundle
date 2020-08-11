<?php


namespace App\Tests\Mock;


class CallableMock {

  public function __invoke() {
    // No implementation needed
  }

  public function ping(){
    // No implementation needed
  }

  public static function staticPing(){
    // No implementation needed
  }

}
