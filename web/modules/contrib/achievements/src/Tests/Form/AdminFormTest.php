<?php

namespace Drupal\achievements\Tests\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the admin form.
 *
 * @group achievements
 */
class AdminFormTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['achievements'];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(['administer achievements']);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test the achievements admin form.
   */
  public function testAdminForm() {
    $settings = [
      'leaderboard_count_per_page' => 75,
      'leaderboard_relative' => 'disabled',
      'leaderboard_relative_nearby_ranks' => 7,
      'unlocked_move_to_top' => FALSE,
    ];
    $this->drupalPostForm(Url::fromRoute('achievements.admin'), $settings, t('Save configuration'));

    $config = $this->config('achievements.settings');
    foreach ($settings as $name => $value) {
      $this->assertEqual($value, $config->get($name), SafeMarkup::format('Achievement setting @setting properly updated', ['@setting' => $name]));
    }
  }

}
