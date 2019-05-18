<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\fillpdf\Traits\TestFillPdfTrait;

/**
 * @coversDefaultClass \Drupal\fillpdf\Form\FillPdfSettingsForm
 * @group fillpdf
 */
class FillPdfSettingsFormTest extends BrowserTestBase {
  public static $modules = ['fillpdf_test', 'file_test'];

  use TestFillPdfTrait;

  /**
   * Set to FALSE to suppress checking all configuration saved.
   *
   * @var bool
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->initializeUser();
  }

  /**
   * Tests the scheme settings with the site default.
   */
  public function testSettingsFormDefaults() {
    // FillPDF is not yet configured.
    // Verify the settings form is however initialized with the site default
    // scheme, which at this point should be 'public', and with the
    // 'fillpdf_service' backend.
    $this->drupalGet(Url::fromRoute('fillpdf.settings'));
    $this->assertSession()->pageTextContains('Public files (site default)');
    $this->assertSession()->checkboxChecked('edit-template-scheme-public');
    $this->assertSession()->checkboxChecked('edit-backend-fillpdf-service');

    // Now set the site default to 'private'.
    $config = $this->container->get('config.factory')
      ->getEditable('system.file')
      ->set('default_scheme', 'private');
    $config->save();

    // The form should now be initialized with the new site default scheme,
    // while the backend should remain unchanged.
    $this->drupalGet(Url::fromRoute('fillpdf.settings'));
    $this->assertSession()->pageTextContains('Private files (site default)');
    $this->assertSession()->checkboxChecked('edit-template-scheme-private');
    $this->assertSession()->checkboxChecked('edit-backend-fillpdf-service');
  }

  /**
   * Tests the scheme settings with the 'dummy_remote' stream wrapper.
   */
  public function testTemplateSchemeDummyRemote() {
    // FillPDF is not yet configured.
    // Verify the 'dummy_remote' stream wrapper is present on the form.
    $this->drupalGet(Url::fromRoute('fillpdf.settings'));
    $this->assertSession()->elementExists('css', '#edit-template-scheme-dummy-remote');

    // Programmatically configure 'dummy_remote' as new default scheme.
    $this->configureFillPdf(['template_scheme' => 'dummy_remote']);

    // Now uninstall the file_test module with the dummy stream wrappers.
    $this->assertTrue(\Drupal::service('module_installer')->uninstall(['file_test']), "Module 'file_test' has been uninstalled.");
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('file_test'), "Module 'file_test' is no longer present.");

