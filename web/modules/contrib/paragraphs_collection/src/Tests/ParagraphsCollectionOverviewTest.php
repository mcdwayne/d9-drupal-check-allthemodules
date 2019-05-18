<?php

namespace Drupal\paragraphs_collection\Tests;

use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the Paragraphs Collection overview pages.
 *
 * @group paragraphs_collection
 */
class ParagraphsCollectionOverviewTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_collection',
    'paragraphs_collection_demo',
    'paragraphs_collection_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the overview pages of discoverable items.
   *
   * These are the layouts for the grid layout plugin and styles for the style
   * plugin.
   */
  public function testOverviewPages() {
    $this->loginAsAdmin([
      'administer paragraphs types',
      'access site reports',
    ]);

    // Check the new link on the reports page.
    $this->drupalGet('/admin/reports');
    $this->assertText('Overviews of items discoverable by behavior plugins.');
    $this->clickLink('Paragraphs Collection');

    // Check the grid layouts overview.
    $this->assertUrl('/admin/reports/paragraphs_collection/layouts');
    $this->assertTitle('Available grid layouts | Drupal');
    $this->assertText('Grid layout label or ID');
    $this->assertText('Details');
    $this->assertText('Used in');

    // Check that a concrete grid layout is displayed.
    $this->assertText('Three columns 1 - 1 - 2');
    $this->assertText('Three columns layout of 1/4, 1/4 and 1/2 width.');
    $this->assertText('paragraphs_collection_demo_1_1_2_column');
    $this->assertLink('Grid');
    $this->assertLinkByHref('/admin/structure/paragraphs_type/grid');

    // Check the tabs.
    $this->assertLink('Layouts');
    $this->clickLink('Styles');

    // Check the styles layouts overview.
    $this->assertUrl('/admin/reports/paragraphs_collection/styles');
    $this->assertTitle('Available styles | Drupal');
    $this->assertText('Group');
    $this->assertText('Style label or ID');
    $this->assertText('Details');
    $this->assertText('Used in');

    // Check that a concrete style is displayed.
    $this->assertText('Blue');
    $this->assertText('paragraphs-blue');
    $this->assertText('General Group');
    $this->assertLink('Container');
    $this->assertLinkByHref('/admin/structure/paragraphs_type/container');

    // Check the tabs.
    $this->assertLink('Layouts');
    $this->assertLink('Styles');

    // Disable the grid layout and style plugins for all paragraphs types.
    $paragraph_type_ids = \Drupal::entityQuery('paragraphs_type')->execute();
    $paragraphs_types = ParagraphsType::loadMultiple($paragraph_type_ids);
    foreach ($paragraphs_types as $paragraphs_type) {
      /** @var \Drupal\paragraphs\ParagraphsTypeInterface $paragraphs_type */
      $paragraphs_type->getBehaviorPlugin('grid_layout')->setConfiguration(['enabled' => FALSE]);
      $paragraphs_type->getBehaviorPlugin('style')->setConfiguration(['enabled' => FALSE]);
      $paragraphs_type->save();
    }

    // Check the grid layouts overview page displays grid layouts but no
    // paragraphs types.
    $this->drupalGet('/admin/reports/paragraphs_collection/layouts');
    $this->assertText('Three columns 1 - 1 - 2');
    $this->assertText('Three columns layout of 1/4, 1/4 and 1/2 width.');
    $this->assertText('paragraphs_collection_demo_1_1_2_column');
    $this->assertNoLink('Grid');
    $this->assertNoLinkByHref('/admin/structure/paragraphs_type/grid');

    // Check the styles overview page displays styles but no paragraphs types.
    $this->drupalGet('/admin/reports/paragraphs_collection/styles');
    $this->assertText('Blue');
    $this->assertText('paragraphs-blue');
    $this->assertText('General Group');
    $this->assertNoLink('Container');
    $this->assertNoLinkByHref('/admin/structure/paragraphs_type/container');
  }

}
