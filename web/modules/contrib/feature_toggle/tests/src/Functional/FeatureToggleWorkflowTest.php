<?php

namespace Drupal\Tests\feature_toggle\Functional;

use Drupal\feature_toggle\Feature;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Feature Toggle workflow.
 *
 * @group feature_toggle
 */
class FeatureToggleWorkflowTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['feature_toggle'];

  /**
   * The feature manager service.
   *
   * @var \Drupal\feature_toggle\FeatureManagerInterface
   */
  protected $featureManager;

  /**
   * The web assert object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $assertSession;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->assertSession = $this->assertSession();
    $this->featureManager = $this->container->get('feature_toggle.feature_manager');
  }

  /**
   * Tests the feature main workflow.
   */
  public function testAdminWorkflow() {
    // Create a web admin user.
    $this->drupalLogin($this->drupalCreateUser(['administer feature_toggle']));

    // With no features, empty message should be shown.
    $this->drupalGet('admin/config/system/feature_toggle');
    $this->assertSession->pageTextContains('No features are available to toggle.');

    // Create a new feature.
    $name = strtolower($this->randomMachineName());
    $label = $this->randomString();
    $form_data = [
      'edit-name' => $name,
      'edit-label' => $label,
    ];
    $this->drupalPostForm('admin/config/system/feature_toggle/add', $form_data, t('Save'));

    // New feature created should be shown.
    $this->assertSession->linkByHrefExists('admin/config/system/feature_toggle/' . $name . '/delete');
    $this->assertSession->fieldExists($name);
    $this->assertSession->pageTextContains('Feature ' . $label . ' saved successfully.');

    // Toggle the feature.
    $form_data = [
      $name => 1,
    ];
    $this->drupalPostForm(NULL, $form_data, t('Save'));

    // Check that checkbox is checked.
    $this->assertSession->checkboxChecked($name);

    // Toggle the feature.
    $form_data = [
      $name => 0,
    ];
    $this->drupalPostForm(NULL, $form_data, t('Save'));

    // Check that checkbox is  not checked.
    $this->assertSession->checkboxNotChecked($name);

    // Delete the feature.
    $this->clickLink('Delete');
    $this->assertSession->addressEquals('admin/config/system/feature_toggle/' . $name . '/delete');
    $this->assertSession->pageTextContains('Are you sure you want to delete the feature ' . $label . '?');
    $this->drupalPostForm(NULL, [], t('Delete'));

    // Confirm that feature has been removed.
    $this->assertSession->linkByHrefNotExists('admin/config/system/feature_toggle/' . $name . '/delete');
    $this->assertSession->fieldNotExists($name);
    $this->assertSession->pageTextContains('Feature ' . $label . ' deleted successfully.');
  }

  /**
   * Tests the feature main workflow.
   */
  public function aatestEditWorkflow() {
    // Create a web edit features user.
    $this->drupalLogin($this->drupalCreateUser(['modify feature_toggle status']));

    // With no features, empty message should be shown.
    $this->drupalGet('admin/config/system/feature_toggle');
    $this->assertSession->pageTextContains('No features are available to toggle.');

    // User should not have access to add page.
    $this->drupalGet('admin/config/system/feature_toggle/add');
    $this->assertSession->statusCodeEquals(403);

    // Create a new feature programmatically.
    $name = strtolower($this->randomMachineName());
    $label = $this->randomMachineName();
    $feature = new Feature($name, $label);
    $this->featureManager->addFeature($feature);

    // New feature created should be shown.
    $this->drupalGet('admin/config/system/feature_toggle');
    $this->assertSession->linkByHrefNotExists('admin/config/system/feature_toggle/' . $name . '/delete');
    $this->assertSession->pageTextContains('Not allowed');
    $this->assertSession->pageTextContains($label);
    $this->assertSession->fieldExists($name);

    // Toggle the feature.
    $form_data = [
      $name => 1,
    ];
    $this->drupalPostForm(NULL, $form_data, t('Save'));

    // Check that checkbox is checked.
    $this->assertSession->checkboxChecked($name);

    // Toggle the feature.
    $form_data = [
      $name => 0,
    ];
    $this->drupalPostForm(NULL, $form_data, t('Save'));

    // Check that checkbox is  not checked.
    $this->assertSession->checkboxNotChecked($name);

    // User should not have access to delete features.
    $this->drupalGet('admin/config/system/feature_toggle/' . $this->randomMachineName() . '/delete');
    $this->assertSession->statusCodeEquals(403);
  }

  /**
   * Tests the custom access checker to delete page.
   */
  public function testDeleteAccess() {
    $this->drupalGet('admin/config/system/feature_toggle/' . $this->randomMachineName() . '/delete');
    $this->assertSession->statusCodeEquals(403);
  }

}
