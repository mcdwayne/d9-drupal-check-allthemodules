<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Creates drupal.org screenshots and tests drupal.org text in the User Guide.
 *
 * Note: Currently, drupal.org tests cannot reliably be run, because drupal.org
 * is blocking visiting drupal.org from tests. So, this test right now just
 * tests the screenshot process itself, independent of the user guide tests.
 *
 * See README.txt file in the module directory for more information about
 * running tests and making screenshots.
 *
 * @group UserGuide
 */
class DrupalScreenshotTest extends ScreenshotTestBase {

  /**
   * Tests the screenshot process.
   */
  public function testCreateScreenshot() {
    $this->setUpScreenshots();
    $this->visitUrl('https://www.drupal.org/documentation');

    // Take a screenshot and verify that it is not all the same color.
    $image = imagecreatefromstring($this->getSession()->getScreenshot());
    $this->logAndSaveImage($image, 'test-screenshot-before-crop.png');

    $xsize = imagesx($image);
    $ysize = imagesy($image);
    $first_color = imagecolorat($image, 0, 0);
    $allsame = TRUE;
    for ($x = 0; $x < $xsize && $allsame; $x++) {
      for ($y = 0; $y < $ysize && $allsame; $y++) {
        if (imagecolorat($image, $x, $y) != $first_color) {
          $allsame = FALSE;
        }
      }
    }
    $this->assertFalse($allsame, 'Screenshot was not all the same color');
    $this->assertTrue($xsize > 500 && $ysize > 500, 'Screenshot was a good size');

    if ($this->doCrop) {
      // Crop in various ways, and verify the result is not empty. Note: these
      // all fail currently on DrupalCI.
      $cropped = imagecropauto($image, IMG_CROP_THRESHOLD, 5, 0xFFFFFF);
      $this->assertTrue($cropped !== FALSE, 'Image crop with threshold succeeded');
      if ($cropped) {
        $this->assertTrue(imagesx($cropped) > 10 && imagesy($cropped) > 10, 'Image threshold crop resulted in a reasonable size');
        $this->logAndSaveImage($image, 'test-screenshot-threshold-crop.png');
      }

      $cropped = imagecropauto($image, IMG_CROP_SIDES);
      $this->assertTrue($cropped !== FALSE, 'Image crop with sides succeeded');
      if ($cropped) {
        $this->assertTrue(imagesx($cropped) > 10 && imagesy($cropped) > 10, 'Image sides crop resulted in a reasonable size');
        $this->logAndSaveImage($image, 'test-screenshot-sides-crop.png');
      }

      $cropped = imagecropauto($image, IMG_CROP_WHITE);
      $this->assertTrue($cropped !== FALSE, 'Image crop with white succeeded');
      if ($cropped) {
        $this->assertTrue(imagesx($cropped) > 10 && imagesy($cropped) > 10, 'Image white crop resulted in a reasonable size');
        $this->logAndSaveImage($image, 'test-screenshot-white-crop.png');
      }
    }
  }

  /**
   * Opens an absolute URL.
   *
   * Like drupalGet() but without the complications.
   *
   * @param string $url
   *   URL to visit.
   */
  protected function visitUrl(string $url) {
    $this->getSession()->visit($url);
  }
}
