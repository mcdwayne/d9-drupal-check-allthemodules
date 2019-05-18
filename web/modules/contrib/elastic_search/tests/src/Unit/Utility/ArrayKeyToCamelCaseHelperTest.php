<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 13/01/17
 * Time: 14:15
 */
namespace Drupal\Tests\elastic_search\Unit\Utility;

use Drupal\elastic_search\Utility\ArrayKeyToCamelCaseHelper;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Class ArrayKeyToCamelCaseHelperTest
 *
 * @group elastic_search
 */
class ArrayKeyToCamelCaseHelperTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * @dataProvider camelConvertProvider
   */
  public function testArrayKeyToCamelCaseHelper($snake, $expected) {

    $converter = new ArrayKeyToCamelCaseHelper();
    $converted = $converter->convert($snake);
    $this->assertEquals($expected, $converted);

  }

  /**
   * @dataProvider camelConvertRecursionProvider
   */
  public function testArrayKeyToCamelCaseHelperRecursion($snake, $expected) {

    $converter = new ArrayKeyToCamelCaseHelper();
    $converted = $converter->convert($snake, TRUE);
    $this->assertEquals($expected, $converted);

  }

  /**
   * @return array
   */
  public function camelConvertProvider() {

    return [
      [
        $this->getBaseArrayInput(),
        [
          "nocamel"              => 0,
          'itRemindsMeOfMrBurns' => 0,
          'iAmAnArray'           => [
            'this_is_Some_Camel'     => 0,
            'this_camelIs_Recursive' => [
              'testNothing'                   => 0,
              'test_Another_thing'            => 0,
              'test_one_FinalArray_recursion' => [
                'the_end' => 0,
              ],
            ],
          ],
        ],
      ],
    ];

  }

  /**
   * @return array
   */
  public function camelConvertRecursionProvider() {

    return [
      [
        $this->getBaseArrayInput(),
        [
          "nocamel"              => 0,
          'itRemindsMeOfMrBurns' => 0,
          'iAmAnArray'           => [
            'thisIsSomeCamel'      => 0,
            'thisCamelIsRecursive' => [
              'testNothing'                => 0,
              'testAnotherThing'           => 0,
              'testOneFinalArrayRecursion' => [
                'theEnd' => 0,
              ],
            ],
          ],
        ],
      ],
    ];

  }

  /**
   * @return array
   */
  protected function getBaseArrayInput() {
    return [
      "nocamel"                   => 0,
      'it_reminds_me_of_mr_burns' => 0,
      'i_am_an_array'             => [
        'this_is_Some_Camel'     => 0,
        'this_camelIs_Recursive' => [
          'testNothing'                   => 0,
          'test_Another_thing'            => 0,
          'test_one_FinalArray_recursion' => [
            'the_end' => 0,
          ],
        ],
      ],
    ];
  }

}
