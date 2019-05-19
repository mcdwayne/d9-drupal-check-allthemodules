<?php
/**
 * @file
 * Contains \Drupal\Tests\textareatabs\Unit\Plugin\Filter\TextAreaTabsTest
 */

namespace Drupal\Tests\textareatabs\Unit\Plugin\Filter;

use Drupal\Core\Form\FormState;
use Drupal\Core\Language\Language;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\textareatabs\Plugin\Filter\TextAreaTabs;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Test the text area tab filter class.
 *
 * @coversDefaultClass \Drupal\textareatabs\Plugin\Filter\TextAreaTabs
 *
 * @requires module textareatabs
 * @group textareatabs
 */
class TextAreaTabsTest extends UnitTestCase {

  /**
   * @property array $definition
   */
  protected $definition;

  /**
   * @property array $defaults
   */
  protected $defaults;

  const LANG = Language::LANGCODE_DEFAULT;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->definition = [
      'id' => 'textareatabs',
      'title' => 'Replace tabs with non-breaking spaces',
      'provider' => 'textareatabs',
      'type' => FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
    ];

    $this->defaults = [
      'id' => 'textareatabs',
      'provider' => 'textareatabs',
      'weight' => 0,
      'status' => TRUE,
    ];

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Assert that the process method works.
   *
   * @param string $replacement
   *   The replacement string to use for the plugin configuration.
   * @param string $output_prefix
   *   The output prefix to tack onto whatever the test string is.
   *
   * @covers ::process
   * @dataProvider replacementProvider
   */
  public function testProcess($replacement, $output_prefix) {
    $configuration = $this->defaults + [
      'settings' => ['textareatabs_character' => $replacement],
    ];

    // Generate a random sentence.
    $sentence = $this->getRandomGenerator()->sentences(10, TRUE);

    // Instantiate the plugin.
    $filter = new TextAreaTabs($configuration, 'textareatabs', $this->definition);
    $this->assertEquals($configuration, $filter->getConfiguration());

    // Assert that the text replacement has been made correctly.
    $this->assertEquals($output_prefix . $sentence, $filter->process("\t" . $sentence, self::LANG)->getProcessedText());
  }

  /**
   * Provide data for testing the process method.
   *
   * @return array
   *   An array of method parameters.
   */
  public function replacementProvider() {
    return [
      ["&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"],
      ['<a href="javascript:alert(aaaaaaaa)>"', "\""],
      ["AAAA", "AAAA"],
    ];
  }

  /**
   * Assert that the settings form behaves properly.
   *
   * @covers ::settingsForm
   */
  public function testSettingsForm() {
    $configuration = $this->defaults + [
      'settings' => ['textareatabs_character' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'],
    ];
    $form_state = new FormState();

    // Instantiate the plugin.
    $filter = new TextAreaTabs($configuration, 'textareatabs', $this->definition);
    $this->assertEquals($configuration, $filter->getConfiguration());

    // Get the settingsForm.
    $form = $filter->settingsForm([], $form_state);

    $this->assertArrayHasKey('textareatabs_character', $form);
    $this->assertEquals('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $form['textareatabs_character']['#default_value']);
  }
}
