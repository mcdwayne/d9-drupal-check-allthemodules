<?php

namespace Drupal\Tests\text_block\Kernel;

use Drupal\Tests\token\Kernel\KernelTestBase;

/**
 * Test the Text Block.
 *
 * @coversDefaultClass \Drupal\text_block\Plugin\Block\TextBlock
 * @group text_block
 */
class TextBlockTest extends KernelTestBase {

  public static $modules = ['block', 'filter', 'text_block'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['filter']);
  }

  /**
   * Test the output of the Text Block.
   *
   * @dataProvider expectedOutput
   */
  public function testBlockOutput($format, $input_value, $expected_output) {
    $block_values = [
      'id' => 'test_text_block',
      'theme' => 'stable',
      'region' => 'content',
      'weight' => 0,
      'plugin' => 'text_block',
      'settings' => [
        'id' => 'test_text_block',
        'label' => 'Test Text Block',
        'provider' => 'text_block',
        'label_display' => FALSE,
        'text' => [
          'value' => $input_value,
          'format' => $format,
        ],
      ],
    ];
    /** @var \Drupal\block\BlockInterface $block */
    $block = $this->container->get('entity_type.manager')->getStorage('block')->create($block_values);
    $block->save();
    $block = $this->container->get('entity_type.manager')->getStorage('block')->load('test_text_block');
    $this->assertNotNull($block);

    $build = $block->getPlugin()->build();
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $this->assertEquals($expected_output, $renderer->renderPlain($build));
  }

  /**
   * Provides sample output to expect.
   *
   * @return array
   *   The data for testing the expected output.
   */
  public function expectedOutput() {
    return [
      [NULL, 'Foo bar', 'Foo bar'],
      ['plain_text', 'Foo bar', "<p>Foo bar</p>\n"],
      [
        NULL,
        '<script>alert("Must not happen");</script>',
        'alert("Must not happen");',
      ],
      [
        'plain_text',
        '<script>alert("Must not happen");</script>',
        "<p>&lt;script&gt;alert(&quot;Must not happen&quot;);&lt;/script&gt;</p>\n",
      ],
    ];
  }

}
