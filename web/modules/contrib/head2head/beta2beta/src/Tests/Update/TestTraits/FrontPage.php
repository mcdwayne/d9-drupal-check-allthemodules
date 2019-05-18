<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\TestTraits\FrontPage.
 */

namespace Drupal\beta2beta\Tests\Update\TestTraits;

/**
 * Trait for testing the front page after updating a site.
 *
 * This test will only pass if there is not any front page content, meaning it
 * generally will only be used when testing the empty install profile upgrades.
 */
trait FrontPage {

  /**
   * Tests the front page.
   */
  public function testUpgrade() {
    $this->runUpdates();

    // Browse around a bit.
    $this->drupalGet('<front>');
    $this->assertResponse(200);
    $this->assertText('Site-Install');
    $this->assertText(t('No front page content has been created yet.'));
  }

}
