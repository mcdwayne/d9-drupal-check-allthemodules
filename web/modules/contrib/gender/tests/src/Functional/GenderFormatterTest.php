<?php

namespace Drupal\Tests\gender\Functional;

use Drupal\Core\Url;

/**
 * Tests the default gender field formatter, which is list_default.
 *
 * @group gender
 */
class GenderFormatterTest extends GenderTestBase {

  /**
   * Tests that selected values are displayed properly by the formatter.
   */
  public function testDefaultFormatter() {
    // Create the URL to the node view page.
    $node_view_url = Url::fromRoute('entity.node.canonical', [
      'node' => $this->node->id(),
    ]);
    // Load the node page.
    $this->drupalGet($node_view_url);
    // Get the list of all gender options.
    $gender_options = gender_options();
    // Assert that the label for each options appears on the node view page.
    foreach ($this->genderList as $gender) {
      $this->assertSession()->pageTextContains($gender_options[$gender]);
    }
  }

}
