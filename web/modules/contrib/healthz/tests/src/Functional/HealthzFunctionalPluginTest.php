<?php

namespace Drupal\Tests\healthz\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Functional tests using test plugins.
 *
 * @group healthz
 */
class HealthzFunctionalPluginTest extends FunctionalTestBase {

  /**
   * Tests that the JSON callback returns the correct responses.
   */
  public function testPluginReturnCodes() {
    $assert_session = $this->assertSession();
    // todo: use the UI to configure this.
    $checks = [
      'does_not_apply' => [
        'id' => 'does_not_apply',
        'provider' => 'healthz_test_plugin',
        'status' => TRUE,
        'weight' => -15,
        'failure_status_code' => 500,
        'settings' => [],
      ],
      'passing_check' => [
        'id' => 'passing_check',
        'provider' => 'healthz_test_plugin',
        'status' => TRUE,
        'weight' => -10,
        'failure_status_code' => 500,
        'settings' => [],
      ],
      'failing_200' => [
        'id' => 'failing_200',
        'provider' => 'healthz_test_plugin',
        'status' => TRUE,
        'weight' => -5,
        'failure_status_code' => 200,
        'settings' => [],
      ],
      'failing_check' => [
        'id' => 'failing_check',
        'provider' => 'healthz_test_plugin',
        'status' => TRUE,
        'weight' => 0,
        'failure_status_code' => 500,
        'settings' => [],
      ],
    ];
    $this->config->set('checks', $checks)->save();

    $this->drupalLogin($this->checkUser);
    $this->drupalGet("/healthz");
    $assert_session->statusCodeEquals(200);

    // Ensure the response contains expected passing checks and error messages.
    $response = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals(['passing_check', 'failing_200'], $response['checks']);
    $this->assertEquals('I always fail and return a 200', $response['errors']['failing_200'][0]);

    $checks['failing_200']['status'] = FALSE;
    $this->config->set('checks', $checks)->save();
    $this->drupalGet("/healthz");
    $assert_session->statusCodeEquals(500);
    $response = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals(['passing_check', 'failing_check'], $response['checks']);
    $this->assertEquals('I always fail', $response['errors']['failing_check'][0]);

    $checks['failing_check']['status'] = FALSE;
    $this->config->set('checks', $checks)->save();
    $this->drupalGet("/healthz");
    $assert_session->statusCodeEquals(200);
    $response = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals(['passing_check'], $response['checks']);
    $this->assertArrayNotHasKey('errors', $response);
  }

}
