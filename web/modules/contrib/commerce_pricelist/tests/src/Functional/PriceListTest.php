<?php

namespace Drupal\Tests\commerce_pricelist\Functional;

use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the price list UI.
 *
 * @group commerce_pricelist
 */
class PriceListTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_pricelist',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_pricelist',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating a price list.
   */
  public function testCreatePriceList() {
    $this->drupalGet(Url::fromRoute('entity.commerce_pricelist.collection')->toString());
    $this->clickLink('Add price list');

    $roles = $this->adminUser->getRoles();
    $role = reset($roles);
    $this->submitForm([
      'name[0][value]' => 'Black Friday 2018',
      'start_date[0][value][date]' => '2018-07-07',
      'customer_eligibility' => 'customer_roles',
      "customer_roles[$role]" => $role,
      // The customer should not be persisted due to the role being used.
      'customer[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved the Black Friday 2018 price list.');

    $price_list = PriceList::load(1);
    $this->assertEquals('Black Friday 2018', $price_list->getName());
    $this->assertEquals('2018-07-07', $price_list->getStartDate()->format('Y-m-d'));
    $this->assertEquals([$role], $price_list->getCustomerRoles());
    $this->assertEmpty($price_list->getCustomerId());
  }

  /**
   * Tests editing a price list.
   */
  public function testEditPriceList() {
    $roles = $this->adminUser->getRoles();
    $role = reset($roles);
    $price_list = $this->createEntity('commerce_pricelist', [
      'type' => 'commerce_product_variation',
      'name' => $this->randomMachineName(8),
      'start_date' => '2018-07-07',
      'customer_role' => $role,
    ]);
    $this->drupalGet($price_list->toUrl('edit-form'));
    $page = $this->getSession()->getPage();
    $tabs = $page->find('xpath', '//nav');
    $this->assertNotEmpty($tabs->findLink('Edit'));
    $this->assertNotEmpty($tabs->findLink('Prices'));
    $this->submitForm([
      'name[0][value]' => 'Random list',
      'start_date[0][value][date]' => '2018-08-08',
      'customer_eligibility' => 'customer',
      'customer[0][target_id]' => $this->adminUser->label() . ' (' . $this->adminUser->id() . ')',
      // The role should not be persisted due to the customer being used.
      "customer_roles[$role]" => $role,
    ], 'Save');

    \Drupal::service('entity_type.manager')->getStorage('commerce_pricelist')->resetCache([$price_list->id()]);
    $price_list = PriceList::load(1);
    $this->assertEquals('Random list', $price_list->getName());
    $this->assertEquals('2018-08-08', $price_list->getStartDate()->format('Y-m-d'));
    $this->assertEmpty($price_list->getCustomerRoles());
    $this->assertEquals($this->adminUser->id(), $price_list->getCustomerId());
  }

  /**
   * Tests deleting a price list.
   */
  public function testDeletePriceList() {
    $price_list = $this->createEntity('commerce_pricelist', [
      'type' => 'commerce_product_variation',
      'name' => $this->randomMachineName(8),
    ]);
    $this->drupalGet($price_list->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], t('Delete'));

    \Drupal::service('entity_type.manager')->getStorage('commerce_pricelist')->resetCache([$price_list->id()]);
    $price_list_exists = (bool) PriceList::load($price_list->id());
    $this->assertFalse($price_list_exists);
  }

}
