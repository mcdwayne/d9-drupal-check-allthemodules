<?php

namespace Drupal\Tests\commerce_addon\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests CRUD with addons.
 *
 * @group commerce_addon
 */
class AddonAdminTest extends CommerceBrowserTestBase {

  public static $modules = [
    'commerce_addon',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_addon',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests creating an addon.
   */
  public function testCreateAddon() {
    $this->drupalGet(Url::fromRoute('entity.commerce_addon.collection'));
    $this->getSession()->getPage()->clickLink('Add an add-on');

    $this->getSession()->getPage()->fillField('Title', 'Super powers');
    $this->getSession()->getPage()->fillField('Price', '25.00');
    $this->getSession()->getPage()->fillField('Description', 'Select to get super powers');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Super powers');
    $this->assertSession()->pageTextContains('$25.00');
  }

}
