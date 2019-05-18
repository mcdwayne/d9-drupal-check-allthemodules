<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\fillpdf\Traits\TestFillPdfTrait;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\Entity\FillPdfForm;

/**
 * Tests PDF parsing.
 *
 * @group fillpdf
 */
class PdfParseTest extends BrowserTestBase {

  use TestFillPdfTrait;

  static public $modules = ['fillpdf_test'];
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->initializeUser();
  }

  /**
   * Tests PDF population using local service.
   *
   * @throws \PHPUnit_Framework_SkippedTestError
   *   Thrown when test had to be skipped as FillPDF LocalServer is not
   *   available.
   */
  public function testParseLocalService() {
    // For local container testing, we require the Docker container to be
    // running on port 8085. If http://127.0.0.1:8085/ping does not return a
    // 200, we assume that we're not in an environment where we can run this
    // test.
    $this->configureLocalServiceBackend();
    $config = $this->container->get('config.factory')->get('fillpdf.settings');
    if (!FillPdf::checkLocalServiceEndpoint($this->container->get('http_client'), $config)) {
      throw new \PHPUnit_Framework_SkippedTestError('FillPDF LocalServer unavailable, so skipping test.');
    }
    $this->backendTest();
  }

  /**
   * Tests PDF population using a local install of pdftk.
   *
   * @throws \PHPUnit_Framework_SkippedTestError
   *   Thrown when test had to be skipped as local pdftk install is not
   *   available.
   *
   * @todo Implementation missing.
   */
  public function testParsePdftk() {
    $this->configureFillPdf(['backend' => 'pdftk']);
    if (!FillPdf::checkPdftkPath()) {
      throw new \PHPUnit_Framework_SkippedTestError('pdftk not available, so skipping test.');
    }
    $this->backendTest();
  }

  /**
   * Tests a backend.
   */
  protected function backendTest() {
    $this->uploadTestPdf('fillpdf_test_v3.pdf');
    $this->assertSession()->pageTextNotContains('No fields detected in PDF.');

    $fillpdf_form = FillPdfForm::load($this->getLatestFillPdfForm());
    $fields = $fillpdf_form->getFormFields();
    $this->assertCount(11, $fields);
  }

}
