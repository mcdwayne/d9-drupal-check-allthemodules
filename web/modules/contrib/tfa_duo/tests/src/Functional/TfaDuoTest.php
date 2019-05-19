<?php

namespace Drupal\Tests\tfa_duo\Functional;

use Drupal\key\Entity\Key;
use Drupal\Tests\tfa\Functional\TfaTestBase;
use Drupal\tfa\TfaLoginTrait;

/**
 * Tests TFA Duo setup.
 *
 * @package Drupal\Tests\tfa_duo\Functional
 *
 * @group tfa_duo
 */
class TfaDuoTest extends TfaTestBase {
  use TfaLoginTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'tfa_test_plugins',
    'tfa_duo',
    'tfa',
    'encrypt',
    'encrypt_test',
    'key',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Generate a Duo key.
    Key::create([
      'id' => 'testing_duo_key',
      'label' => 'Testing Duo Key',
      'key_type' => "duo",
      'key_type_settings' => [],
      'key_provider' => 'file',
      'key_provider_settings' => [
        'file_location' => realpath(drupal_get_path('module', 'tfa_duo') . '/tests/assets/tfa_duo_test.key'),
        'strip_line_breaks' => FALSE,
      ],
    ])->save();
  }

  /**
   * Test TFA Duo module setup and functionality.
   */
  public function testTfaDuo() {
    // Setup tfa to use tfa_duo.
    $user = $this->drupalCreateUser(['admin tfa settings']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/people/tfa');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $edit = [
      'tfa_enabled' => TRUE,
      'tfa_validate' => 'tfa_duo',
      'tfa_required_roles[authenticated]' => TRUE,
      'tfa_allowed_validation_plugins[tfa_duo]' => TRUE,
      'validation_plugin_settings[tfa_duo][duo_key]' => 'testing_duo_key',
      'encryption_profile' => $this->encryptionProfile->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->assertTrue($assert_session->optionExists('edit-tfa-validate', 'tfa_duo')->isSelected(), t('Duo Plugin selected'));
    $this->drupalLogout();

    // Verify a normal user is prompted with the tfa page.
    $user = $this->drupalCreateUser();
    $edit = ['name' => $user->getAccountName(), 'pass' => $user->passRaw];
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $assert_session->addressEquals('tfa/' . $user->id() . '/' . $this->getLoginHash($user));
  }

}
