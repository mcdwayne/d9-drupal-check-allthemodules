<?php

namespace Drupal\mass_contact\Tests\Form;

use Drupal\mass_contact\Form\AdminSettingsForm;
use Drupal\mass_contact\MassContactInterface;
use Drupal\system\Tests\System\SystemConfigFormTestBase;

/**
 * Admin settings form test.
 *
 * @group mass_contact
 */
class AdminSettingsFormTest extends SystemConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'mass_contact',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->form = AdminSettingsForm::create($this->container);
    $values = [
      'form_information' => $this->randomString(),
      'recipient_limit' => 42,
      'send_with_cron' => TRUE,
      'optout_enabled' => MassContactInterface::OPT_OUT_GLOBAL,
      'create_archive_copy' => NULL,
      'hourly_threshold' => 33,
      'category_display' => 'checkboxes',
    ];
    foreach ($values as $config_key => $value) {
      $this->values[$config_key] = [
        '#value' => $value,
        '#config_name' => 'mass_contact.settings',
        '#config_key' => $config_key,
      ];
    }
  }

}
