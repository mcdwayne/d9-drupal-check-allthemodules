<?php

namespace Drupal\Tests\ckeditor_blockimagepaste;

use Drupal\TestSite\TestSetupInterface;

/**
 * Setup file used by dvg_notification Nightwatch tests.
 *
 * @see \Drupal\Tests\Scripts\TestSiteApplicationTest
 */
class TestSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {

    // Enable all the required modules.
    \Drupal::service('module_installer')->install(['node']);
    \Drupal::service('module_installer')->install(['editor']);
    \Drupal::service('module_installer')->install(['ckeditor']);
    \Drupal::service('module_installer')->install(['ckeditor_blockimagepaste']);
    \Drupal::service('module_installer')->install(['image_test_page']);

  }

}
