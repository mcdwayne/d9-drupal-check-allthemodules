<?php

namespace Drupal\Tests\feature_toggle\Functional;

use Drupal\feature_toggle\Feature;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Feature Toggle JS integration.
 *
 * @group feature_toggle
 */
class FeatureToggleJavascriptTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['feature_toggle'];

  /**
   * The web assert object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $assertSession;

  /**
   * The feature manager service.
   *
   * @var \Drupal\feature_toggle\FeatureManagerInterface
   */
  protected $featureManager;

  /**
   * The feature status service.
   *
   * @var \Drupal\feature_toggle\FeatureStatusInterface
   */
  protected $featureStatus;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->assertSession = $this->assertSession();
    $this->featureManager = $this->container->get('feature_toggle.feature_manager');
    $this->featureStatus = $this->container->get('feature_toggle.feature_status');

    // Create a web user.
    $this->drupalLogin($this->drupalCreateUser(['administer feature_toggle']));
  }

  /**
   * Tests the feature main workflow.
   */
  public function testJavascriptSettings() {
    $features = [];
    for ($i = 0; $i < 10; $i++) {
      $feature = new Feature(strtolower($this->randomMachineName()), $this->randomMachineName());
      $this->featureManager->addFeature($feature);
      $this->featureStatus->setStatus($feature, TRUE);
      $features[] = $feature;
    }

    // Check that both features are set to 1 in drupalSettings.
    $this->drupalGet('admin/config/system/feature_toggle');
    $settings = $this->getDrupalSettings();
    $feature_toggle_data = $settings['feature_toggle']['enabled'];
    foreach ($features as $feature) {
      $this->assertSession->assert(in_array($feature->name(), $feature_toggle_data), 'Variable present');
      // Set features to 0.
      $this->featureStatus->setStatus($feature, FALSE);
    }

    // Check that features are not present.
    $this->drupalGet('');
    $settings = $this->getDrupalSettings();
    $feature_toggle_data = $settings['feature_toggle']['enabled'];
    foreach ($features as $key => $feature) {
      $this->assertSession->assert(!in_array($feature->name(), $feature_toggle_data), 'Variable not present');
      // Enable only even features.
      if ($key % 2 == 0) {
        $this->featureStatus->setStatus($feature, TRUE);
      }
    }

    // Check that odd features are not present.
    $this->drupalGet('admin/config/system/feature_toggle');
    $settings = $this->getDrupalSettings();
    $feature_toggle_data = $settings['feature_toggle']['enabled'];
    foreach ($features as $key => $feature) {
      if ($key % 2 == 0) {
        $this->assertSession->assert(in_array($feature->name(), $feature_toggle_data), 'Variable present');
      }
      else {
        $this->assertSession->assert(!in_array($feature->name(), $feature_toggle_data), 'Variable not present');
      }
    }

    foreach ($features as $feature) {
      $this->featureManager->deleteFeature($feature->name());
    }

    // Check that features are not present.
    $this->drupalGet('');
    $settings = $this->getDrupalSettings();
    $feature_toggle_data = $settings['feature_toggle']['enabled'];
    $this->assertSession->assert(empty($feature_toggle_data), 'Array is empty');
  }

}
