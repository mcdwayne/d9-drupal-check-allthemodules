<?php

namespace Drupal\Test\jquery_colorpicker\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\jquery_colorpicker\Service\JQueryColorpickerService;

/**
 * @coversDefaultClass \Drupal\jquery_colorpicker\Service\JQueryColorpickerService
 * @group jquery_colorpicker
 */
class JQueryColorpickerServiceTest extends UnitTestCase {
  use StringTranslationTrait;

  /**
   * The JQuery Colorpicker Service.
   *
   * @var \Drupal\jquery_colorpicker\Service\JQueryColorpickerService
   */
  protected $JQueryColorpickerService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->JQueryColorpickerService = new JQueryColorpickerService();
  }

  /**
   * @covers ::validateHexColor
   *
   * @dataProvider providerTestValidateHexColor
   */
  public function testValidateHexColor($expected, $color) {
    $this->assertEquals($expected, $this->JQueryColorpickerService->validateHexColor($color));
  }

  /**
   * Provides data for the testValidateHexColor test.
   */
  public function providerTestValidateHexColor() {

    $data = [];
    $data[] = [FALSE, FALSE];
    $data[] = [FALSE, []];
    $test = new \stdClass();
    $data[] = [FALSE, $test];
    $data[] = [FALSE, 1.23];
    $data[] = [FALSE, 12345];
    $data[] = [FALSE, "12345"];
    $data[] = [FALSE, "11111g"];
    $data[] = [FALSE, "11111G"];
    $data[] = [FALSE, "fffffg"];
    $data[] = [FALSE, "fffffG"];
    $data[] = [FALSE, "FFFFFg"];
    $data[] = [FALSE, "FFFFFG"];
    $data[] = [FALSE, "#12345"];
    $data[] = [FALSE, "#11111g"];
    $data[] = [FALSE, "#11111G"];
    $data[] = [FALSE, "#fffffg"];
    $data[] = [FALSE, "#fffffG"];
    $data[] = [FALSE, "#FFFFFg"];
    $data[] = [FALSE, "#FFFFFG"];

    // Valid submissions.
    $data[] = [TRUE, "#FFF"];
    $data[] = [TRUE, "#123456"];
    $data[] = [TRUE, "#11111f"];
    $data[] = [TRUE, "#11111F"];
    $data[] = [TRUE, "#FFFFF1"];
    $data[] = [TRUE, "#fffff1"];

    return $data;
  }

}
