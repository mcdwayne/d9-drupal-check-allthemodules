<?php

namespace Drupal\Tests\protect_before_launch\Unit;

/**
 * Force implement createMock(), taken from Drupal PhpunitCompatibilityTrait.
 */
trait PhpunitCompatibilityTrait {

  /**
   * Returns a mock object for the specified class using the available method.
   *
   * The getMock method does not exist in PHPUnit 6. To provide backward
   * compatibility this trait provides the getMock method and uses createMock if
   * this method is available on the parent class.
   *
   * @param string $originalClassName
   *   Name of the class to mock.
   * @param array|null $methods
   *   When provided, only methods whose names are in the array are replaced
   *   with a configurable test double. The behavior of the other methods is not
   *   changed. Providing null means that no methods will be replaced.
   * @param array $arguments
   *   Parameters to pass to the original class' constructor.
   * @param string $mockClassName
   *   Class name for the generated test double class.
   * @param bool $callOriginalConstructor
   *   Can be used to disable the call to the original class' constructor.
   * @param bool $callOriginalClone
   *   Can be used to disable the call to the original class' clone constructor.
   * @param bool $callAutoload
   *   Can be used to disable __autoload() during the generation of the test
   *   double class.
   * @param bool $cloneArguments
   *   Enables the cloning of arguments passed to mocked methods.
   * @param bool $callOriginalMethods
   *   Enables the invocation of the original methods.
   * @param object $proxyTarget
   *   Sets the proxy target.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   PHP Unit Mock Object.
   *
   * @see https://www.drupal.org/node/2907725
   * @see \PHPUnit_Framework_TestCase::getMock
   * @see https://github.com/sebastianbergmann/phpunit/wiki/Release-Announcement-for-PHPUnit-5.4.0
   */
  public function createMock($originalClassName, $methods = [], array $arguments = [], $mockClassName = '', $callOriginalConstructor = FALSE, $callOriginalClone = TRUE, $callAutoload = TRUE, $cloneArguments = FALSE, $callOriginalMethods = FALSE, $proxyTarget = NULL) {
    $mock = $this->getMockBuilder($originalClassName)
      ->setMethods($methods)
      ->setConstructorArgs($arguments)
      ->setMockClassName($mockClassName)
      ->setProxyTarget($proxyTarget);
    if ($callOriginalConstructor) {
      $mock->enableOriginalConstructor();
    }
    else {
      $mock->disableOriginalConstructor();
    }
    if ($callOriginalClone) {
      $mock->enableOriginalClone();
    }
    else {
      $mock->disableOriginalClone();
    }
    if ($callAutoload) {
      $mock->enableAutoload();
    }
    else {
      $mock->disableAutoload();
    }
    if ($cloneArguments) {
      $mock->enableArgumentCloning();
    }
    else {
      $mock->disableArgumentCloning();
    }
    if ($callOriginalMethods) {
      $mock->enableProxyingToOriginalMethods();
    }
    else {
      $mock->disableProxyingToOriginalMethods();
    }
    return $mock->getMock();
  }

}