    // Reload the page and verify that 'dummy_remote' is gone.
    $this->drupalGet(Url::fromRoute('fillpdf.settings'));
    $this->assertSession()->elementNotExists('css', '#edit-template-scheme-dummy-remote');
    $this->assertSession()->pageTextContains('Your previously used file storage dummy_remote:// is no longer available');
  }

  /**
   * Tests the scheme settings with the 'private' stream wrapper.
   */
  public function testTemplateSchemePrivate() {
    // FillPDF is not yet configured.
    // Configure FillPDF with the 'test' backend and the site default scheme,
    // which at this point is 'public'.
    $this->configureFillPdf();

    // Now on the settings form, switch to the 'private' scheme.
    $this->drupalPostForm(Url::fromRoute('fillpdf.settings'), ['template_scheme' => 'private'], 'Save configuration');

    // Verify the new values have been submitted *and* successfully saved.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->fieldValueEquals('template_scheme', 'private');
    $this->assertEqual($this->config('fillpdf.settings')->get('template_scheme'), 'private');

    // Now remove the private path from settings.php and rebuild the container.
    $this->writeSettings([
      'settings' => [
        'file_private_path' => (object) [
          'value' => '',
          'required' => TRUE,
        ],
      ],
    ]);
    $this->rebuildContainer();

    // Reload the page to verify the 'private' scheme is gone.
    $this->drupalGet(Url::fromRoute('fillpdf.settings'));
    $this->assertSession()->elementNotExists('css', '#edit-template-scheme-private');
    $this->assertSession()->pageTextContains('Your previously used file storage private:// is no longer available');

    // Verify that the site default scheme, which at this point is 'public', is
    // preselected but not yet saved in config.
    $this->assertSession()->fieldValueEquals('template_scheme', file_default_scheme());
    $this->assertEqual($this->config('fillpdf.settings')->get('template_scheme'), 'private');
  }

  /**
   * Tests the scheme settings with the 'public' stream wrapper.
   */
  public function testTemplateSchemePublic() {
    // FillPDF is not yet configured.
    // Configure FillPDF with the 'test' backend and the site default scheme,
    // which at this point is 'public'.
    $this->configureFillPdf();

    // On the settings page, verify the 'public' scheme is set both in the form
    // and in config.
    $this->drupalGet(Url::fromRoute('fillpdf.settings'));
    $this->assertSession()->fieldValueEquals('template_scheme', 'public');
    $this->assertEqual($this->config('fillpdf.settings')->get('template_scheme'), 'public');

    // Verify the subdirectory doesn't exist yet.
    $directory = 'public://fillpdf';
    $this->assertFalse(is_dir($directory), 'Directory does not exist prior to testing.');

    // Now on the settings form, save the unchanged configuration to create the
    // subdirectory. Verify it does exist now and is writeable.
    $this->drupalPostForm(NULL, [], 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertTrue(is_dir($directory), 'Directory exists now.');
    $this->assertTrue(file_prepare_directory($directory), 'Directory is writeable.');

    // Now delete the directory and replace it with a file with the same name,
    // so the directory can't be recreated. Try saving again and verify an error
    // is set.
    drupal_rmdir($directory);
    file_unmanaged_copy('public://.htaccess', $directory);
    $this->drupalPostForm(NULL, [], 'Save configuration');
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');
    $this->assertSession()->pageTextContains('Could not automatically create the subdirectory');
  }

  /**
   * Tests the backend settings with the 'fillpdf_service' backend.
   */
  public function testBackendFillPdfService() {
    // FillPDF is not yet configured. The settings form is however initialized
    // with the 'fillpdf_service' backend. Save that configuration.
    $this->drupalPostForm(Url::fromRoute('fillpdf.settings'), NULL, 'Save configuration');

    // There's currently no validation, so the 'backend' setting should be
    // both submitted and stored.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->fieldValueEquals('backend', 'fillpdf_service');
    $this->assertEqual($this->config('fillpdf.settings')->get('backend'), 'fillpdf_service');

    // Now add an API key and save again.
    $this->drupalPostForm(NULL, ['fillpdf_service_api_key' => 'Invalid, just playing around.'], 'Save configuration');

    // There's currently no validation, so the obviously invalid
    // 'fillpdf_service_api_key' should be both submitted and stored.
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->fieldValueEquals('fillpdf_service_api_key', 'Invalid, just playing around.');
    $this->assertEqual($this->config('fillpdf.settings')->get('fillpdf_service_api_key'), 'Invalid, just playing around.');
  }

  /**
   * Tests the backend settings with the 'pdftk' backend.
   */
  public function testBackendPdftk() {
    // FillPDF is not yet configured.
    // Try configuring FillPDF with the 'pdftk' backend, yet an invalid path.
    $edit = [
      'template_scheme' => 'private',
      'backend' => 'pdftk',
      'pdftk_path' => 'xyz',
    ];
    $this->drupalPostForm(Url::fromRoute('fillpdf.settings'), $edit, 'Save configuration');

    // The validation handler kicked in.
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');
    $this->assertSession()->pageTextContains('The path you have entered for pdftk is invalid. Please enter a valid path.');

    // Verify the new values have been submitted, but *not* saved.
    foreach ($edit as $field => $value) {
      $this->assertSession()->fieldValueEquals($field, $value);
      $this->assertEqual($this->config('fillpdf.settings')->get($field), NULL);
    }
  }

  /**
   * Tests the backend settings with the 'test' backend.
   */
  public function testBackendTest() {
    // FillPDF is not yet configured.
    // Go to the settings page and verify the autodetected 'test' backend is
    // present only once and with the form-altered label.
    $this->drupalGet(Url::fromRoute('fillpdf.settings'));
    $this->assertSession()->pageTextContainsOnce('plugin for testing');
    $this->assertSession()->pageTextContains('Form-altered pass-through plugin for testing');

    // Try configuring FillPDF with the 'test' backend, yet with invalid values
    // for the form-altered 'example_setting' and the unrelated
    // 'fillpdf_service_api_key'.
    $edit = [
      'template_scheme' => 'private',
      'backend' => 'test',
      'example_setting' => 'x',
      'fillpdf_service_api_key' => 'Invalid, just playing around.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    // The 'test' radio option is present and working.
    $this->assertSession()->pageTextNotContains('An illegal choice has been detected.');
    // However, our custom validation handler kicked in.
    $this->assertSession()->pageTextNotContains('The configuration options have been saved.');
    $this->assertSession()->pageTextContains('Not a valid value.');
    // Therefore, the new values should be submitted, but *not* saved.
    foreach ($edit as $field => $value) {
      $this->assertSession()->fieldValueEquals($field, $value);
      $this->assertEqual($this->config('fillpdf.settings')->get($field), NULL);
    }

    // Try again with a valid value.
    $edit['example_setting'] = 'xyz';
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    // This time, our custom validation handler passes.
    $this->assertSession()->pageTextNotContains('Not a valid value.');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    // So the new values should be submitted *and* saved this time, except for
    // the unrelated 'fillpdf_service_api_key' which should be dismissed.
    $expected = ['fillpdf_service_api_key' => NULL] + $edit;
    foreach ($expected as $field => $value) {
      $this->assertSession()->fieldValueEquals($field, $value);
      $this->assertEqual($this->config('fillpdf.settings')->get($field), $value);
    }
  }

}
