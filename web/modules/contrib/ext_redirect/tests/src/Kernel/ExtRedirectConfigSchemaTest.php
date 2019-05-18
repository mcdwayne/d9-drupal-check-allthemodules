<?php
/**
 * Created by PhpStorm.
 * User: marek.kisiel
 * Date: 25/07/2017
 * Time: 12:26
 */

namespace Drupal\Tests\ext_redirect\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class ExtRedirectConfigSchemaTest
 *
 * @package Drupal\Tests\ext_redirect\Kernel
 * @group ext_redirect
 */
class ExtRedirectConfigSchemaTest extends KernelTestBase {

  protected static $modules = ['ext_redirect'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['ext_redirect']);
  }

  public function testConfigSchema() {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('ext_redirect.settings');
    self::assertInstanceOf('\Drupal\Core\Config\Config', $config);
    $primary_host = 'app.dev';
    $config->set('primary_host', $primary_host);

    $aliases = ['alias1.dev', 'alias2.dev', 'alias3.dev'];
    $config->set('allowed_host_aliases', $aliases);

    $config->save();

    $storedAliases = $config->get('allowed_host_aliases');

    self::assertEquals($primary_host, $config->get('primary_host'));
    self::assertCount(3, $storedAliases);
    self::assertArraySubset($aliases, $storedAliases);
  }

  public function testConfig() {
    /** @var \Drupal\ext_redirect\Service\ExtRedirectConfig $extRedirectConfig */
    $extRedirectConfig = \Drupal::service('ext_redirect.config');
    $this->assertInstanceOf('Drupal\ext_redirect\Service\ExtRedirectConfig', $extRedirectConfig);

    $primaryHost = 'app.dev';
    $extRedirectConfig->setPrimaryHost($primaryHost);

    $aliases = "alias1.dev\nalias2.dev\nalias3.dev";

    $extRedirectConfig->setAllowedHostAliasesFromString($aliases);

    $extRedirectConfig->save();

    $this->assertEquals($primaryHost, $extRedirectConfig->getPrimaryHost());

    $arrAliases = explode("\n", $aliases);
    $this->assertArraySubset($arrAliases, $extRedirectConfig->getAllowedHostAliases());
    $this->assertEquals($aliases, $extRedirectConfig->getAllowedHostAliasesAsString());
  }
}