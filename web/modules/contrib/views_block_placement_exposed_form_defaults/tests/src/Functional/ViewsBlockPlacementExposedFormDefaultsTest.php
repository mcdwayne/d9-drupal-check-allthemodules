<?php

namespace Drupal\Tests\views_block_placement_exposed_form_defaults\Functional;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the VBPEFD module.
 *
 * @group views_block_placement_exposed_form_defaults
 */
class ViewsBlockPlacementExposedFormDefaultsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'views',
    'views_ui',
    'node',
    'taxonomy',
    'block',
    'views_block_placement_exposed_form_defaults',
    'vbpefd_test_config',
  ];

  /**
   * A test term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $testTerm;

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->testTerm = Term::create([
      'vid' => 'test_vocab',
      'name' => 'Test term',
    ]);
    $this->testTerm->save();

    $this->testNode = Node::create([
      'type' => 'test_content_type',
      'title' => 'Test node',
    ]);
    $this->testNode->save();
  }

  /**
   * Test the exposed forms defaults site building flow.
   */
  public function testExposedFormDefaults() {
    $this->drupalLogin($this->rootUser);

    // Visit the view.
    $this->drupalGet('admin/structure/views/view/test_view');
    $this->assertSession()->linkExists('Items per page, 0 customizable filters');

    // Open the modal to configure fields that should be displayed on the block
    // form.
    $this->clickLink('Items per page, 0 customizable filters');
    $this->assertSession()->elementContains('css', '#edit-customizable-exposed-filters--wrapper', 'Customizable filters');
    $this->assertSession()->elementContains('css', '#edit-customizable-exposed-filters--wrapper', 'Exposed term reference');
    $this->submitForm([
      'customizable_exposed_filters[field_term_reference_target_id]' => TRUE,
    ], 'Apply');

    // Save the view.
    $this->assertSession()->linkExists('Items per page, 1 customizable filter');
    $this->submitForm([], 'Save');

    // Place the views block in a region.
    $this->drupalGet('admin/structure/block/add/views_block:test_view-block_1/classy');
    $this->submitForm([
      'settings[exposed_filters][field_term_reference_target_id]' => $this->testTerm->id(),
      'region' => 'content',
    ], 'Save block');

    // Verify the exposed form is rendered with the selected default.
    $this->drupalGet('<front>');
    $this->assertSession()->fieldValueEquals('field_term_reference_target_id', $this->testTerm->id());
  }

}
