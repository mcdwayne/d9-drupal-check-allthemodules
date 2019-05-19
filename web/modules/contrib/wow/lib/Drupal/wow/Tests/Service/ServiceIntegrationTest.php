<?php
/**
 * @file
 * Definition of ServiceIntegrationTest.
 */

namespace Drupal\wow\Tests\Service;

use Drupal\wow\Tests\UnitTestBase;

use WoW\Core\Service\ServiceHttps;
use WoW\Core\Service\ServiceHttp;

/**
 * Tests the integration with the service.
 */
class ServiceIntegrationTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Service Integration Tests',
      'description' => 'Integration Tests with the Battle.net service.',
      'group' => 'WoW',
    );
  }

  protected function setUp() {
    parent::setUp();
    drupal_load('module', 'wow');
  }

  /**
   * Tests normal HTTP requests (China redirect to HTTPS).
   */
  function testHTTPRequests() {
    $infos = wow_service_info();
    if (!extension_loaded('openssl')) {
      // Removes China as it redirect to HTTPS.
      unset($infos['cn']);
    }

    foreach ($infos as $region => $info) {
      $args = array('!region' => $info->name);
      $service = new ServiceHttp($region, $info->locales);

      // Test battle groups.
      $response = $service->newRequest('data/battlegroups/')->execute();
      // Only trigger the test if service is available.
      if ($response->getCode() != 503) {
        $this->assertTrue(array_key_exists('battlegroups', $response->getData()), t('!region service returned a HTTP 200 status.', $args), 'WoW');

        // Test a non existing resource.
        $response = $service->request("non-existing-path");
        $this->assertEqual(404, $response->getCode(), t("!region service returned a HTTP 404 status.", $args), 'WoW');
      }
      else {
        $this->pass(t("!region service is currently unavailable (maintenance).", $args), 'WoW');
      }
    }
  }

  /**
   * Tests normal HTTPS requests (Only if OpenSSL extension is loaded).
   */
  function testHTTPSRequests() {
    if (extension_loaded('openssl')) {
      foreach (wow_service_info() as $region => $info) {
        $args = array('!region' => $info->name);
        // Tests the service with a fake public and private key.
        $service = new ServiceHttps($region, $info->locales, 'fake-public-key', 'fake-private-key');
        $response = $service->newRequest('realm/status')->execute();
        // Only trigger the test if service is available.
        if ($response->getCode() != 503) {
          $this->assertEqual(500, $response->getCode(), t("!region service returned a HTTP 500 status.", $args), 'WoW');
          $this->assertEqual('Invalid Application', $response->getData('reason'), t("Reason given '!reason', expected 'Invalid Application'.", array('!reason' => $response->getData('reason'))), 'WoW');
        }
      }
    }
  }

}
