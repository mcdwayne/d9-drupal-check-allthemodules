<?php

namespace Drupal\Tests\uc_catalog\Functional;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;

/**
 * Tests the catalog block functionality.
 *
 * @group ubercart
 */
class CatalogBlockTest extends CatalogTestBase {

  public static $modules = ['uc_catalog', 'block'];
  public static $adminPermissions = [
    'administer catalog',
    'view catalog',
    'administer blocks',
  ];

  /**
   * The catalog block being tested.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->block = $this->drupalPlaceBlock('uc_catalog_block');
  }

  /**
   * Tests catalog block basic functionality.
   */
  public function testCatalogBlock() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Confirm configuration defaults on the block settings page.
    $configuration = $this->block->getPlugin()->getConfiguration();
    $this->assertFalse($configuration['link_title']);
    $this->assertFalse($configuration['expanded']);
    $this->assertTrue($configuration['product_count']);
    $this->assertEquals(BlockPluginInterface::BLOCK_LABEL_VISIBLE, $configuration['label_display']);

    // Create a taxonomy term to use as a catalog category.
    $term = $this->createCatalogTerm();
    // Create product and put it in this category.
    $product = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);

    // Test the catalog block with one product.
    $this->drupalGet('');
    // If the block is present, we should see both
    // the block title and the term title.
    $assert->pageTextContains($this->block->label());
    $assert->pageTextContains($term->label());
    $assert->linkExists($term->label() . ' (1)', 0, 'The category is listed in the catalog block.');

    // Click through to catalog category page, verify product is there.
    $this->clickLink($term->label() . ' (1)');
    $assert->titleEquals($term->label() . ' | Drupal');
    $assert->linkExists($product->label(), 0, 'The product is listed in the catalog.');
  }

  /**
   * Tests the optional block title link to catalog page functionality.
   */
  public function testTitleLink() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Title link is turned off by default.
    $this->drupalGet('');
    $assert->pageTextContains($this->block->label());
    $assert->linkNotExists($this->block->label(), 0, 'The block title is not a link.');
    $assert->linkByHrefNotExists('catalog', 0, 'The block title is not linked to the catalog page.');

    // Turn on title link.
    $this->block->getPlugin()->setConfigurationValue('link_title', TRUE);
    $this->block->save();

    // Verify title is now linked.
    $this->drupalGet('');
    $assert->linkExists($this->block->label(), 0, 'The block title is a link.');
    $assert->linkByHrefExists('catalog', 0, 'The block title is linked to the catalog page.');
  }

  /**
   * Tests the expand catalog categories functionality.
   */
  public function testExpandCategories() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Top level category with two children.
    $parent = $this->createCatalogTerm();
    $child = [];
    $child[1] = $this->createCatalogTerm(['parent' => $parent->id()]);
    $child[2] = $this->createCatalogTerm(['parent' => $parent->id()]);

    // Create first product in this category.
    $product = [];
    $product[1] = $this->createProduct([
      'taxonomy_catalog' => [$child[1]->id()],
    ]);

    // Create second product in a different category.
    $product[2] = $this->createProduct([
      'taxonomy_catalog' => [$child[2]->id()],
    ]);

    // Categories are not expanded by default.
    $this->drupalGet('');
    $assert->pageTextContains($parent->label());
    $assert->linkExists($parent->label() . ' (2)', 0, 'Product count is shown for top-level term.');
    $assert->pageTextNotContains($child[1]->label());
    $assert->linkNotExists($child[1]->label() . ' (1)', 0, 'Product count is not shown for child term.');
    $assert->pageTextNotContains($child[2]->label());
    $assert->linkNotExists($child[2]->label() . ' (1)', 0, 'Product count is not shown for child term.');

    // Turn on expanded term display.
    $this->block->getPlugin()->setConfigurationValue('expanded', TRUE);
    $this->block->save();
    // @todo Catalog theme function doesn't use block configuration values,
    // it duplicates that information in the module config instead. This
    // needs to be fixed! (See also todo in CatalogBlock plugin.) But until
    // then, we need to set the config value here so that the link_title
    // functionality will work.
    \Drupal::configFactory()->getEditable('uc_catalog.settings')->set('expand_categories', TRUE)->save();

    // Verify catalog is expanded to show all levels.
    $this->drupalGet('');
    $assert->pageTextContains($parent->label());
    $assert->linkExists($parent->label() . ' (2)', 0, 'Product count is shown for top-level term.');
    $assert->linkExists($child[1]->label() . ' (1)', 0, 'Product count is shown for child term.');
    $assert->linkExists($child[2]->label() . ' (1)', 0, 'Product count is shown for child term.');
  }

  /**
   * Tests display of product counts in catalog block.
   */
  public function testProductCountDisplay() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a taxonomy term to use as a catalog category.
    $term = $this->createCatalogTerm();
    $product = [];
    // Create first product in this category.
    $product[] = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);

    // Show product counts is the default.
    $this->drupalGet('');
    $assert->pageTextContains($term->label());
    $assert->linkExists($term->label() . ' (1)', 0, 'Product count is shown.');

    // Create second product in this category.
    $product[] = $this->createProduct([
      'taxonomy_catalog' => [$term->id()],
    ]);
    // @todo Remove this when the CatalogBlock implements caching properly.
    Cache::invalidateTags($this->block->getCacheTags());

    // Now there should be two products in this category.
    // This also tests the catalog block caching, because if caching isn't done
    // properly then we won't see the additional product here.
    $this->drupalGet('');
    $assert->pageTextContains($term->label());
    $assert->linkExists($term->label() . ' (2)', 0, 'Product count of 2 is shown.');

    // Turn off product count display.
    $this->block->getPlugin()->setConfigurationValue('product_count', FALSE);
    $this->block->save();
    // @todo Catalog theme function doesn't use block configuration values,
    // it duplicates that information in the module config instead. This
    // needs to be fixed! (See also todo in CatalogBlock plugin.) But until
    // then, we need to set the config value here so that the link_title
    // functionality will work.
    \Drupal::configFactory()->getEditable('uc_catalog.settings')->set('block_nodecount', FALSE)->save();

    // Product count should no longer show.
    $this->drupalGet('');
    $assert->pageTextContains($term->label());
    $assert->linkExists($term->label(), 0, 'Product count is not shown.');
  }

}
