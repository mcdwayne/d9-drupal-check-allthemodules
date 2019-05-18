<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;

/**
 * Tests to verify that the Emotion Detection feature works correctly.
 *
 * @group google_vision
 */
class UserEmotionTest extends GoogleVisionTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user'];

  /**
   * A user with permission to create users with their profile pictures.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer google vision',
      'administer account settings',
      'administer user fields',
      'administer users',
    ]);
    $this->drupalLogin($this->adminUser);
    // Check whether the api key is set.
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertNotNull('api_key', 'The api key is set');
  }

  /**
   * Test to ensure that the user emotion is not detected when Emotion
   * Detection is disabled.
   */
  public function testNoUserEmotionDetection() {
    // Create an image field for the user picture and get its id.
    $field_id = $this->getImageFieldId('user', 'user');
    // Ensuring that the Emotion Detection feature is disabled.
    $this->drupalGet('admin/config/people/accounts/fields/' . $field_id);
    $username = $this->createUserWithProfilePicture();
    $this->assertNoText('Please upload a photo where you are smiling and happy');
    $this->assertText("Created a new user account for $username. No email has been sent");
  }

  /**
   * Test to ensure that user emotion is detected when Emotion Detection is
   * enabled.
   */
  public function testUserEmotionDetection() {
    // Create an image field for the user picture and get its id.
    $field_id = $this->getImageFieldId('user', 'user');
    // Enabling the Emotion Detection feature.
    $edit = [
      'emotion_detect' => 1,
    ];
    $this->drupalPostForm('admin/config/people/accounts/fields/' . $field_id, $edit, t('Save settings'));
    // Ensuring that the Emotion Detection feature is enabled.
    $this->drupalGet('admin/config/people/accounts/fields/' . $field_id);
    $username = $this->createUserWithProfilePicture();
    $this->assertText('Please upload a photo where you are smiling and happy');
    $this->assertText("Created a new user account for $username. No email has been sent");
  }
}
