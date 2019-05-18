<?php

namespace Drupal\Tests\fillpdf\Functional;

use Drupal\Core\Url;

/**
 * @coversDefaultClass \Drupal\fillpdf\Service\FillPdfLinkManipulator
 *
 * @group fillpdf
 *
 * @todo Convert into a unit test.
 */
class LinkManipulatorTest extends FillPdfUploadTestBase {

  /**
   * Tests handling of a non-existing FillPdfForm ID.
   */
  public function testLinkExceptions() {
    // Hit the generation route with no query string set.
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], []);
    $this->drupalGet($fillpdf_route);
    // Ensure the exception is converted to an error and access is denied.
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains("This link doesn't specify a query string, so failing.");

    // Hit the generation route with no fid set.
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'sample' => 1,
      ],
    ]);
    $this->drupalGet($fillpdf_route);
    // Ensure the exception is converted to an error and access is denied.
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains("No FillPdfForm was specified in the query string, so failing.");

    // Hit the generation route with a non-existing fid set.
    $fillpdf_route = Url::fromRoute('fillpdf.populate_pdf', [], [
      'query' => [
        'fid' => 1234,
      ],
    ]);
    $this->drupalGet($fillpdf_route);
    // Ensure the exception is converted to an error and access is denied.
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains("The requested FillPdfForm doesn't exist, so failing.");
  }

}
