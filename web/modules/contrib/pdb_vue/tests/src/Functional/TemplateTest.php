<?php

namespace Drupal\Tests\pdb_vue\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the vue_example_2 component with an html template.
 *
 * @group pdb_vue
 */
class TemplateTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'pdb', 'pdb_vue'];

  /**
   * Test that the Vue Example 2 block displays using a template.
   */
  public function testExample2BlockUsesTemplate() {
    $assert = $this->assertSession();

    $config = $this->config('pdb_vue.settings');
    // Set the values the user submitted in the form.
    $config->set('development_mode', TRUE);
    $config->save();

    // Place the "Vue Example 1" block.
    $this->drupalPlaceBlock('vue_component:vue_example_2');

    // Go to the home page.
    $this->drupalGet('<front>');

    // Assert that the block was placed and has the correct class.
    $assert->responseContains('vue-example-2');
    $assert->responseContains('class="test"');
  }

}
