<?php

namespace Drupal\Tests\migrate_override\Functional;

use Drupal\migrate_override\OverrideManagerService;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the settings form.
 *
 * @group migrate_override
 */
class SettingsTest extends BrowserTestBase {

  public static $modules = [
    'system',
    'node',
    'user',
    'migrate',
    'migrate_override',
  ];

  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->nodeType = $this->createContentType();
  }

  /**
   * Tests the settings form.
   */
  public function testSettingsForm() {
    $account = User::load(1);

    // Reset the password.
    $password = 'foo';
    $account->setPassword($password)->save();

    // Support old and new tests.
    $account->passRaw = $password;

    $this->drupalLogin($account);
    $this->drupalGet('admin/config/migrate_override/migrateoverridesettings');
    $this->assertResponse(200);
    $this->assertText('Content Entity Type');
    $this->assertText($this->nodeType->label());
    $edit = [
      'node[' . $this->nodeType->id() . '][migrate_override_enabled]'  => TRUE,
      'node[' . $this->nodeType->id() . '][fields][title]' => OverrideManagerService::FIELD_OVERRIDEABLE,
    ];
    $this->submitForm($edit, 'Save');

    $config = \Drupal::config('migrate_override.migrateoverridesettings');
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    /** @var \Drupal\migrate_override\OverrideManagerServiceInterface $service */
    $service = \Drupal::service('migrate_override.override_manager');
    $this->assertTrue($config->get('entities.node.' . $this->nodeType->id() . ".migrate_override_enabled"));
    $this->assertSame(OverrideManagerService::FIELD_OVERRIDEABLE, $config->get('entities.node.' . $this->nodeType->id() . '.fields.title'));
    $this->assertTrue($service->entityBundleHasField('node', $this->nodeType->id()));
  }

}
