<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\fillpdf\Entity\FillPdfFormField;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\fillpdf\Traits\TestFillPdfTrait;
use Drupal\Core\Url;

/**
 * @coversDefaultClass \Drupal\fillpdf\Form\FillPdfFormDuplicateForm
 * @group fillpdf
 */
class FillPdfFormDuplicateFormTest extends BrowserTestBase {

  use TestFillPdfTrait;

  static public $modules = ['fillpdf_test'];
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configureFillPdf();
    $this->initializeUser();
  }

  /**
   * Tests the duplicate function.
   */
  public function testDuplicateForm() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();
    $template_fid = FillPdfForm::load($form_id)->fid->value;

    // Verify the FillPdfForm's fields are stored.
    $field_ids = \Drupal::entityQuery('fillpdf_form_field')->condition('fillpdf_form', $form_id)->execute();
    $this->assertCount(4, $field_ids, "4 FillPdfFormFields have been created.");

    // We're now on the edit form. Add an admin title.
    $this->assertSession()->pageTextContains('New FillPDF form has been created.');
    $admin_title = 'Test';
    $this->drupalPostForm(NULL, ['admin_title[0][value]' => $admin_title], 'Save');
    $this->assertSession()->pageTextContains("FillPDF Form $admin_title has been updated.");

    // Go to the overview form, click duplicate but cancel to come back.
    $overview_url = Url::fromRoute('fillpdf.forms_admin');
    $this->drupalGet($overview_url);
    $this->clickLink('Duplicate');
    $this->assertSession()->pageTextContains("Create duplicate of $admin_title?");
    $this->assertSession()->fieldValueEquals('new_admin_title', "Duplicate of $admin_title");
    $this->clickLink('Cancel');
    $this->assertSession()->addressEquals($overview_url);

    // Back to the overview form, try again, and this time create a duplicate.
    $this->clickLink('Duplicate');
    $this->drupalPostForm(NULL, ['new_admin_title' => 'Another test'], 'Save');
    $this->assertSession()->pageTextContains('FillPDF form has been duplicated.');
    $this->assertSession()->addressEquals(Url::fromRoute('fillpdf.forms_admin'));

    // Now verify the FillPdfForm and its fields have actually been duplicated,
    // but are using the same template file.
    $new_form_id = $this->getLatestFillPdfForm();
    $this->assertNotEquals($new_form_id, $form_id);
    $field_ids = \Drupal::entityQuery('fillpdf_form_field')->condition('fillpdf_form', $new_form_id)->execute();
    foreach ($field_ids as $id) {
      $this->assertNotNull(FillPdfFormField::load($id), "The FillPdfFormField #{$id} has ben duplicated.");
    }
    $this->assertEquals($template_fid, FillPdfForm::load($form_id)->fid->value);
  }

}
