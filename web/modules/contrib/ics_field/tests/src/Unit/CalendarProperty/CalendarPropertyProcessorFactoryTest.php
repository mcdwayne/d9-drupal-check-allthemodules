<?php

namespace Drupal\Tests\ics_field\Unit\CalendarProperty;

use Drupal\ics_field\CalendarProperty\CalendarPropertyProcessorFactory;
use Drupal\Tests\UnitTestCase;

/**
 * @group ics_field
 */
class CalendarPropertyProcessorFactoryTest extends UnitTestCase {

  /**
   * Test making the class with the correct dependencies
   */
  public function testInstantiation() {

    $tpi = $this->getMockBuilder('Drupal\ics_field\Timezone\TimezoneProviderInterface')
                ->getMock();
    $t = $this->getMockBuilder('Drupal\Core\Utility\Token')
              ->disableOriginalConstructor()
              ->getMock();
    $f = new CalendarPropertyProcessorFactory($tpi, $t);

    $this->assertInstanceOf('Drupal\ics_field\CalendarProperty\CalendarPropertyProcessorFactory',
                            $f);

  }

  /**
   * Test generating output
   */
  public function testGeneration() {

    $tpi = $this->getMockBuilder('Drupal\ics_field\Timezone\TimezoneProviderInterface')
                ->getMock();
    $t = $this->getMockBuilder('Drupal\Core\Utility\Token')
              ->disableOriginalConstructor()
              ->getMock();
    $f = new CalendarPropertyProcessorFactory($tpi, $t);

    $fdi = $this->getMockBuilder('Drupal\Core\Field\FieldDefinitionInterface')
                ->disableOriginalConstructor()
                ->getMock();
    $fdi->expects($this->once())
        ->method('getSetting')
        ->will($this->returnValue('I am the reference'));

    $fci = $this->getMockBuilder('Drupal\Core\Field\FieldConfigInterface')
                ->disableOriginalConstructor()
                ->getMock();
    $fci->expects($this->once())
        ->method('uuid')
        ->will($this->returnValue('i am the uuid'));

    $fdi->expects($this->once())
        ->method('getConfig')
        ->will($this->returnValue($fci));

    $this->assertInstanceOf('Drupal\ics_field\CalendarProperty\CalendarPropertyProcessor',
                            $f->create($fdi));
  }

}
