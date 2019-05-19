<?php

namespace Drupal\Tests\social_auth_hid\Functional;

use Drupal\social_api\SocialApiSettingsFormBaseTest;

/**
 * Test Social Auth Humanitarian ID settings form.
 *
 * @group social_auth
 *
 * @ingroup social_auth_hid
 */
class SocialAuthHidSettingsFormTest extends SocialApiSettingsFormBaseTest {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['social_auth_hid'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->module = 'social_auth_hid';
    $this->socialNetwork = 'hid';
    $this->moduleType = 'social-auth';

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function testIsAvailableInIntegrationList() {
    $this->fields = ['client_id', 'client_secret'];

    parent::testIsAvailableInIntegrationList();
  }

  /**
   * {@inheritdoc}
   */
  public function testSettingsFormSubmission() {
    $this->edit = [
      'client_id' => $this->randomString(10),
      'client_secret' => $this->randomString(10),
    ];

    parent::testSettingsFormSubmission();
  }

}
