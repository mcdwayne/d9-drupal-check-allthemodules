<?php

namespace Drupal\Tests\views_filter_clear\Functional\Form;

use Drupal\Tests\views_ui\Functional\UITestBase;

/**
 * Functional admin UI test for Views Filter Clear.
 *
 * @group views_filter_clear
 */
class AdminUiTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_exposed_admin_ui'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views_filter_clear',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);
  }

  /**
   * Tests the UI.
   */
  public function testUi() {
    // Expose the 'type' filter.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type', [], t('Expose filter'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $this->assertSession()->fieldExists(t('Add a clear link'));
    $this->assertSession()->checkboxNotChecked(t('Add a clear link'));

    // Check the option and save the view.
    $edit['options[expose][add_clear_link]'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Verify the option is saved.
    $display = $this->container->get('entity_type.manager')->getStorage('view')->load('test_exposed_admin_ui')->getDisplay('default');
    $this->assertTrue($display['display_options']['filters']['type']['expose']['add_clear_link']);

    // Uncheck the option.
    $this->drupalGet('admin/structure/views/nojs/handler/test_exposed_admin_ui/default/filter/type');
    $edit['options[expose][add_clear_link]'] = FALSE;
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Verify the option is saved.
    $display = $this->container->get('entity_type.manager')->getStorage('view')->load('test_exposed_admin_ui')->getDisplay('default');
    $this->assertFalse($display['display_options']['filters']['type']['exposed']['add_clear_link']);
  }

}
