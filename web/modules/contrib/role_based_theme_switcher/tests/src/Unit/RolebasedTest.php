<?php

namespace Drupal\Tests\role_based_theme_switcher\Unit;
use Drupal\Tests\UnitTestCase;

/**
*
* Test for Role check.
*
* @group role_based_theme_switcher
**/
class RolebasedTest extends UnitTestCase {

   public function setUp() {
        parent::setUp();
    }

    public function testStub() {
      $role = ['authenticated', 'administrator'];
      // Create a stub for the SomeClass class.
      $stub = $this->getMockBuilder('Drupal\role_based_theme_switcher\Theme\RoleNegotiator')
          ->disableOriginalConstructor()
              ->setMethods(['getPriorityRole'])
                         ->getMock();
      $stub->expects($this->once())
          ->method('getPriorityRole')
                 ->with(
                       $this->equalTo($role)
                   );

      // Configure the stub.
        $stub->getPriorityRole($role);
    }
}
