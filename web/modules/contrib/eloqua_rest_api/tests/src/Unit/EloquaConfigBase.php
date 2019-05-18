<?php

/**
 * @file
 * Contains \Drupal\Tests\eloqua_rest_api\Unit\EloquaConfigBase.
 */

namespace Drupal\Tests\eloqua_rest_api\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests Eloqua REST API admin configuration form.
 *
 * @group eloqua
 */
abstract class EloquaConfigBase extends UnitTestCase {

  /**
   * @param $configs
   */
  protected function getConfigFactoryReturning($configs, $method = 'get') {
    $configFactoryObserver = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $configFactoryObserver->expects($this->any())
      ->method($method)
      ->with($this->equalTo('eloqua_rest_api.settings'))
      ->willReturn($configs);
    return $configFactoryObserver;
  }

  /**
   * @param array $expectedConfigs
   */
  protected function getMockConfigWithCredentials(array $expectedConfigs = []) {
    // Provide default values.
    if (empty($expectedConfigs)) {
      $expectedConfigs = [
        'eloqua_rest_api_site_name' => '',
        'eloqua_rest_api_login_name' => '',
        'eloqua_rest_api_login_password' => '',
        'eloqua_rest_api_base_url' => NULL,
        'eloqua_rest_api_timeout' => 10,
      ];
    }

    // Ensure configuration is pulled for each credential.
    $configObserver = $this->getMockBuilder('\Drupal\Core\Config\ImmutableConfig')
      ->disableOriginalConstructor()
      ->getMock();

    // Ensure the "get" method is called 5 times, as expected.
    $configObserver->expects($this->atLeast(5))
      ->method('get')
      ->withConsecutive(
        $this->equalTo('eloqua_rest_api_site_name'),
        $this->equalTo('eloqua_rest_api_login_name'),
        $this->equalTo('eloqua_rest_api_login_password'),
        $this->equalTo('eloqua_rest_api_base_url'),
        $this->equalTo('eloqua_rest_api_timeout')
      )
      // Will the provided values.
      ->will($this->onConsecutiveCalls(
        $expectedConfigs['eloqua_rest_api_site_name'],
        $expectedConfigs['eloqua_rest_api_login_name'],
        $expectedConfigs['eloqua_rest_api_login_password'],
        $expectedConfigs['eloqua_rest_api_base_url'],
        $expectedConfigs['eloqua_rest_api_timeout']
      ));

    return $configObserver;
  }

}
