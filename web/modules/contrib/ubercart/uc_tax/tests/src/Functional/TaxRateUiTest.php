<?php

namespace Drupal\Tests\uc_tax\Functional;

/**
 * Tests the operation of the tax rate configuration user interface.
 *
 * @group ubercart
 */
class TaxRateUiTest extends TaxTestBase {

  /**
   * Tests the operation of the tax rate configuration user interface.
   */
  public function testTaxUi() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    // Verify tax rate configuration item is listed on store configuration menu.
    $this->drupalGet('admin/store/config');
    $assert->linkByHrefExists('admin/store/config/tax');
    $assert->pageTextContains('Configure tax rates and rules.');
    $this->clickLink('Tax rates');
    $assert->addressEquals('admin/store/config/tax');
    $assert->pageTextContains('No tax rates have been configured yet.');

    // Create a 20% inclusive tax rate.
    $rate = [
      'label' => $this->randomMachineName(8),
      'settings[rate]' => 20,
      'settings[jurisdiction]' => 'Uberland',
      'shippable' => 0,
      'product_types[product]' => 1,
      'product_types[blank-line]' => 1,
      // No shipping line item if uc_quote not installed.
      // 'line_item_types[shipping]' => 1,
      'line_item_types[generic]' => 1,
      'line_item_types[tax]' => 1,
      'display_include' => 1,
      'inclusion_text' => ' incl. tax',
    ];
    $tax_rate = $this->createTaxRate('percentage_rate', $rate);

    $this->drupalGet('admin/store/config/tax');
    // Verify that tax was saved successfully by checking for expected label,
    // rate, taxed shipping types, taxed product types, and taxed line item
    // types.
    $assert->pageTextContains($tax_rate->label());
    $assert->pageTextContains($tax_rate->getRate() . '%');
    // Expected shipping types.
    $assert->pageTextContains('Any product');
    // Expected product types.
    $assert->pageTextContains('product, blank-line');
    // Expected line item types.
    $assert->pageTextContains('generic, tax');

    // Test 'Clone' operation.
    $this->drupalGet('admin/store/config/tax');
    $this->clickLink('Clone');
    $assert->addressEquals('admin/store/config/tax');
    // Check that tax was cloned successfully.
    $assert->pageTextContains('Tax rate ' . $tax_rate->label() . ' was cloned.');

    // Default sort is alphabetical, but we need the clone
    // to be at the top of the list so the next tests work!
    $this->drupalPostForm(
      NULL,
      ['entities[' . $tax_rate->id() . '_clone][weight]' => -10],
      'Save configuration'
    );
    $assert->addressEquals('admin/store/config/tax');

    // Test 'Delete' operation. Delete the Clone.
    $this->clickLink('Delete');
    $assert->addressEquals('admin/store/config/tax/' . $tax_rate->id() . '_clone/delete');
    // Check that delete confirmation form was found.
    $assert->pageTextContains('Are you sure you want to delete Copy of ' . $tax_rate->label() . '?');
    // @todo Commented out until core issue with the Cancel button
    // URL on confirm forms for sites in a subdirectory is fixed.
    // @see https://www.drupal.org/project/drupal/issues/2582295
    /*
    // Verify the 'Cancel' button works.
    $this->clickLink('Cancel');
    $assert->addressEquals('admin/store/config/tax');
    // Check that tax rate was not deleted.
    $assert->pageTextContains('Copy of ' . $tax_rate->label());
    // Now, actually delete the rate.
    $this->clickLink('Delete');
    $assert->addressEquals('admin/store/config/tax/' . $tax_rate->id() . '_clone/delete');
    */
    $this->drupalPostForm(NULL, [], 'Delete tax rate');
    $assert->addressEquals('admin/store/config/tax');
    $assert->pageTextContains('Tax rate Copy of ' . $tax_rate->label() . ' has been deleted.');
    // Go to next page to clear the drupal_set_message.
    $this->drupalGet('admin/store/config/tax');
    // Check that the deleted tax rate no longer appears.
    $assert->pageTextNotContains('Copy of ' . $tax_rate->label());

    // Test 'Disable' operation.
    $this->drupalGet('admin/store/config/tax');
    $this->clickLink('Disable');
    $assert->addressEquals('admin/store/config/tax');
    $assert->pageTextContains('The ' . $tax_rate->label() . ' tax rate has been disabled.');
    // Test 'Enable' operation.
    $this->clickLink('Enable');
    $assert->addressEquals('admin/store/config/tax');
    $assert->pageTextContains('The ' . $tax_rate->label() . ' tax rate has been enabled.');

    // Test 'Edit' operation.
    $this->drupalGet('admin/store/config/tax');
    $this->clickLink('Edit');
    $assert->addressEquals('admin/store/config/tax/' . $tax_rate->id());
    // Test for known fields.
    $assert->pageTextContains('Default tax rate');
    $assert->pageTextContains('Tax rate override field');
    $assert->pageTextContains('Jurisdiction');
    $assert->pageTextContains('Taxed products');
    $assert->pageTextContains('Taxed product types');
    $assert->pageTextContains('Taxed line items');
    $assert->pageTextContains('Tax inclusion text');
    // Test for Save tax rate button, Cancel link, delete link.
    $assert->linkExists('Cancel');
    // We have already tested delete.
    $assert->linkExists('Delete');
    // Test cancel.
    $this->clickLink('Cancel');
    $assert->addressEquals('admin/store/config/tax');

    // Test 'Add' operation.
    $this->drupalPostForm(NULL, ['plugin' => 'percentage_rate'], 'Add tax rate');
    $assert->addressEquals('admin/store/config/tax/add/percentage_rate');
    // Test for same known fields as above.
    $assert->pageTextContains('Default tax rate');
    $assert->pageTextContains('Tax rate override field');
    $assert->pageTextContains('Jurisdiction');
    $assert->pageTextContains('Taxed products');
    $assert->pageTextContains('Taxed product types');
    $assert->pageTextContains('Taxed line items');
    $assert->pageTextContains('Tax inclusion text');
    // Test for Save tax rate button, Cancel link, no delete link.
    $assert->linkExists('Cancel');
    $assert->linkNotExists('Delete');
  }

}
