<?php

namespace Drupal\anonymous_publishing_cl\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Overall testing.
 *
 * @group Anonymous Publishing
 *
 * @ingroup anonymous_publishing
 */
class AnonymousPublishingAdminSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'comment',
    'anonymous_publishing',
    'anonymous_publishing_cl'
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Anonymous publishing',
      'description' => 'Tests for the Anonymous publishing module.',
      'group' => 'Anonymous publishing',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array('administer anonymous_publishing'));
    $this->drupalLogin($admin_user);

    // Sets the module appropriately for testing.
    \Drupal::configFactory()->getEditable('anonymous_publishing_cl.settings')
      ->set('allowed_content_types', array('article', 'comment'))
      ->set('flood_limit', -1)
      ->set('general_options', array(
        'sactivate' => TRUE,
        'modmail' => FALSE,
        'blockip' => FALSE,
        'aregist' => FALSE,
      ))
      ->set('flood_limit', -1)
      ->save();
  }

  public function testAnonymousPublishingSettings() {

    // Open admin UI.
    $this->drupalGet('/admin/config/people/anonymous_publishing_cl');

    // ----------------------------------------------------------------------
    // 1) Check the default settings value.
    $this->assertFieldChecked('edit-allowed-content-types-article', 'Anonymous Posting for article page is activated.');
    $this->assertFieldChecked('edit-allowed-content-types-comment', 'Anonymous Posting for comments is activated.');

    $this->assertFieldChecked('edit-general-options-sactivate', 'Allow self-activation option is activated.');
    $this->assertNoFieldChecked('edit-general-options-sactstick', 'Allow self-activation sticky option is disabled.');

    $this->assertFieldById('edit-flood-limit', '-1', 'Flood limit is set to -1');
  }


}
