<?php

/**
 * @file
 * Contains \Drupal\Tests\eloqua_rest_api\Unit\Factory\ClientFactory.
 */

namespace Drupal\Tests\eloqua_rest_api\Unit\Form {

  use Drupal\eloqua_rest_api\Form\EloquaRestApiAdminSettings;
  use Drupal\Tests\eloqua_rest_api\Unit\EloquaConfigBase;

  /**
   * Tests Eloqua REST API admin configuration form.
   *
   * @group eloqua
   */
  class EloquaRestApiAdminSettingsTest extends EloquaConfigBase {

    /**
     * Ensure the form ID returned by the form is as expected.
     * @test
     */
    public function shouldReturnFormId() {
      $adminForm = $this->getAdminForm();
      $this->assertEquals($adminForm->getFormId(), 'eloqua_rest_api_admin_settings');
    }

    /**
     * Ensure the form gets built as expected.
     * @test
     */
    public function shouldBuildForm() {
      $formStateMock = $this->getMock('\Drupal\Core\Form\FormStateInterface');
      $array = [];

      // Set up config factory expectations.
      $expectedConfigs = [
        'eloqua_rest_api_site_name' => 'SiteName',
        'eloqua_rest_api_login_name' => 'Login.Name',
        'eloqua_rest_api_login_password' => 'batteryhorsestaple',
        'eloqua_rest_api_base_url' => 'https://secure.p01.eloqua.com/API/REST',
        'eloqua_rest_api_timeout' => 10,
      ];
      $configObserver = $this->getMockConfigWithCredentials($expectedConfigs);
      $configFactoryObserver = $this->getConfigFactoryReturning($configObserver);

      // Build the admin form.
      $adminForm = $this->getAdminForm($configFactoryObserver);
      $form = $adminForm->buildForm($array, $formStateMock);

      // Assert default values.
      foreach ($expectedConfigs as $name => $expectedValue) {
        $this->assertEquals($form[$name]['#default_value'], $expectedValue);
      }
    }

    /**
     * Ensure the form is submitted as expected.
     * @test
     */
    public function shouldSubmitForm() {
      $form = [
        'eloqua_rest_api_site_name' => [
          '#parents' => 'expected_site_name_parents',
        ],
        'eloqua_rest_api_login_name' => [
          '#parents' => 'expected_login_name_parents',
        ],
        'eloqua_rest_api_login_password' => [
          '#parents' => 'expected_loginc_pw_parents',
        ],
        'eloqua_rest_api_base_url' => [
          '#parents' => 'expected_base_url_parents',
        ],
        'eloqua_rest_api_timeout' => [
          '#parents' => 'expected_timeout_parents',
        ],
        'form_id' => [
          '#parents' => 'should_not_be_called',
        ],
      ];

      // Set up config expectations.
      $expectedConfigs = [
        'eloqua_rest_api_site_name' => 'SiteName',
        'eloqua_rest_api_login_name' => 'Login.Name',
        'eloqua_rest_api_login_password' => 'batteryhorsestaple',
        'eloqua_rest_api_base_url' => 'https://secure.p01.eloqua.com/API/REST',
        'eloqua_rest_api_timeout' => 20,
      ];

      $formStateMock = $this->getMock('\Drupal\Core\Form\FormStateInterface');
      $formStateMock->expects($this->exactly(5))
        ->method('getValue')
        ->withConsecutive(
          $this->equalTo($form['eloqua_rest_api_site_name']['#parents']),
          $this->equalTo($form['eloqua_rest_api_login_name']['#parents']),
          $this->equalTo($form['eloqua_rest_api_login_password']['#parents']),
          $this->equalTo($form['eloqua_rest_api_base_url']['#parents']),
          $this->equalTo($form['eloqua_rest_api_timeout']['#parents'])
        )
        ->will($this->onConsecutiveCalls(
          $expectedConfigs['eloqua_rest_api_site_name'],
          $expectedConfigs['eloqua_rest_api_login_name'],
          $expectedConfigs['eloqua_rest_api_login_password'],
          $expectedConfigs['eloqua_rest_api_base_url'],
          $expectedConfigs['eloqua_rest_api_timeout']
        ));

      // Ensure the "save" method is called on the expected config object.
      $configObserver = $this->getMockBuilder('\Drupal\Core\Config\Config')
        ->disableOriginalConstructor()
        ->getMock();
      $configObserver->expects($this->once())
        ->method('save');

      // Ensure expected configs are set on the config object.
      $configObserver->expects($this->exactly(5))
        ->method('set')
        ->withConsecutive([
          $this->equalTo('eloqua_rest_api_site_name'),
          $this->equalTo($expectedConfigs['eloqua_rest_api_site_name']),
        ], [
          $this->equalTo('eloqua_rest_api_login_name'),
          $this->equalTo($expectedConfigs['eloqua_rest_api_login_name']),
        ], [
          $this->equalTo('eloqua_rest_api_login_password'),
          $this->equalTo($expectedConfigs['eloqua_rest_api_login_password']),
        ], [
          $this->equalTo('eloqua_rest_api_base_url'),
          $this->equalTo($expectedConfigs['eloqua_rest_api_base_url']),
        ], [
          $this->equalTo('eloqua_rest_api_timeout'),
          $this->equalTo($expectedConfigs['eloqua_rest_api_timeout']),
        ]);

      // Ensure a mutable version of the config object is returned by the factory.
      $configFactoryObserver = $this->getConfigFactoryReturning($configObserver, 'getEditable');

      // Build the admin form.
      $adminForm = $this->getAdminForm($configFactoryObserver);
      $adminForm->submitForm($form, $formStateMock);
    }

    /**
     * @param $configFactory
     * @return EloquaRestApiAdminSettings
     */
    protected function getAdminForm($configFactory = NULL) {
      $translationMock = $this->getMock('\Drupal\Core\StringTranslation\TranslationInterface');

      if (empty($configFactory)) {
        $configFactory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');
      }

      $factory = new EloquaRestApiAdminSettings($configFactory);
      $factory->setStringTranslation($translationMock);
      return $factory;
    }

  }

}

// Necessary because ConfigFormBase uses global drupal_set_message() function.
namespace Drupal\Core\Form {
  if (!function_exists('\Drupal\Core\Form\drupal_set_message')) {
    function drupal_set_message() {}
  }
}
