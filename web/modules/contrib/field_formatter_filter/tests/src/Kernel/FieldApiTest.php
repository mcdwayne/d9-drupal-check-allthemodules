<?php

namespace Drupal\Tests\field_formatter_filter\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests applying the filter formatter to a node.
 *
 * As this is done through the non-bootstrapped mocked up back end,
 * I have to manually add a lot more specific dependencies than I'd like.
 *
 * @group field_formatter_filter
 */
class FieldApiTest extends EntityKernelTestBase {
  use \Drupal\simpletest\ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'system',
    'user',
    'text',
    'field_ui',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need some normal expectations to be present.
    // We require 'node' in order to run NodeType::create()
    $this->installEntitySchema('node');
    // Presumably 'user' is assumed a lot.
    $this->installEntitySchema('user');
    // 'system' provide a date_format that we need when rendering later.
    $this->installConfig(['system', 'field', 'node', 'user']);

    // Need to mock more bootstrap stuff.
    $this->createTextFormats();
  }

  /**
   * Tests creating and configuring a bundle.
   */
  public function testCreateContentType() {
    $this->createContentType(['type' => 'fff_article']);
  }

  /**
   * Test enabling the filter formatter. Check before, during and after.
   */
  public function testTeaserFilter() {
    $entity_type = 'node';
    $bundle = 'fff_article';
    $view_mode = 'teaser';

    $this->createContentType(['type' => $bundle]);
    $node = $this->createTestNode($bundle);

    // Verify that rendering the teaser normally shows unwanted text.
    $build = $this->container->get('entity.manager')
      ->getViewBuilder($entity_type)
      ->view($node, $view_mode);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertTrue((bool) preg_match("/the real content of the body text/", $output), 'Teaser view of node contains expected markup');
    $this->assertTrue((bool) preg_match("/<img/", $output), 'Teaser view of node contains messy markup');

    // Now enable the module.
    $this->container->get('module_installer')
      ->install(['field_formatter_filter'], TRUE);

    // Re-check that all is well, issue #2868519 implies it may damage normal
    // display.
    $build = $this->container->get('entity.manager')
      ->getViewBuilder($entity_type)
      ->view($node, $view_mode);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertTrue((bool) preg_match("/the real content of the body text/", $output), 'Teaser view of node contains expected markup');

    // Now edit the teaser view mode settings to use our safe markup filter.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()->getStorage('entity_view_display')
      ->load("${entity_type}.${bundle}.{$view_mode}");
    $component = $display->getComponent('body');
    // Adjust the body field formatter component and save the display.
    /*
    $component = [
      'type' => 'text_summary_or_trimmed',
      'label' => 'hidden',
      'weight' => 10,
      'settings' => [
        'trim_length' => 600,
      ],
      'third_party_settings' => [
        'field_formatter_filter' => [
          'format' => 'teaser_safe_text',
        ],
      ],
    ];
    */
    $component['third_party_settings']['field_formatter_filter']['format'] = 'teaser_safe_text';
    $display->setComponent('body', $component)->save();

    // Now re-render the teaser, and assert that the text has been sanitised.
    // display.
    $build = $this->container->get('entity.manager')
      ->getViewBuilder($entity_type)
      ->view($node, $view_mode);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertTrue((bool) preg_match("/the real content of the body text/", $output), 'Teaser view of node contains expected markup');
    $this->assertFalse((bool) preg_match("/<img/", $output), 'Teaser view of node does not contain messy markup');

    // Normal mode of operations successfully tested.
    // Now try to break it...
    // Assign an invalid value to the formatter.
    // As if the text format was deleted?
    // May trigger "Error: Call to a member function filters() on null".
    $component['third_party_settings']['field_formatter_filter']['format'] = 'invalid_text_format';
    $display->setComponent('body', $component)->save();
    $build = $this->container->get('entity.manager')
      ->getViewBuilder($entity_type)
      ->view($node, $view_mode);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertTrue((bool) preg_match("/the real content of the body text/", $output), 'Teaser view of node contains expected markup');
    $this->assertFalse((bool) preg_match("/<img/", $output), 'Teaser view of node does not contain messy markup');
  }

  /**
   * Create our test content type.
   *
   * Extend ContentTypeCreationTrait.
   * I'm not sure, but it seems this is not expected to be a thing we use
   * in Kernel tests - is it  based around assuming a simpletest environment?
   *
   * @inheritdoc
   */
  public function createContentType(array $values = []) {
    // From ContentTypeCreationTrait.
    $content_type = $this->drupalCreateContentType($values);
    // To avoid too many dependencies,
    // -- just toggle off the 'display user' for view modes.
    $content_type->set('display_submitted', FALSE);
    $content_type->save();
  }

  /**
   * Create our test content type.
   *
   * This operates completely through the API operations available for
   * configuring views modes and widget settings.
   *
   * @param string $bundle
   *
   * @see \Drupal\Tests\field_ui\Kernel\EntityDisplayTest
   */
  public function xcreateContentType(array $values = []) {
    $bundle = $values['type'];
    // Create a node bundle.
    $entity_type = 'node';
    $type = NodeType::create(['type' => $bundle]);
    // To avoid too many dependencies,
    // -- just toggle off the 'display user' for view modes.
    $type->set('display_submitted', FALSE);
    $type->save();
    node_add_body_field($type);

    // Presave its view modes - the default display and form display.
    // I think this helps ensure defaults are in place.
    //
    // Deprecated: entity_get_display($entity_type, $bundle, 'default')->save();
    \Drupal::entityTypeManager()->getStorage('entity_view_display')
      ->load("${entity_type}.${bundle}.default")
      ->save();
    // Deprecated: entity_get_form_display($entity_type, $bundle, 'default')->save();
    \Drupal::entityTypeManager()->getStorage('entity_form_display')
      ->load("${entity_type}.${bundle}.default")
      ->save();

    // Pre-save its teaser view mode. Same as above, but uses a static call?
    EntityViewDisplay::load("${entity_type}.${bundle}.teaser")->save();
  }

  /**
   * Create a node with sample content.
   *
   * @param $bundle
   * @return \Drupal\Core\Entity\EntityInterface
   */
  private function createTestNode($bundle) {
    // Sample markup is in an external file - just to keep HTML out of code.
    $path = __DIR__ . '/../..';
    $body = file_get_contents($path . '/sample-markup.txt');
    $node = Node::create([
      'type' => $bundle,
      'title' => 'Test this is filtered',
      'uid' => 1,
      'body' => ['value' => $body, 'format' => 'full_html'],
    ]);
    $validated = $node->validate();
    $saved = $node->save();
    // It's now populated with expected values like date and nid, so should be
    // ready to render.
    return $node;
  }

  /**
   * We need to set up two text formats.
   */
  private function createTextFormats() {
    // Add a text format with minimum data only.
    $format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $format->save();

    // Add another text format specifying all possible properties.
    $format = FilterFormat::create([
      'format' => 'teaser_safe_text',
      'name' => 'Teaser safe text',
    ]);
    $format->setFilterConfig('filter_html', [
      'status' => 1,
      'settings' => [
        'allowed_html' => '<p> <br> <a href hreflang> <em> <strong>',
      ],
    ]);
    $format->save();
  }

}
