<?php

namespace Drupal\Tests\whitelabel\Kernel;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\whitelabel\Traits\WhiteLabelCreationTrait;

/**
 * Tests configuration overrides with White label.
 *
 * @group whitelabel
 */
class WhiteLabelConfigOverrideTest extends KernelTestBase {

  use WhiteLabelCreationTrait {
    createWhiteLabel as drupalCreateWhiteLabel;
  }
  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'text',
    'options',
    'user',
    'file',
    'image',
    'whitelabel',
  ];


  private $whiteLabel;

  private $values = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['system', 'whitelabel']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('whitelabel');

    $viewer = $this->drupalCreateUser(['view white label pages']);
    $this->setCurrentUser($viewer);

    // Create a logo.
    $image = File::create([
      'uri' => 'public://white-label-logo.png',
    ]);
    $image->save();

    // Create a user.
    $account = $this->drupalCreateUser(['serve white label pages']);

    // Create a white label with given logo and user.
    $this->values = [
      'token' => $this->randomMachineName(),
      'uid' => $account->id(),
      'name' => $this->randomString(),
      'slogan' => $this->randomString(),
      'logo_path' => $image->getFileUri(),
      'logo_url' => '',
      'logo_default' => FALSE,
    ];
    $this->whiteLabel = $this->createWhiteLabel($this->values);
    $this->whiteLabel
      // Set the actual logo now, to have above array match the output.
      ->setLogo($image)
      ->save();

    // Make sure all configuration fields are enabled.
    $this->config('whitelabel.settings')->setData([
      'site_name' => TRUE,
      'site_name_display' => TRUE,
      'site_slogan' => TRUE,
      'site_logo' => TRUE,
      'site_colors' => TRUE,
      'site_theme' => TRUE,
      'site_admin_theme' => NULL,
    ])->save();

    // Set the white label.
    $this->setCurrentWhiteLabel($this->whiteLabel);
  }

  /**
   * Test to see if the white label configuration works correctly.
   */
  public function testConfigurationOverrides() {
    $site_config = $this->container->get('config.factory')->get('system.site');
    $theme_config = $this->container->get('config.factory')->get('system.theme.global');

    // Test non-overridden configs.
    $read_configuration = [
      'name' => $site_config->getOriginal('name', FALSE),
      'slogan' => $site_config->getOriginal('slogan', FALSE),
      'logo_path' => $theme_config->getOriginal('logo.path', FALSE),
      'logo_default' => $theme_config->getOriginal('logo.use_default', FALSE),
    ];

    // Make sure configuration does not matches the white label.
    foreach ($read_configuration as $key => $detected_value) {
      $this->assertNotEquals($this->values[$key], $detected_value);
    }

    // Test overridden configs.
    $read_configuration = [
      'name' => $site_config->get('name'),
      'slogan' => $site_config->get('slogan'),
      'logo_path' => $theme_config->get('logo.path'),
      'logo_default' => $theme_config->get('logo.use_default'),
    ];

    // Make sure configuration does match the white label.
    foreach ($read_configuration as $key => $detected_value) {
      $this->assertEquals($this->values[$key], $detected_value, $key);
    }

    // Check once more with editable configuration (not overridden).
    $site_config = $this->config('system.site');
    $theme_config = $this->config('system.theme.global');

    // Test overridden configs.
    $read_configuration = [
      'name' => $site_config->get('name'),
      'slogan' => $site_config->get('slogan'),
      'logo_path' => $theme_config->get('logo.path'),
      'logo_default' => $theme_config->get('logo.use_default'),
    ];

    // Make sure configuration does not matches the white label.
    foreach ($read_configuration as $key => $detected_value) {
      $this->assertNotEquals($this->values[$key], $detected_value);
    }
  }

}
