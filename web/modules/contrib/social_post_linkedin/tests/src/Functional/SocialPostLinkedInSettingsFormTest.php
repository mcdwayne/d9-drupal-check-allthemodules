<?php

namespace Drupal\Tests\social_post_linkedin\Functional;

use Drupal\social_api\SocialApiSettingsFormBaseTest;

/**
 * Test Social Post LinkedIn settings form.
 *
 * @group social_post
 *
 * @ingroup social_post_linkedin
 */
class SocialPostLinkedInSettingsFormTest extends SocialApiSettingsFormBaseTest {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['social_post_linkedin'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->module = 'social_post_linkedin';
    $this->socialNetwork = 'linkedin';
    $this->moduleType = 'social-post';

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
