<?php

namespace Drupal\Tests\commerce_addon\Functional;

use Drupal\commerce_addon\Entity\AddonType;
use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests CRUD with addons.
 *
 * @group commerce_addon
 */
class AddonTypeAdminTest extends CommerceBrowserTestBase {

  public static $modules = [
    'commerce_addon',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_addon',
      'administer commerce_addon_type',
    ], parent::getAdministratorPermissions());
  }

  public function testCreateAddonType() {
    $url = Url::fromRoute('entity.commerce_addon_type.collection');
    $this->drupalGet($url);
    $this->getSession()->getPage()->clickLink('Add addon type');

    $values = [
      'id' => 'foo',
      'label' => 'Label of foo',
    ];
    $this->submitForm($values, 'Save');
    $type = AddonType::load('foo');
    $this->assertNotEmpty($type);
  }

}
