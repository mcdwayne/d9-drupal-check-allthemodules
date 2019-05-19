<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for User Guide tests involving screenshots.
 */
abstract class ScreenshotTestBase extends WebDriverTestBase {

  /**
   * Set to TRUE to use cropping.
   *
   * Cropping doesn't currently work on DrupalCI.
   */
  protected $doCrop = FALSE;

  /**
   * The directory where screenshots should be saved.
   *
   * This is set in the setUpScreenshots() method.
   */
  protected $screenshotsDirectory;

  /**
   * The URL to the directory where screenshots should be saved.
   *
   * This is set in the setUpScreenshots() method.
   */
  protected $screenshotsDirectoryUrl;

  /**
   * Sets up the screenshots output directory.
   */
  protected function setUpScreenshots() {
    $this->screenshotsDirectory = $this->htmlOutputDirectory . '/' .
      $this->databasePrefix . '/screenshots';
    $this->ensureDirectoryWriteable($this->screenshotsDirectory, 'screenshots');
    $this->screenshotsDirectoryUrl = $GLOBALS['base_url'] .
      '/sites/simpletest/browser_output/' . $this->databasePrefix .
      '/screenshots';
    $this->logTestMessage('SCREENSHOTS GOING TO: ' . $this->screenshotsDirectory);
    $this->getSession()->resizeWindow(1200, 800);
  }

  /**
   * Saves an image to the browser output directory, and logs it.
   *
   * @param resource $image
   *   GD library image resource to save.
   * @param string $filename
   *   File name to save it with.
   */
  protected function logAndSaveImage($image, $filename) {
    // Save images to the HTML output directory, mirroring what happens
    // in $this->htmlOutput().
    imagepng($image, $this->screenshotsDirectory . '/' . $filename);
    $this->logTestMessage($this->screenshotsDirectoryUrl . '/' . $filename);
  }

  /**
   * Logs a message to the test log file.
   *
   * @param string $message
   *   Message to log. A line return will be appended.
   */
  protected function logTestMessage($message) {
    file_put_contents($this->htmlOutputFile, $message . "\n", FILE_APPEND);
  }

  /**
   * Ensures that we can write a file to a directory, with an assertion if not.
   *
   * @param string $directory
   *   Directory to ensure is writeable.
   * @param string $name
   *   Name of directory for error message if there is a problem.
   */
  protected function ensureDirectoryWriteable($directory, $name) {
    if (!$directory) {
      $this->fail("Attempting to ensure empty directory variable in $name");
      return;
    }
    // Attempt to create and modify permissions in the directory. Do not use
    // Drupal container calls, so this can run before installation.
    if (!is_dir($directory)) {
      @mkdir($directory, 0777, TRUE);
    }
    @chmod($directory, 0777);

    // Just to make sure, attempt to create a file. fopen fails if the file
    // exists, so attempt to delete it first, but ignore errors if it doesn't
    // exist yet (it shouldn't).
    $filename = $directory . '/temp_test' . $this->randomMachineName() . '.txt';
    @unlink($filename);
    $fp = @fopen($filename, 'x');
    if (!$fp) {
      $this->fail("Could not create output file $filename in $name");
    }
    else {
      fclose($fp);
      $this->pass("Directory $directory is working for $name");
    }
    @unlink($filename);
  }

  /**
   * Creates jQuery code to show only the selected part of the page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to be shown. Single
   *   quotes must be escaped.
   * @param bool $border
   *   (optional) If TRUE, also add a white border around $selector. This is
   *   needed as a buffer for trimming the image, if the part you are trimming
   *   to is along the edge of the page. Defaults to FALSE.
   *
   * @return string
   *   jQuery code that will hide everything else on the page. Also puts a
   *   white border around the page for trimming purposes. Note that everything
   *   inside $selector is also shown, which may not be what you want.
   *
   * @see UserGuideDemoTestBase::hideArea()
   */
  protected function showOnly($selector, $border = FALSE) {
    // Hide everything.
    $code = "jQuery('*').hide(); ";
    // Show the selected item and its children and parents.
    $code .= "jQuery('" . $selector . "').show(); ";
    $code .= "jQuery('" . $selector . "').parents().show(); ";
    $code .= "jQuery('" . $selector . "').find('*').show(); ";
    // Add border if indicated.
    if ($border) {
      $code .= $this->addBorder($selector, '#ffffff', TRUE);
    }
    return $code;
  }

  /**
   * Creates jQuery code to hide the selected part of the page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to hide. Single
   *   quotes must be escaped.
   *
   * @return string
   *   jQuery code that will hide this section of the page.
   *
   * @see UserGuideDemoTestBase::showOnly()
   */
  protected function hideArea($selector) {
    return "jQuery('" . $selector . "').hide(); ";
  }

  /**
   * Creates jQuery code to set the width of an area on the page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to set the width of.
   *   Single quotes must be escaped.
   * @param int $width
   *   (optional) Number of pixels. Defaults to 600.
   *
   * @return string
   *   jQuery code that will set the width of this area.
   */
  protected function setWidth($selector, $width = 600) {
    return "jQuery('" . $selector . "').css('width', '" . $width . "px'); ";
  }

  /**
   * Creates jQuery code to set the body background color.
   *
   * This is useful to aid in being able to trim the screenshot automatically.
   * On some pages, non-white body background color may interfere with being
   * able to trim the page effectively.
   *
   * @param string $color
   *   (optional) Color to set. Defaults to white.
   *
   * @return string
   *   jQuery code that will set the background color.
   */
  protected function setBodyColor($color = '#ffffff') {
    return "jQuery('body').css('background', '" . $color . "'); ";
  }

  /**
   * Creates jQuery code to omit scrollbars.
   *
   * This is useful to aid in being able to trim the screenshot automatically.
   * On some pages, the scrollbars may interfere with the process.
   *
   * @return string
   *   jQuery code that will set the body to not overflow.
   */
  protected function removeScrollbars() {
    return "jQuery('body').css('overflow', 'hidden');";
  }

  /**
   * Creates jQuery code to put a 2px border around an area of a page.
   *
   * @param string $selector
   *   jQuery selector for the part of the page you want to add a border to.
   *   Single quotes must be escaped.
   * @param string $color
   *   A hex color code starting with #. Defaults to the standard red color.
   * @param bool $remove_shadow
   *   (optional) TRUE to also remove the box shadow. Defaults to FALSE.
   *
   * @return string
   *   jQuery code that adds the border.
   */
  protected function addBorder($selector, $color = '#e62600', $remove_shadow = FALSE) {
    $code = "jQuery('" . $selector . "').css('border', '2px solid " . $color . "'); ";
    if ($remove_shadow) {
      $code .= "jQuery('" . $selector . "').css('box-shadow', 'none'); ";
    }

    return $code;
  }

  /**
   * Scrolls the window to the top, to avoid test weirdness.
   */
  protected function scrollWindowUp() {
    $this->getSession()->getDriver()->executeScript('window.scroll(0,0);');
  }

  /**
   * Override of assertText().
   *
   * The one in AssertLegacyTest doesn't work in WebDriverTestBase.
   */
  protected function assertText($text) {
    $this->assertSession()->pageTextContains($text);
  }

  /**
   * Debug: stops the test with the site open.
   */
  protected function stopTheTestForDebug() {
    $this->assertSession()->waitForElementVisible('css', '.test-wait', 100000000000000000000);
  }

}
