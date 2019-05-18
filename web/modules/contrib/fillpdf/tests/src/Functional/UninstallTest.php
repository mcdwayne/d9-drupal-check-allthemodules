<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\fillpdf\Traits\TestFillPdfTrait;
use Drupal\Core\Url;

/**
 * Tests uninstalling the module.
 *
 * @group fillpdf
 */
class UninstallTest extends BrowserTestBase {

  use TestFillPdfTrait;

  static public $modules = ['fillpdf_test'];

  protected $profile = 'minimal';

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configureFillPdf();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
      'administer pdfs',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests uninstalling the module.
   */
  public function testUninstall() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');

    // Go to the uninstall page and check the requirements.
    $this->drupalGet(Url::fromRoute('system.modules_uninstall'));
    $this->assertSession()->pageTextContains('The following reasons prevent FillPDF from being uninstalled');
    $this->assertSession()->pageTextContains('There is content for the entity type: FillPDF form. Remove fillpdf form entities');
    $this->assertSession()->pageTextNotContains('There is content for the entity type: FillPDF form field. Remove fillpdf form field entities');

    // Check the fillpdf form fields are discovered.
    $this->drupalGet(Url::fromRoute('system.prepare_modules_entity_uninstall', ['entity_type_id' => 'fillpdf_form_field']));
    $this->assertSession()->pageTextContains('This will delete 4 fillpdf form field entities');

    // Now delete all fillpdf forms.
    $this->drupalGet(Url::fromRoute('system.prepare_modules_entity_uninstall', ['entity_type_id' => 'fillpdf_form']));
    $this->assertSession()->pageTextContains('Are you sure you want to delete all fillpdf form entities?');
    $this->drupalPostForm(NULL, [], 'Delete all fillpdf form entities');
    $this->assertSession()->pageTextContains('All fillpdf form entities have been deleted');

    // Make sure all fillpdf form fields have been deleted as well.
    $this->drupalGet(Url::fromRoute('system.prepare_modules_entity_uninstall', ['entity_type_id' => 'fillpdf_form_field']));
    $this->assertSession()->pageTextContains('There are 0 fillpdf form field entities to delete');

    // Now go back to the uninstall page and uninstall fillpdf_test and fillpdf.
    foreach (['fillpdf_test', 'fillpdf'] as $module) {
      $this->drupalPostForm(Url::fromRoute('system.modules_uninstall'), ["uninstall[$module]" => TRUE], 'Uninstall');
      $this->assertSession()->pageTextContains('The following modules will be completely uninstalled from your site, and all data from these modules will be lost');
      $this->drupalPostForm(NULL, [], 'Uninstall');
      $this->assertSession()->pageTextContains('The selected modules have been uninstalled');
    }
  }

}
