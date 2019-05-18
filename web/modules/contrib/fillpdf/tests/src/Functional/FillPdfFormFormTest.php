<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\user\Entity\Role;

/**
 * @coversDefaultClass \Drupal\fillpdf\Form\FillPdfFormForm
 * @group fillpdf
 */
class FillPdfFormFormTest extends FillPdfUploadTestBase {

  /**
   * Tests the FillPdfForm entity's edit form.
   */
  public function testDefaultEntityId() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');

    // Default entity type is not yet given, so there should be no ID element.
    $this->assertSession()->pageTextNotContains('Default entity ID');

    $testcases = [];
    // Test case 0: no entity.
    $testcases[1]['type'] = 'user';
    $testcases[1]['id'] = $this->adminUser->id();
    $testcases[1]['label'] = $this->adminUser->label();

    $testcases[2]['type'] = 'node';
    $testcases[2]['id'] = $this->testNodes[1]->id();
    $testcases[2]['label'] = $this->testNodes[1]->label();

    foreach ($testcases as $case) {
      $type = $case['type'];
      $id = $case['id'];
      $label = $case['label'];

      // Set a default entity type and check if it's properly saved.
      $this->drupalPostForm(NULL, ['default_entity_type' => $type], self::OP_SAVE);
      $this->assertSession()->pageTextContains("FillPDF Form has been updated.");
      $this->assertSession()->fieldValueEquals('edit-default-entity-type', $type);

      // Check the default entity ID autocomplete is present now and showing the
      // correct description.
      $this->assertSession()->fieldValueEquals('edit-default-entity-id', '');
      $this->assertSession()->pageTextContains("Enter the title of a $type to test populating the PDF template.");

      // Now set a default entity ID and check if the entity type is unchanged.
      $this->drupalPostForm(NULL, ['default_entity_id' => $label], self::OP_SAVE);
      $this->assertSession()->pageTextContains("FillPDF Form has been updated.");
      $this->assertSession()->fieldValueEquals('edit-default-entity-type', $type);

      // Check the default entity ID autocomplete is still present and showing
      // the updated description with the link.
      $this->assertSession()->fieldValueEquals('edit-default-entity-id', "$label ($id)");
      $this->assertSession()->linkExistsExact("Download this PDF template populated with data from the $type $label ($id).");
    }
  }

  /**
   * Tests the FillPdfForm entity's edit form.
   */
  public function testFormFormUpload() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');

    $latest_fid = $this->getLatestFillPdfForm();
    $latest_fillpdf_form = FillPdfForm::load($latest_fid);
    $max_fid_after = $latest_fillpdf_form->fid->value;
    $this->drupalGet('admin/structure/fillpdf/' . $max_fid_after);
    $this->assertSession()->statusCodeEquals(200);

    // Check if the 'accept' attribute is correctly set.
    $this->assertSession()->elementAttributeContains('css', 'input#edit-upload-pdf-upload', 'accept', 'application/pdf');

    // Run all upload tests.
    $this->assertNotUploadTextFile(self::OP_UPLOAD);
    $this->assertNotUploadTextFile(self::OP_SAVE);
    $this->assertUploadPdfFile(self::OP_UPLOAD, TRUE);
    $this->assertUploadPdfFile(self::OP_SAVE, TRUE);
  }

  /**
   * Tests the FillPdfForm entity's edit form.
   */
  public function testStorageSettings() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();
    $previous_file_id = $this->getLastFileId();

    $edit_form_url = Url::fromRoute('entity.fillpdf_form.edit_form', ['fillpdf_form' => $form_id]);
    $generate_url = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $form_id,
        'entity_id' => "node:{$this->testNodes[1]->id()}",
      ],
    ]);

    // Check the initial storage settings.
    $this->assertSession()->fieldValueEquals('scheme', '_none');
    foreach (['- None -', 'private://', 'public://'] as $option) {
      $this->assertSession()->optionExists('scheme', $option);
    }
    $this->assertSession()->fieldValueEquals('destination_path[0][value]', '');
    $this->drupalGet($edit_form_url);

    // Now hit the generation route and make sure the PDF file is *not* stored.
    $this->drupalGet($generate_url);
    $this->assertEquals($previous_file_id, $this->getLastFileId(), 'Generated file is not stored.');

    // Set the 'public' scheme and see if the 'destination_path' field appears.
    $this->drupalPostForm($edit_form_url, ['scheme' => 'public'], self::OP_SAVE);
    $this->assertSession()->fieldValueEquals('scheme', 'public');
    $this->assertSession()->pageTextContains('Destination path');

    // Hit the generation route again and make sure this time the PDF file is
    // stored in the public storage.
    $this->drupalGet($generate_url);
    $this->assertEquals(++$previous_file_id, $this->getLastFileId(), 'Generated file was stored.');
    $this->assertStringStartsWith('public://', File::load($this->getLastFileId())->getFileUri());

    // Now disallow the public scheme and reload.
    $this->configureFillPdf(['allowed_schemes' => ['private']]);

    // Reload and check if the public option has disappeared now.
    $this->drupalGet($edit_form_url);
    $this->assertSession()->fieldValueEquals('scheme', '_none');
    foreach (['- None -', 'private://'] as $option) {
      $this->assertSession()->optionExists('scheme', $option);
    }
    $this->assertSession()->optionNotExists('scheme', 'public://');

    // Hit the generation route once more and make sure the scheme has been
    // unset, so the PDF file is *not* stored.
    $this->drupalGet($generate_url);
    $this->assertEquals($previous_file_id, $this->getLastFileId(), 'Generated file is not stored.');

    // Set the 'private' scheme.
    $this->drupalPostForm($edit_form_url, ['scheme' => 'private'], self::OP_SAVE);
    $this->assertSession()->fieldValueEquals('scheme', 'private');

    // Hit the generation route again and make sure this time the PDF file is
    // stored in the private storage.
    $this->drupalGet($generate_url);
    $this->assertEquals(++$previous_file_id, $this->getLastFileId(), 'Generated file was stored.');
    $this->assertStringStartsWith('private://', File::load($this->getLastFileId())->getFileUri());

    // Now disallow the private scheme as well and reload.
    $this->configureFillPdf(['allowed_schemes' => []]);
    $this->drupalGet($edit_form_url);

    // Check if the whole storage settings section has disappeared now.
    $this->assertSession()->pageTextNotContains('Storage and download');

    // Hit the generation route one last time and make sure the PDF file is
    // again *not* stored.
    $this->drupalGet($generate_url);
    $this->assertEquals($previous_file_id, $this->getLastFileId(), 'Generated file is not stored.');
  }

  /**
   * Tests proper registration of managed_files.
   */
  public function testFillPdfFileUsage() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');

    // Set the administrative title and check if it has been successfully set.
    $admin_title = 'Example form';
    $this->drupalPostForm(NULL, ['admin_title[0][value]' => $admin_title], self::OP_SAVE);
    $this->assertSession()->pageTextContains("FillPDF Form $admin_title has been updated.");
    $this->assertSession()->fieldValueEquals('edit-admin-title-0-value', $admin_title);

    // Grant additional permission to the logged in user.
    $existing_user_roles = $this->loggedInUser->getRoles(TRUE);
    $role_to_modify = Role::load(end($existing_user_roles));
    $this->grantPermissions($role_to_modify, ['access files overview']);

    // Check if the uploaded test PDF file is properly registered as a permanent
    // managed_file.
    $fillpdf_form = FillPdfForm::load($this->getLatestFillPdfForm());
    $file_id = $fillpdf_form->get('file')->first()->getValue()['target_id'];
    $this->drupalPostForm('admin/content/files', ['edit-filename' => 'fillpdf_test_v3.pdf'], 'Filter');
    $this->assertSession()->elementsCount('css', 'table td.views-field.views-field-filename', 1);
    $this->assertSession()->pageTextContains('Permanent');
    // @todo Past 8.6.x, use File::load($file_id)->createFileUrl() directly.
    // See https://www.drupal.org/project/fillpdf/issues/3023341.
    $file_uri = File::load($file_id)->getFileUri();
    $this->assertSession()->linkByHrefExists(file_create_url($file_uri));

    // Now go check the File usage screen and see if the FillPdfForm is listed
    // with its canonical link.
    $this->drupalGet("admin/content/files/usage/$file_id");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($fillpdf_form->toUrl()->toString());
  }

}
