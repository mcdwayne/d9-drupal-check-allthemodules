<?php

/**
 * @file
 * Definition of ServiceTest.
 */

namespace Drupal\wow\Tests\Service;

use Drupal\wow\Mocks\RequestMock;

use Drupal\wow\Mocks\ServiceHttpStubRequest;
use Drupal\wow\Mocks\ServiceHttpsStubRequest;
use Drupal\wow\Tests\UnitTestBase;

use WoW\Core\Response;
use WoW\Core\Service\ServiceHttp;
use WoW\Core\Service\ServiceHttps;

/**
 * Test Service methods.
 */
class ServiceTest extends UnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Service',
      'description' => 'Unit Tests Service.',
      'group' => 'WoW',
    );
  }

  public function testHttpRequest() {
    // Assert the path and host are correctly set.
    $service = new ServiceHttpStubRequest('dummy', array());
    $response = $service->request('test/path', array(), array('Date' => 'Dummy Date'));

    $this->assertEqual('http://dummy/api/wow/test/path', $response->getRequest(), 'Found URL on request.', 'WoW');
    $this->assertEqual('Dummy Date', $response->getHeader('Date'), 'Found Date header on request.', 'WoW');
  }

  public function testHttpsRequest() {
    // Assert the path and host are correctly set.
    $service = new ServiceHttpsStubRequest('dummy', array(), 'fake-public-key', 'fake-private-key');
    $response = $service->request('test/path', array(), array('Date' => 'Dummy Date'));

    $this->assertEqual('https://dummy/api/wow/test/path', $response->getRequest(), 'Found URL on request.', 'WoW');
    $this->assertEqual('Dummy Date', $response->getHeader('Date'), 'Found Date header on request.', 'WoW');
    $this->assertEqual('BNET fake-public-key:4JX6CqfP/MbigbAdkSkfVotQEsk=', $response->getHeader('Authorization'), 'Found Authorization header on request.', 'WoW');
  }

  public function testGetLocale() {
    $service = new ServiceHttpStubRequest('dummy', array(
      'en' => 'en_GB',
      'fr' => 'fr_FR',
      'es-MX' => 'es_ES',
    ));

    $locale = $service->getLocale('fr');
    $this->assertEqual('fr_FR', $locale, 'Found fr_FR locale parameter on request.', 'WoW');

    $locale = $service->getLocale('en');
    $this->assertEqual('en_GB', $locale, 'Found en_GB locale parameter on request.', 'WoW');

    $locale = $service->getLocale('es-MX');
    $this->assertEqual('es_ES', $locale, 'Found es_ES locale parameter on request.', 'WoW');

    $locale = $service->getLocale('non-existing');
    $this->assertNull($locale, 'No locale parameter on request.', 'WoW');
  }
}
