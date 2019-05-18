<?php

namespace Drupal\Tests\eff_action\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests EFF Action module.
 *
 * @group EFF
 */
class EffActionTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['eff_action'];

  /**
   * Tests EFF Action module.
   */
  public function testEffAction() {
    $this->drupalGet('<front>');
    $elements = $this->xpath('//script[@src="https://www.eff.org/doa/widget.min.js"]');
    $this->assertTrue(isset($elements[0]), 'Script tag found.');
  }

}
