<?php
namespace Drupal\pagarme_marketplace\Tests\Functional;
use Drupal\commerce\EntityHelper;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\pagarme_marketplace\Tests\Functional\PagarmeMarketplaceTestCase;
/**
 * Tests the split delete functionality.
 *
 * @group pagarme_marketplace
 */
class PagarmeMarketplaceSplitDeleteTestCase extends PagarmeMarketplaceTestCase {
  /**
   * Disable strict config schema checking.
   *
   * The schema is verified at the end of running the update.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;
  public static function getInfo() {
    return array(
      'name' => 'Split delete',
      'description' => 'Create a split and test split delete functionality.',
      'group' => 'Pagarme Marketplace',
    );
  }

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Checks split delete functionality.
   */
  public function testSplitDelete() {
    $this->drupalLogin($this->adminUser);
    /* Creating product */
    $this->drupalGet('admin/commerce/products');
    $this->getSession()->getPage()->clickLink(t('Add product'));
    /* Check the integrity of the add form. */
    $this->assertSession()->fieldExists('title[0][value]');
    $this->assertSession()->fieldExists('variations[form][inline_entity_form][sku][0][value]');
    $this->assertSession()->fieldExists('variations[form][inline_entity_form][price][0][number]');
    $this->assertSession()->fieldExists('variations[form][inline_entity_form][status][value]');
    $this->assertSession()->buttonExists('Create variation');

    $store_ids = EntityHelper::extractIds($this->stores);
    $title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $title,
    ];
    foreach ($store_ids as $store_id) {
      $edit['stores[target_id][value][' . $store_id . ']'] = $store_id;
    }
    $product_sku = '89750947';
    $variations_edit = [
      'variations[form][inline_entity_form][sku][0][value]' => $product_sku,
      'variations[form][inline_entity_form][price][0][number]' => '100.00',
      'variations[form][inline_entity_form][status][value]' => 1,
    ];
    $this->submitForm($variations_edit, t('Create variation'));
    $this->submitForm($edit, t('Save and publish'));

    $result = \Drupal::entityQuery('commerce_product')
      ->condition("title", $edit['title[0][value]'])
      ->range(0, 1)
      ->execute();
    $product_id = reset($result);
    $product = Product::load($product_id);

    $this->assertNotNull($product, 'The new product has been created.');
    $this->assertSession()->pageTextContains(t('The product @title has been successfully saved', ['@title' => $title]));
    $this->assertSession()->pageTextContains($title);
    $this->assertFieldValues($product->getStores(), $this->stores, 'Created product has the correct associated stores.');
    $this->assertFieldValues($product->getStoreIds(), $store_ids, 'Created product has the correct associated store ids.');
    $this->drupalGet($product->toUrl('canonical'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($product->getTitle());
    $variation = \Drupal::entityQuery('commerce_product_variation')
      ->condition('sku', $product_sku)
      ->range(0, 1)
      ->execute();
    $variation_id = current($variation);
    $variation = ProductVariation::load($variation_id);
    $this->assertNotNull($variation, 'The new product variation has been created.');
    /* Create a simple split */
    $data_split = $this->dataDummySplit($product_id, '100.00');
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/splits/add/' . $variation_id);
    $this->submitForm($data_split, t('Save rule'));
    $this->assertSession()->responseContains(t('Split rule saved.'));
    /* Get split object from the database */
    $split = $this->getSplitByProductVariationId($variation_id);
    $create_split = FALSE;
    $split_id = NULL;
    if (!empty($split->split_id)) {
      $split_id = $split->split_id;
      $create_split = TRUE;
    }
    /* Check that the recipient exists in the database. */
    $this->assertTrue($create_split, 'Split found in database.');
    $this->drupalGet('admin/commerce/config/marketplace/'. $this->api_key .'/splits/delete/' . $split_id);
    $this->submitForm([], t('Delete'));
    // Check if split was successfully deleted.
    $split = $this->getSplitByProductVariationId($variation_id);
    $this->assertTrue((empty($split)), 'Split successfully deleted.');
  }
}
