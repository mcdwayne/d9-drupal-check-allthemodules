<?php

namespace Drupal\Tests\pdb_vue\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the vue_spa_component component.
 *
 * @group pdb_vue
 */
class SpaComponentTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'pdb', 'pdb_vue'];

  /**
   * {@inheritdoc}
   */
  public function testMultipleComponentsInSpaMode() {
    $assert = $this->assertSession();

    $config = $this->config('pdb_vue.settings');
    // Set the values the user submitted in the form.
    $config->set('development_mode', TRUE);
    $config->set('use_spa', TRUE);
    $config->save();

    // Create administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
    ]));

    // Go to the block instance configuration.
    $this->drupalGet('admin/structure/block/add/vue_component%3Avue_spa_component/classy');

    // Check that the pdb_configuration options are available.
    $assert->responseContains('Component Settings');
    $assert->fieldExists('settings[pdb_configuration][textField]');
    // Save the block.
    $this->submitForm(
      [
        'settings[pdb_configuration][textField]' => 'Test Config',
        'region' => 'content',
      ],
      'Save block');

    // Add a second block.
    $this->drupalPlaceBlock('vue_component:vue_spa_component');

    // Go to the home page.
    $this->drupalGet('<front>');

    // Assert that the blocks were placed and have the correct tag and property.
    $assert->responseContains('<vue-spa-component text-field="Test Config" instance-id=');
    $assert->responseContains('<vue-spa-component instance-id=');

    // Check that the spa-init.js library is added after the component js.
    $assert->responseMatches('/vue-spa-component\.js(.|\n)+spa-init\.js/');
  }

}
