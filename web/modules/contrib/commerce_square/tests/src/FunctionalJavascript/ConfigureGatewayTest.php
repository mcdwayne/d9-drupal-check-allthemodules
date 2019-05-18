<?php

namespace Drupal\Tests\commerce_square\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JSWebAssert;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;

/**
 * Tests the creation and configuration of the gateway.
 *
 * @group commerce_square
 */
class ConfigureGatewayTest extends CommerceBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_payment',
    'commerce_square',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_payment_gateway',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Tests that a Square gateway can be configured.
   */
  public function testCreateSquareGateway() {
    $this->drupalGet(Url::fromRoute('commerce_square.settings')->toString());

    $this->getSession()->getPage()->fillField('Application Secret', 'fluff');
    $this->getSession()->getPage()->fillField('Application Name', 'Drupal Commerce 2 Demo');
    $this->getSession()->getPage()->fillField('Application ID', 'sq0idp-nV_lBSwvmfIEF62s09z0-Q');
    $this->getSession()->getPage()->fillField('Sandbox Application ID', 'sandbox-sq0idp-nV_lBSwvmfIEF62s09z0-Q');
    $this->getSession()->getPage()->fillField('Sandbox Access Token', 'sandbox-sq0atb-uEZtx4_Qu36ff-kBTojVNw');
    $this->getSession()->getPage()->pressButton('Save configuration');

    $is_squareup = strpos($this->getSession()->getCurrentUrl(), 'squareup.com');
    $this->assertTrue($is_squareup !== FALSE);

    $this->drupalGet('admin/commerce/config/payment-gateways/add');
    $this->getSession()->getPage()->fillField('Name', 'Square');
    $this->getSession()->getPage()->checkField('Square');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->fillField('id', 'square');
    $this->getSession()->getPage()->checkField('Sandbox');
    $this->getSession()->getPage()->fillField('configuration[square][test][test_location_id]', 'CBASEGmzMStUzri2iDAveKJhcd8gAQ');
    $this->assertSession()->fieldDisabled('configuration[square][live][live_location_id]');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->responseContains(new FormattableMarkup('Saved the %label payment gateway.', ['%label' => 'Square']));

    $this->drupalGet('admin/commerce/config/payment-gateways/manage/square');
    $this->getSession()->getPage()->checkField('Production');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('You must select a location for the configured mode.');
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\FunctionalJavascriptTests\JSWebAssert
   *   A new web-assert option for asserting the presence of elements with.
   */
  public function assertSession($name = NULL) {
    return new JSWebAssert($this->getSession($name), $this->baseUrl);
  }

  /**
   * Creates a screenshot.
   *
   * @param bool $set_background_color
   *   (optional) By default this method will set the background color to white.
   *   Set to FALSE to override this behaviour.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When operation not supported by the driver.
   * @throws \Behat\Mink\Exception\DriverException
   *   When the operation cannot be done.
   */
  protected function createScreenshot($set_background_color = TRUE) {
    $jpg_output_filename = $this->htmlOutputClassName . '-' . $this->htmlOutputCounter . '-' . $this->htmlOutputTestId . '.jpg';
    $session = $this->getSession();
    if ($set_background_color) {
      $session->executeScript("document.body.style.backgroundColor = 'white';");
    }
    $image = $session->getScreenshot();
    file_put_contents($this->htmlOutputDirectory . '/' . $jpg_output_filename, $image);
    $this->htmlOutputCounter++;
  }

}
