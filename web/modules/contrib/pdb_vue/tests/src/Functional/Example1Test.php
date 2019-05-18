<?php

namespace Drupal\Tests\pdb_vue\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the vue_example_1 component.
 *
 * @group pdb_vue
 */
class Example1Test extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'pdb', 'pdb_vue'];

  /**
   * {@inheritdoc}
   */
  public function testNoExamplesInProductionMode() {
    $assert = $this->assertSession();

    // Create administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
    ]));

    $this->drupalGet('admin/structure/block/library/classy');
    $assert->responseContains('PDB Vue.js');

    // Assert that no Vue Example blocks are available in Development.
    $assert->responseNotContains('Vue Example 1');
  }

  /**
   * Test that the Vue Example 1 block displays.
   */
  public function testExample1BlockAppears() {
    $assert = $this->assertSession();

    $config = $this->config('pdb_vue.settings');
    // Set the values the user submitted in the form.
    $config->set('development_mode', TRUE);
    $config->save();

    // Create administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
    ]));

    // Check that the Vue Example Block is available.
    $this->drupalGet('admin/structure/block/library/classy');
    $assert->responseContains('Vue Example 1');

    // Place the "Vue Example 1" block.
    $this->drupalPlaceBlock('vue_component:vue_example_1');

    // Go to the home page.
    $this->drupalGet('<front>');

    // Assert that the block was placed and has the correct class.
    $assert->responseContains('vue-example-1');

    // Check that the vue.js library was added.
    $assert->responseContains('//cdnjs.cloudflare.com/ajax/libs/vue/');
    $assert->responseContains('vue-example-1.js');
  }

}
