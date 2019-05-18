<?php

namespace Drupal\Tests\mixitup_views\Unit;

use Drupal\mixitup_views\MixitupViewsDefaultOptionsService;
use Drupal\Tests\UnitTestCase;

/**
 * Class MixItUpDefaultOptionsServiceTest.
 *
 * @group MixItUp Views
 * @package Drupal\Tests\mixitup_views\Unit
 */
class MixItUpDefaultOptionsServiceTest extends UnitTestCase {

  protected $unit;

  /**
   * Before a test method is run, setUp() is invoked.
   *
   * Create new unit object.
   */
  public function setUp() {
    $this->unit = new MixitupViewsDefaultOptionsService();
  }

  /**
   * Covers defaultOptions method if $convert param coming with a FALSE value.
   *
   * @covers \Drupal\mixitup_views\MixitupViewsDefaultOptionsService::defaultOptions
   */
  public function testDefaultOptionsFalse():void {
    $options = [
      'selectors' => [
        'target' => '.mix',
        'filter' => '.filter',
        'sort' => '.sort',
      ],
      'load' => [
        'filter' => 'all',
        'sort' => 'default:asc',
      ],
      'animation' => [
        'enable' => TRUE,
        'effects' => 'fade scale',
        'duration' => 600,
        'easing' => 'ease',
        'perspectiveDistance' => '3000px',
        'perspectiveOrigin' => '50% 50%',
        'queue' => TRUE,
        'queueLimit' => 1,
      ],
      'restrict' => [
        'vocab' => FALSE,
        'vocab_ids' => [],
      ],
    ];

    $this->assertEquals($options, $this->unit->defaultOptions(NULL));
  }

  /**
   * Covers defaultOptions method if $convert param coming with a TRUE value.
   *
   * @covers \Drupal\mixitup_views\MixitupViewsDefaultOptionsService::defaultOptions
   */
  public function testDefaultOptionsTrue():void {
    $options = [
      'selectors_target' => '.mix',
      'selectors_filter' => '.filter',
      'selectors_sort' => '.sort',
      'load_filter' => 'all',
      'load_sort' => 'default:asc',
      'animation_enable' => TRUE,
      'animation_effects' => 'fade scale',
      'animation_duration' => 600,
      'animation_easing' => 'ease',
      'animation_perspectiveDistance' => '3000px',
      'animation_perspectiveOrigin' => '50% 50%',
      'animation_queue' => TRUE,
      'animation_queueLimit' => 1,
      'restrict_vocab' => FALSE,
      'restrict_vocab_ids' => [],
    ];

    $this->assertEquals($options, $this->unit->defaultOptions(TRUE));
  }

  /**
   * If test has finished running, tearDown() will be invoked.
   *
   * Unset the $unit object.
   */
  public function tearDown() {
    unset($this->unit);
  }

}
