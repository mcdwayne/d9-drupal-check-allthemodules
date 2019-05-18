<?php

namespace Drupal\Tests\personal_digest\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the email_example module.
 *
 * @group personal_digest
 */
abstract class PersonalDigestTestBase extends BrowserTestBase {

  /*
   * @var Admin User.
   */
  protected $adminUser;


  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'datetime',
    'views',
    'personal_digest',
    'personal_digest_tests_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser([], [], TRUE);
    $this->setCurrentUser($this->adminUser);

    // Add one view in the personal digest settings, in order to test the email
    // digest.
    $settings_conf = \Drupal::service('config.factory')->getEditable('personal_digest.settings');
    $settings_conf->set('views', ["personal_digest_test:default"]);
    $settings_conf->save();

  }

  protected function cronRun() {
    $this->drupalGet('cron/' . \Drupal::state()->get('system.cron_key'));
  }
}
