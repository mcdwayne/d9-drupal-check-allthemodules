<?php

namespace Drupal\Tests\config_selector\Functional;

use Drupal\config_selector\Entity\Feature;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the profile supplied configuration can be selected.
 *
 * @group config_selector
 */
class ConfigSelectorUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_selector_ui_test', 'config_selector_test_one'];

  /**
   * Tests the Configuration Selector UI.
   */
  public function testUi() {
    // This label is different in 8.5.x and 8.6.x therefore get it
    // programmatically.
    $views_plural_label = \Drupal::entityTypeManager()->getDefinition('view')->getPluralLabel();
    $assert = $this->assertSession();
    $user = $this->createUser(['access administration pages', 'administer site configuration']);
    $this->drupalLogin($user);

    // Ensure the UI is linked from admin/structure.
    $this->drupalGet('admin/structure');
    $assert->linkExists('Configuration Selector features');
    $this->clickLink('Configuration Selector features');

    // Test the listing form.
    $assert->responseContains('Feature test');
    $assert->responseContains('The main view for editing content');
    $assert->linkExists('Manage configuration');
    $this->clickLink('Manage configuration');

    // Test the manage configuration form.
    $assert->linkNotExists('Edit configuration');
    $this->container->get('module_installer')->install(['views_ui']);
    $this->getSession()->reload();
    $assert->linkExists('Edit configuration');

    // Test the default configuration.
    $this->assertEquals($views_plural_label, $this->xpath('//table[@id="edit-table"]/tbody/tr[1]/td[1]')[0]->getText());
    $this->assertEquals('feature_test_3', $this->xpath('//table[@id="edit-table"]/tbody/tr[2]/td[1]')[0]->getText());
    $this->assertEquals('The best view', $this->xpath('//table[@id="edit-table"]/tbody/tr[2]/td[2]')[0]->getText());
    $this->assertEquals('3', $this->xpath('//table[@id="edit-table"]/tbody/tr[2]/td[3]')[0]->getText());
    $this->assertEquals('Enabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[2]/td[4]')[0]->getText());
    $this->assertEquals('feature_test_2', $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[1]')[0]->getText());
    $this->assertEquals('An even better view', $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[2]')[0]->getText());
    $this->assertEquals('2', $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[3]')[0]->getText());
    $this->assertEquals('Disabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[4]')[0]->getText());
    $this->assertEquals('feature_test_1', $this->xpath('//table[@id="edit-table"]/tbody/tr[4]/td[1]')[0]->getText());
    $this->assertEquals('A good view', $this->xpath('//table[@id="edit-table"]/tbody/tr[4]/td[2]')[0]->getText());
    $this->assertEquals('1', $this->xpath('//table[@id="edit-table"]/tbody/tr[4]/td[3]')[0]->getText());
    $this->assertEquals('Disabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[4]/td[4]')[0]->getText());

    // Switch to feature_test_1.
    $this->xpath('//table[@id="edit-table"]/tbody/tr[4]/td[5]')[0]->clickLink('Select');
    $this->assertSession()->pageTextContains("Configuration entity feature_test_1 has been selected.");
    $this->assertEquals('Enabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[4]/td[4]')[0]->getText());
    $this->assertEquals('Disabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[2]/td[4]')[0]->getText());
    $this->assertEquals('Disabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[4]')[0]->getText());
    // Test the views. Need to clear some static caches to get them loaded
    // correctly.
    $this->rebuildContainer();
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    $this->assertTrue($view_storage->load('feature_test_1')->status());
    $this->assertFalse($view_storage->load('feature_test_2')->status());
    $this->assertFalse($view_storage->load('feature_test_3')->status());

    // Switch to feature_test_2.
    $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[5]')[0]->clickLink('Select');
    $this->assertSession()->pageTextContains("Configuration entity feature_test_2 has been selected.");
    $this->assertEquals('Disabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[2]/td[4]')[0]->getText());
    $this->assertEquals('Enabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[4]')[0]->getText());
    $this->assertEquals('Disabled', $this->xpath('//table[@id="edit-table"]/tbody/tr[4]/td[4]')[0]->getText());
    // Test the views. Need to clear some static caches to get them loaded
    // correctly.
    $this->rebuildContainer();
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    $this->assertFalse($view_storage->load('feature_test_1')->status());
    $this->assertTrue($view_storage->load('feature_test_2')->status());
    $this->assertFalse($view_storage->load('feature_test_3')->status());

    // Add another type of configuration entity to the feature.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $configs */
    $configs = \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple();
    $configs['feature_a_one']
      ->setThirdPartySetting('config_selector', 'feature', 'feature_test')
      ->setThirdPartySetting('config_selector', 'description', 'A fallback description')
      ->save();
    $this->getSession()->reload();
    // Test we now have two entity types.
    $this->assertEquals(\Drupal::entityTypeManager()->getDefinition('config_test')->getPluralLabel(), $this->xpath('//table[@id="edit-table"]/tbody/tr[1]/td[1]')[0]->getText());
    $this->assertEquals($views_plural_label, $this->xpath('//table[@id="edit-table"]/tbody/tr[3]/td[1]')[0]->getText());

    // Ensure that the fallback description is used for entities that don't
    // implement getDescription or have a 'description' property.
    $this->assertEquals('A fallback description', $this->xpath('//table[@id="edit-table"]/tbody/tr[2]/td[2]')[0]->getText());

    // Delete all the configuration and test the empty text shows.
    $feature = Feature::load('feature_test');
    foreach ($feature->getConfiguration() as $config_entities) {
      foreach ($config_entities as $config_entity) {
        $config_entity->delete();
      }
    }
    $this->getSession()->reload();
    $this->assertSession()->pageTextContains('The feature has no configuration.');

    // Delete the feature and ensure the UI is not available.
    $feature->delete();
    $this->drupalGet('admin/structure');
    $assert->linkNotExists('Configuration Selector features');
    $this->drupalGet('admin/structure/config_selector');
    $assert->statusCodeEquals(403);
  }

}
