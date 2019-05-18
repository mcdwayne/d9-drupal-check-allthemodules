<?php

namespace Drupal\Tests\ext_redirect\Functional;

use Drupal\Core\Url;

/**
 * Created by PhpStorm.
 * User: marek.kisiel
 * Date: 19/08/2017
 * Time: 15:13
 */

/**
 * Class ExtRedirectWebTest
 *
 * @package Drupal\Tests\ext_redirect\Web
 * @group ext_redirect
 */
class ExtRedirectStatusMessageTestBase extends ExtRedirectWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'External Redirect',
      'description' => 'Allow mapping redirects from external sites',
      'group' => 'External Redirect',
    );
  }



  public function testMissingConfigStatusMessage() {
    $this->drupalGet('<front>');
    $text = 'Primary host not specified';
    $this->assertText($text);

    /** @var \Drupal\ext_redirect\Service\ExtRedirectConfig $config */
    $config = \Drupal::service('ext_redirect.config');
    $config->setPrimaryHost('app.dev');
    $config->save();

    $this->drupalGet('<front>');
    $this->assertNoText($text);
  }

  public function testMissingConfigStatusMessageNotVisibleAtSettingsPage() {
    $this->initUserSession();
    $this->drupalLogin($this->user);
    $this->drupalGet('<front>');
    $this->assertText('Primary host not specified');
    $url = Url::fromRoute('ext_redirect.ext_redirect_settings_form');
    $this->drupalGet($url);
    $this->assertResponse(200);
    $this->assertNoText('Primary host not specified');

  }

}