<?php

namespace Drupal\Tests\config_selector\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the profile supplied configuration can be selected.
 *
 * @group config_selector
 */
class ConfigSelectorProfileTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'config_selector_profile_test';

  /**
   * Tests the profile supplied configuration can be selected.
   */
  public function testProfileInstall() {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $configs */
    $configs = \Drupal::entityTypeManager()->getStorage('config_test')->loadMultiple();

    $this->assertTrue($configs['feature_a_five']->status());
    // The profile supplies configuration with a higher priority.
    $this->assertFalse($configs['feature_a_two']->status());
    // Lower priority than feature_a_two.
    $this->assertFalse($configs['feature_a_one']->status());
    // Lower priority than feature_a_two.
    $this->assertFalse($configs['feature_a_three']->status());
    // Higher priority but it is disabled in default configuration.
    $this->assertFalse($configs['feature_a_four']->status());

    // Module supplied configuration.
    $this->assertTrue($configs['feature_b_two']->status());
    $this->assertFalse($configs['feature_b_one']->status());
    $this->assertTrue($configs['feature_c_one']->status());

    // Profile supplied configuration.
    $this->assertTrue($configs['feature_d_two']->status());
    $this->assertFalse($configs['feature_d_one']->status());
  }

}
