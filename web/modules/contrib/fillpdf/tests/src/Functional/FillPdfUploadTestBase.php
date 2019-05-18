<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\file\Entity\File;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\file\Functional\FileFieldTestBase;
use Drupal\Tests\fillpdf\Traits\TestFillPdfTrait;

/**
 * Allows testing everything around uploading PDF template files.
 *
 * @group fillpdf
 * @todo Switch to a trait or FileManagedTestBase once it contains a more robust
 *   set of tools. See: https://www.drupal.org/project/drupal/issues/3043024.
 */
abstract class FillPdfUploadTestBase extends FileFieldTestBase {

  use TestFillPdfTrait;

  static public $modules = ['fillpdf_test'];

  protected $profile = 'minimal';

  /**
   * Upload a file in the managed file widget.
   *
   * @var string
   */
  const OP_UPLOAD = 'Upload';

  /**
   * Remove a file from the managed file widget.
   *
   * @var string
   */
  const OP_REMOVE = 'Remove';

  /**
   * Create a new FillPdfForm. Submit button on FillPdfOverviewForm.
   *
   * @var string
   */
  const OP_CREATE = 'Create';

  /**
   * Save and update the FillPdfForm. Submit button on FillPdfFormForm.
   *
   * @var string
   */
  const OP_SAVE = 'Save';

  /**
   * Test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $testNodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configureFillPdf();
    $this->initializeUser();

    $this->testNodes[1] = $this->createNode([
      'title' => 'Hello',
      'type' => 'article',
    ]);
    $this->testNodes[2] = $this->createNode([
      'title' => 'Goodbye',
      'type' => 'article',
    ]);
  }

  /**
   * Asserts that a text file may not be uploaded.
   *
   * @param string $op
   *   (optional) Operation to perform. May be either of:
   *   - FillPdfUploadTestBase::OP_UPLOAD (default),
   *   - FillPdfUploadTestBase::OP_CREATE, or
   *   - FillPdfUploadTestBase::OP_SAVE.
   */
  protected function assertNotUploadTextFile($op = self::OP_UPLOAD) {
    $previous_file_id = $this->getLastFileId();

    // Try uploading a text file in the managed file widget.
    $edit = ['files[upload_pdf]' => $this->getTestFile('text')->getFileUri()];
    $this->drupalPostForm(NULL, $edit, $op);

    // Whether submitted or just uploaded, the validation should set an error
    // and the file shouldn't end up being uploaded.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Only files with the following extensions are allowed: pdf.');
    $this->assertEquals($previous_file_id, $this->getLastFileId());

    // Make sure FillPdf Forms were not affected.
    $this->assertSession()->pageTextNotContains('New FillPDF form has been created.');
    $this->assertSession()->pageTextNotContains('Your previous field mappings have been transferred to the new PDF template you uploaded.');

  }

  /**
   * Asserts that a PDF file may be properly uploaded as a template.
   *
   * @param string $op
   *   (optional) Operation to perform. May be either of:
   *   - FillPdfUploadTestBase::OP_UPLOAD (default),
   *   - FillPdfUploadTestBase::OP_CREATE, or
   *   - FillPdfUploadTestBase::OP_SAVE.
   * @param bool $filename_preexists
   *   (optional) Whether the testfile has previously been uploaded, so a file
   *   with the same filename preexists. Defaults to FALSE.
   */
  protected function assertUploadPdfFile($op = self::OP_UPLOAD, $filename_preexists = FALSE) {
    $previous_file_id = $this->getLastFileId();

    // Upload PDF test file.
    $edit = ['files[upload_pdf]' => $this->getTestPdfPath('fillpdf_test_v3.pdf')];
    $this->drupalPostForm(NULL, $edit, $op);

    // Whether submitted or just uploaded, at least temporarily the file should
    // exist now both as an object and physically on the disk.
    $new_file = File::load($this->getLastFileId());
    $new_filename = $new_file->getFilename();
    $this->assertFileExists($new_file);
    $this->assertLessThan((int) $new_file->id(), $previous_file_id);

    // If the same file was previously uploaded, it should have a "_0" appendix.
    $this->assertEquals($new_filename, $filename_preexists ? 'fillpdf_test_v3_0.pdf' : 'fillpdf_test_v3.pdf');

    switch ($op) {
      case self::OP_UPLOAD:
        // We only uploaded, so make sure FillPdf Forms were not affected.
        $this->assertSession()->pageTextNotContains('New FillPDF form has been created.');
        $this->assertSession()->pageTextNotContains('Your previous field mappings have been transferred to the new PDF template you uploaded.');

        // Make sure the file is temporary only.
        // @todo Simplify once there is an assertFileIsTemporary().
        //   See: https://www.drupal.org/project/drupal/issues/3043129.
        $this->assertTrue($new_file->isTemporary(), new FormattableMarkup('File %file is temporary.', ['%file' => $new_file->getFileUri()]));

        // Now remove the PDF file again. The temporary file should now be
        // removed both from the disk and the database.
        $this->drupalPostForm(NULL, NULL, self::OP_REMOVE);
        $this->assertFileNotExists($new_file);
        // @todo Simplify once Core bug gets fixed.
        //   See: https://www.drupal.org/project/drupal/issues/3043127.
        $this->assertFileEntryNotExists($new_file, NULL);
        break;

      case self::OP_CREATE:
        // A new FillPdfForm should be created.
        $this->assertSession()->pageTextContains('New FillPDF form has been created.');
        $this->assertSession()->pageTextNotContains('Your previous field mappings have been transferred to the new PDF template you uploaded.');

        // There should be four fields in the correct order.
        // @todo: Add some CSS markup to the view so we can test the order.
        $this->assertSession()->pageTextContainsOnce('ImageField');
        $this->assertSession()->pageTextContainsOnce('TestButton');
        $this->assertSession()->pageTextContainsOnce('TextField1');
        $this->assertSession()->pageTextContainsOnce('TextField2');
        $this->assertSession()->elementsCount('css', 'tbody > tr', 4);

        // Make sure the file is permanent and correctly placed.
        $this->assertFileIsPermanent($new_file);
        $expected_file_uri = FillPdf::buildFileUri($this->config('fillpdf.settings')->get('template_scheme'), "fillpdf/{$new_filename}");
        $this->assertEquals($new_file->getFileUri(), $expected_file_uri);
        break;

      case self::OP_SAVE:
        // The current FillPdfForm should be updated with the new file.
        $this->assertSession()->pageTextNotContains('New FillPDF form has been created.');
        $this->assertSession()->pageTextContains('Your previous field mappings have been transferred to the new PDF template you uploaded.');

        // Make sure the file is permanent and correctly placed.
        $this->assertFileIsPermanent($new_file);
        $expected_file_uri = FillPdf::buildFileUri($this->config('fillpdf.settings')->get('template_scheme'), "fillpdf/{$new_filename}");
        $this->assertEquals($new_file->getFileUri(), $expected_file_uri);
        break;
    }
  }

}
