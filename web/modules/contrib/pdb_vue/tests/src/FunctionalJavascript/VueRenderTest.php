<?php

namespace Drupal\Tests\pdb_vue\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Vue Render tests.
 *
 * @group pdb_vue
 */
class VueRenderTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'pdb',
    'pdb_vue',
  ];

  /**
   * Test that a Vue instance renders.
   */
  public function testExample1Renders() {
    $assert = $this->assertSession();

    $config = $this->config('pdb_vue.settings');
    // Set the values the user submitted in the form.
    $config->set('development_mode', TRUE);
    $config->save();

    // Place the "Vue Example 1" block.
    $this->drupalPlaceBlock('vue_component:vue_example_1');

    $this->drupalGet('<front>');

    $assert->waitForElement('css', '.test');
    $assert->pageTextContains("Hello Vue");
  }

  /**
   * Test that Vue SPA Components render.
   */
  public function testSpaComponentsRenders() {
    $assert = $this->assertSession();

    $config = $this->config('pdb_vue.settings');
    // Set the values the user submitted in the form.
    $config->set('development_mode', TRUE);
    $config->set('use_spa', TRUE);
    $config->set('spa_element', 'main');
    $config->save();

    // Place 3 "SPA Component" blocks.
    $this->drupalPlaceBlock('vue_component:vue_spa_component',
      [
        'pdb_configuration' => [
          'textField' => 'component 1',
        ],
      ]
    );
    $this->drupalPlaceBlock('vue_component:vue_spa_component',
      [
        'pdb_configuration' => [
          'textField' => 'component 2',
        ],
      ]
    );
    $this->drupalPlaceBlock('vue_component:vue_spa_component',
      [
        'pdb_configuration' => [
          'textField' => 'component 3',
        ],
      ]
    );

    $this->drupalGet('<front>');

    $assert->waitForElement('css', '.test');
    $assert->pageTextContains("component 1");
    $assert->pageTextContains("component 2");
    $assert->pageTextContains("component 3");
  }

}
