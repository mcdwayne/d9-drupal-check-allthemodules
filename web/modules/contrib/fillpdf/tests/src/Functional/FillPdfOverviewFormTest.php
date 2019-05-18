<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\Core\Url;

/**
 * @coversDefaultClass \Drupal\fillpdf\Form\FillPdfOverviewForm
 * @group fillpdf
 */
class FillPdfOverviewFormTest extends FillPdfUploadTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;

  /**
   * Tests the overview form is accessible.
   */
  public function testOverviewForm() {
    $this->drupalGet(Url::fromRoute('fillpdf.forms_admin'));
  }

  /**
   * Tests the overview form's PDF file upload functionality.
   */
  public function testOverviewFormUpload() {
    // Without any file being supplied, nothing should happen at all,
    // particularly no FillPdfForm should be created.
    $this->uploadTestPdf(NULL);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('New FillPDF form has been created.');

    // Check if the 'accept' attribute is correctly set.
    $this->assertSession()->elementAttributeContains('css', 'input#edit-upload-pdf-upload', 'accept', 'application/pdf');

    // Run all upload tests.
    $this->assertNotUploadTextFile(self::OP_UPLOAD);
    $this->assertNotUploadTextFile(self::OP_CREATE);
    $this->assertUploadPdfFile(self::OP_UPLOAD, FALSE);
    $this->assertUploadPdfFile(self::OP_CREATE, FALSE);
  }

  /**
   * Tests the overview form's operation links.
   */
  public function testOverviewFormLinks() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');

    // Set the administrative title and check if it has been successfully set.
    $admin_title = 'Example form';
    $this->drupalPostForm(NULL, ['admin_title[0][value]' => $admin_title], self::OP_SAVE);
    $this->assertSession()->pageTextContains("FillPDF Form $admin_title has been updated.");
    $this->assertSession()->fieldValueEquals('edit-admin-title-0-value', $admin_title);

    // Go back to the overview page.
    $this->drupalGet('admin/structure/fillpdf');

    // Check if the administrative title appears in the view.
    $this->assertSession()->pageTextContains($admin_title);

    // Check hook_entity_operation_alter(). Only the altered link should exist.
    $this->assertSession()->linkExistsExact('Import configuration test');
    $this->assertSession()->linkNotExistsExact('Import configuration');

    // Check hook_entity_operation(). Both links should exist.
    $this->assertSession()->linkExistsExact('Export configuration test');
    $this->assertSession()->linkExistsExact('Export configuration');
  }

  /**
   * Tests an entity reference to a FillPdfForm.
   *
   * @todo: This doesn't belong here.
   */
  public function testEntityReference() {
    // Create new FillPdfForm.
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $fid = $this->getLatestFillPdfForm();

    // Set the administrative title.
    $admin_title = 'Example form';
    $this->drupalPostForm("admin/structure/fillpdf/{$fid}", ['admin_title[0][value]' => $admin_title], self::OP_SAVE);
    $this->assertSession()->statusCodeEquals(200);

    // Create host content type.
    $bundle = $this->createContentType();
    $bundle_id = $bundle->id();

    // Create an entity reference to our FillPdfForm.
    $this->createEntityReferenceField('node', $bundle_id, 'field_fillpdf_form', 'FillPDF form', 'fillpdf_form');
    $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load("node.{$bundle_id}.default")
      ->setComponent('field_fillpdf_form', [
        'type' => 'options_select',
      ])->save();
    $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load("node.{$bundle_id}.default")
      ->setComponent('field_fillpdf_form', [
        'type' => 'entity_reference_label',
        'settings' => ['link' => TRUE],
      ])->save();

    // Grant additional permission to the logged in user.
    $existing_user_roles = $this->loggedInUser->getRoles(TRUE);
    $role_to_modify = Role::load(end($existing_user_roles));
    $this->grantPermissions($role_to_modify, ["create $bundle_id content"]);

    // On a new node, check if the select contains an option with the
    // administrative title we have set.
    $this->drupalGet("/node/add/{$bundle_id}");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->optionExists('edit-field-fillpdf-form', $admin_title);

    // Select our FillPdfForm reference, save and see the label is rendered as
    // canonical link.
    $edit = [
      'title[0][value]' => 'Test node',
      'field_fillpdf_form' => $fid,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $fillpdf_form = FillPdfForm::load($fid);
    $this->assertSession()->linkByHrefExists($fillpdf_form->toUrl()->toString());

  }

}
