<?php

namespace Drupal\Tests\field_formatter_filter\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests applying the filter formatter to a node.
 *
 * This set of tests exercises fff_content test module, avoiding
 * too many internal API calls for stubbing.
 *
 * @group field_formatter_filter
 */
class NodeFilterTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * In KernelTestBase, Modules are "loaded" but not "installed",
   * whatever that means. We have to manually installConfig() for them?
   *
   * Our own test module sets up the content type fff_test_type with the
   * field formatter filter enabled on teaser body.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'node',
    'field',
    'text',
    'user',
    'filter',
    'field_formatter_filter',
  ];

  /**
   * {@inheritdoc}
   *
   * Derived with reference to from NodeImportCreateTest.
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    // Need some normal expectations to be present.
    // 'system' provide a date_format that we need when rendering later.
    $this->installConfig(['system']);
    $this->createContentType(['type' => 'fff_test_type']);
  }

  /**
   * Set up the content type we use for testing.
   *
   * This function signiture is compatible with ContentTypeCreationTrait,
   * although it's a total override of it, and ContentTypeCreationTrait is not
   * even used here.
   * The idea is to avoid confusion.
   * That's the *idea* anyway.
   *
   * Enable the module that provides the content type definition,
   * and other config dependencies it needs.
   *
   * Our content type definition is imported via
   * config/install/node.type.*.yml not via crud.
   * I thought this was going to be the easy way, but really, the
   * Content Type API is much easier!
   *
   * @param string $bundle
   */
  private function createContentType(array $values = ['type' => 'fff_test_type']) {
    $bundle = $values['type'];
    // Not using $this->enableModules() as it does not recurse dependencies.
    $this->container->get('module_installer')
      ->install(['fff_content'], TRUE);
    // Verify success before continuing.
    $node_type = NodeType::load($bundle);
    $this->assertTrue($node_type, "The $bundle content type was created.");
  }

  /**
   * Create a node with sample content.
   *
   * @param string $bundle
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
   * Tests applying the text formatter to node teasers.
   */
  public function testTeaserFilter() {
    $node = $this->createTestNode('fff_test_type');
    // When the node is set to promoted, we expect to see its teaser
    // on the /node page. Once you have views.module on at least.
    // But that would require BrowserTestBase
    // instead of KernelTestBase.
    // Artificially just call the render stack directly instead.
    $build = $this->container->get('entity.manager')
      ->getViewBuilder('node')
      ->view($node, 'full');
    $output = \Drupal::service('renderer')->renderRoot($build);

    // When rendered in 'full' mode, the bad markup should be there.
    $this->assertTrue((bool) preg_match("/the real content of the body text/", $output), 'Full view of node contains expected markup');
    $this->assertTrue((bool) preg_match("/<img/", $output), 'Full view of node contains messy markup');

    // But when rendered as a teaser, it must NOT be there.
    $build = $this->container->get('entity.manager')
      ->getViewBuilder('node')
      ->view($node, 'teaser');
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertTrue((bool) preg_match("/the real content of the body text/", $output), 'Teaser view of node contains expected markup');
    $this->assertFalse((bool) preg_match("/<img/", $output), 'Teaser view of node does not contain messy markup');
  }

}
