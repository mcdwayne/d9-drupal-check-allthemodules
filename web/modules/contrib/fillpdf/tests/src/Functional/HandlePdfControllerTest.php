<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * @coversDefaultClass \Drupal\fillpdf\Controller\HandlePdfController
 *
 * Also covers \Drupal\fillpdf\Plugin\FillPdfActionPlugin
 * and \Drupal\fillpdf\OutputHandler.
 *
 * @group fillpdf
 *
 * @todo Convert into a unit test.
 */
class HandlePdfControllerTest extends FillPdfUploadTestBase {

  /**
   * Tests DownloadAction.
   */
  public function testDownloadAction() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();

    $fid_before = $this->getLastFileId();
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $form_id,
        'sample' => 1,
      ],
    ]);
    $this->drupalGet($fillpdf_route);
    $fid_after = $this->getLastFileId();

    // Make sure the PDF file has not been saved.
    $this->assertEquals($fid_before, $fid_after);

    // Make sure we are seeing the downloaded PDF
    $this->assertSession()->statusCodeEquals(200);
    $maybe_pdf = $this->getSession()->getPage()->getContent();
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    static::assertEquals('application/pdf', $finfo->buffer($maybe_pdf), "The file has the correct MIME type.");

  }

  /**
   * Tests SaveAction.
   */
  public function testSaveAction() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();
    $edit = [
      'scheme' => 'public',
    ];
    $this->drupalPostForm("admin/structure/fillpdf/{$form_id}", $edit, 'Save');

    $fid_before = $this->getLastFileId();
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $form_id,
        'sample' => 1,
      ],
    ]);
    $this->drupalGet($fillpdf_route);
    $fid_after = $this->getLastFileId();

    // Make sure the PDF file has been saved.
    $this->assertEquals($fid_before + 1, $fid_after);

    // Make sure we are /not/ redirected to the PDF.
    $this->assertSession()->statusCodeEquals(200);
    $maybe_pdf = $this->getSession()->getPage()->getContent();
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    static::assertNotEquals('application/pdf', $finfo->buffer($maybe_pdf), "The file has the correct MIME type.");
  }

  /**
   * Tests RedirectAction.
   */
  public function testRedirectAction() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();
    $edit = [
      'scheme' => 'public',
      'destination_redirect[value]' => TRUE,
    ];
    $this->drupalPostForm("admin/structure/fillpdf/{$form_id}", $edit, 'Save');

    $fid_before = $this->getLastFileId();
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $form_id,
        'sample' => 1,
      ],
    ]);
    $this->drupalGet($fillpdf_route);
    $fid_after = $this->getLastFileId();

    // Make sure the PDF file has been saved.
    $this->assertEquals($fid_before + 1, $fid_after);

    // Make sure we have been redirected to the PDF.
    $this->assertSession()->statusCodeEquals(200);
    $maybe_pdf = $this->getSession()->getPage()->getContent();
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    static::assertEquals('application/pdf', $finfo->buffer($maybe_pdf), "The file has the correct MIME type.");
  }

  /**
   * Tests filename and destination of a populated PDF file.
   */
  public function testTokenFilenameDestination() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();
    $edit = [
      'title[0][value]' => '[current-date:html_year]-[user:account-name]-[node:title].pdf',
      'scheme' => 'public',
      'destination_path[0][value]' => '[current-date:html_year]-[user:account-name]-[node:title]',
    ];
    $this->drupalPostForm("admin/structure/fillpdf/{$form_id}", $edit, 'Save');

    $year = date('Y');
    $node1_id = $this->testNodes[1]->id();
    $node1_title = $this->testNodes[1]->getTitle();
    $node2_id = $this->testNodes[2]->id();
    $node2_title = $this->testNodes[2]->getTitle();
    $user_id = $this->adminUser->id();
    $user_name = $this->adminUser->getAccountName();

    $testcases = [];
    // Test case 0: no entity.
    $testcases[1]['entities'] = [];
    $testcases[1]['expected'] = "{$year}--";

    // Test case 1: existing node.
    $testcases[1]['entities'] = ["node:{$node1_id}"];
    $testcases[1]['expected'] = "{$year}--{$node1_title}";

    // Test case 2: two existing nodes.
    $testcases[2]['entities'] = ["node:{$node1_id}", "node:{$node2_id}"];
    $testcases[2]['expected'] = "{$year}--{$node2_title}";

    // Test case 3: twice the same node.
    $testcases[3]['entities'] = ["node:{$node1_id}", "node:{$node1_id}"];
    $testcases[3]['expected'] = "{$year}--{$node1_title}";

    // Test case 4: existing user.
    $testcases[4]['entities'] = ["user:{$user_id}"];
    $testcases[4]['expected'] = "{$year}-{$user_name}-";

    // Test case 5: existing node and existing user.
    $testcases[5]['entities'] = ["node:{$node1_id}", "user:{$user_id}"];
    $testcases[5]['expected'] = "{$year}-{$user_name}-{$node1_title}";

    // Test case 6: non-existing node.
    $testcases[6]['entities'] = ["node:123"];
    $testcases[6]['expected'] = "{$year}--";

    // Test case 7: existing node and non-existing user.
    $testcases[7]['entities'] = ["node:{$node1_id}", "user:456"];
    $testcases[7]['expected'] = "{$year}--{$node1_title}";

    foreach ($testcases as $id => $case) {
      // Hit the generation route.
      $entities = $case['entities'];
      $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
        'query' => [
          'fid' => $form_id,
          'entity_ids' => $entities,
        ],
      ]);
      $this->drupalGet($fillpdf_route);

      // Get last file and check if filename and path are correct.
      $file = File::load($this->getLastFileId());
      $filename = $file->getFilename();
      $uri = $file->getFileUri();

      $expected = $case['expected'];
      $this->assertEquals("{$expected}.pdf", $filename, "Test case $id: The file has the filename $filename.");
      $this->assertEquals("public://fillpdf/{$expected}/{$expected}.pdf", $uri, "Test case $id: The file has the expected URI.");

      // Check if file is permanent and has the right format.
      $this->assertFileIsPermanent($file);
      $this->drupalGet(file_create_url($uri));
      $maybe_pdf = $this->getSession()->getPage()->getContent();
      $finfo = new \finfo(FILEINFO_MIME_TYPE);
      static::assertEquals('application/pdf', $finfo->buffer($maybe_pdf), "Test case $id: The file has the correct MIME type.");

      // Delete the file, so we don't run into conflicts with the next testcase.
      $file->delete();
    }
  }

  /**
   * Tests handling of an no longer allowed storage scheme.
   */
  public function testStorageSchemeDisallowed() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();
    $previous_file_id = $this->getLastFileId();
    $edit = [
      'admin_title[0][value]' => 'Scheme test',
      'scheme' => 'public',
      'destination_path[0][value]' => 'test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $form_id,
      ],
    ]);

    // Hit the generation route. Make sure we are redirected to the front page.
    $this->drupalGet($fillpdf_route);
    $this->assertSession()->addressNotEquals('/fillpdf');
    $this->assertSession()->statusCodeEquals(200);
    // Get back to the front page and make sure the file was stored in the
    // private storage.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('File storage scheme public:// is unavailable');
    $this->assertEquals(++$previous_file_id, $this->getLastFileId(), 'Generated file was stored.');
    $this->assertStringStartsWith('public://', File::load($this->getLastFileId())->getFileUri());

    // Now disallow the public scheme.
    $this->configureFillPdf(['allowed_schemes' => ['private']]);

    // Hit the generation route again. This time we should be redirected to the
    // PDF file. Make sure no PHP error occured.
    $this->drupalGet($fillpdf_route);
    $this->assertSession()->addressEquals('/fillpdf');
    $this->assertSession()->statusCodeEquals(200);
    // Get back to the front page and check if an error was set, and we didn't
    // try to store the file.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains("File storage scheme public:// is unavailable, so a PDF file generated from FillPDF form Scheme test ($form_id) could not be stored.");
    $this->assertEquals($previous_file_id, $this->getLastFileId(), 'Generated file was not stored.');
  }

  /**
   * Tests handling of an unavailable storage scheme.
   */
  public function testStorageSchemeUnavailable() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $form_id = $this->getLatestFillPdfForm();
    $previous_file_id = $this->getLastFileId();
    $edit = [
      'admin_title[0][value]' => 'Scheme test',
      'scheme' => 'private',
      'destination_path[0][value]' => 'test',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => $form_id,
      ],
    ]);

    // Hit the generation route. Make sure we are redirected to the front page.
    $this->drupalGet($fillpdf_route);
    $this->assertSession()->addressNotEquals('/fillpdf');
    $this->assertSession()->statusCodeEquals(200);
    // Get back to the front page and make sure the file was stored in the
    // private storage.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('File storage scheme private:// is unavailable');
    $this->assertEquals(++$previous_file_id, $this->getLastFileId(), 'Generated file was stored.');
    $this->assertStringStartsWith('private://', File::load($this->getLastFileId())->getFileUri());

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

    // Hit the generation route again. This time we should be redirected to the
    // PDF file. Make sure no PHP error occured.
    $this->drupalGet($fillpdf_route);
    $this->assertSession()->addressEquals('/fillpdf');
    $this->assertSession()->statusCodeEquals(200);
    // Get back to the front page and check if an error was set, and we didn't
    // try to store the file.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains("File storage scheme private:// is unavailable, so a PDF file generated from FillPDF form Scheme test ($form_id) could not be stored.");
    $this->assertEquals($previous_file_id, $this->getLastFileId(), 'Generated file was not stored.');
  }

}
