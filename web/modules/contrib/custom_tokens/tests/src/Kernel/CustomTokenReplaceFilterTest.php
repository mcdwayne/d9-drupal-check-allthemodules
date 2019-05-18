<?php

namespace Drupal\Tests\custom_tokens\Kernel;

use Drupal\custom_tokens\Entity\TokenEntity;
use Drupal\custom_tokens\Plugin\Filter\CustomTokenReplaceFilter;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the token replacements.
 */
class CustomTokenReplaceFilterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'custom_tokens',
  ];

  /**
   * Test the custom filter.
   *
   * @dataProvider filterTestCases
   */
  public function testFilter($entities, $text, $expected) {
    foreach ($entities as $entity) {
      TokenEntity::create($entity)->save();
    }

    $filter = new CustomTokenReplaceFilter([], '', ['provider' => 'foo']);
    $response = $filter->process($text, 'en');
    $this->assertEquals($expected, $response->getProcessedText());
  }

  /**
   * Test cases for ::testFilter.
   */
  public function filterTestCases() {
    return [
      'Simple token replacement' => [
        [
          [
            'id' => 'foo',
            'label' => 'Foo',
            'tokenName' => 'token-to-replace',
            'tokenValue' => 'replaced value!',
          ],
        ],
        'some text [token-to-replace] more text',
        'some text replaced value! more text',
      ],
    ];
  }

}
