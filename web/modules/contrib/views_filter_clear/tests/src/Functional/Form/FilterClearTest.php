<?php

namespace Drupal\Tests\views_filter_clear\Functional\Form;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the clear filter links.
 *
 * @group views_filter_clear
 */
class FilterClearTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo fix schema.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['clear_filter_test'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views_filter_clear',
    'views_filter_clear_test',
  ];

  /**
   * The view to test on.
   *
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $view;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);

    // Create some random nodes.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode();
    }

    $this->view = $this->container->get('entity_type.manager')->getStorage('view')->load('clear_filter_test');
  }

  /**
   * Tests the clear links.
   */
  public function testLinks() {
    // No clear link should appear by default.
    $this->drupalGet('clear-filter-test');
    $this->assertSession()->fieldExists('Content type');
    $this->assertSession()->fieldExists('Title');
    $this->assertSession()->linkNotExists(t('Clear'));

    // Enable clear link.
    $display = &$this->view->getDisplay('default');
    $display['display_options']['filters']['type']['expose']['add_clear_link'] = TRUE;
    $this->view->save();

    $this->drupalGet('clear-filter-test');
    $this->assertSession()->fieldExists('Content type');
    $this->assertSession()->fieldExists('Title');
    $this->assertSession()->linkExists(t('Clear'));

    $search = ['type' => 'article', 'title' => 'foo'];
    $this->drupalPostForm(NULL, $search, t('Apply'));
    $this->assertSession()->linkByHrefExists('clear-filter-test?title=foo');
    $this->assertSession()->linkByHrefNotExists('clear-filter-test?type=article');

    // Click the link.
    $this->clickLink(t('Clear'));
    $this->assertSession()->addressEquals('clear-filter-test');

    // Enable clear link on title filter.
    $display = &$this->view->getDisplay('default');
    $display['display_options']['filters']['title']['expose']['add_clear_link'] = TRUE;
    $this->view->save();

    $this->drupalGet('clear-filter-test');
    $this->assertSession()->fieldExists('Content type');
    $this->assertSession()->fieldExists('Title');
    $this->assertSession()->linkExists(t('Clear'), 0);
    $this->assertSession()->linkExists(t('Clear'), 1);

    $search = ['type' => 'article', 'title' => 'foo'];
    $this->drupalPostForm(NULL, $search, t('Apply'));
    $this->assertSession()->linkByHrefExists('clear-filter-test?title=foo');
    $this->assertSession()->linkByHrefExists('clear-filter-test?type=article');

    // Click the first link.
    $this->clickLink(t('Clear'), 0);
    $this->assertSession()->addressEquals('clear-filter-test?title=foo');

    // Click the second link.
    $this->clickLink(t('Clear'), 1);
    $this->assertSession()->addressEquals('clear-filter-test');
  }

}
