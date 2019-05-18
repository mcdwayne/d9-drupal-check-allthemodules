<?php
/**
 * @file
 * Contains \Drupal\Tests\mailmute\Kernel\MuteUserTest.
 */

namespace Drupal\Tests\mailmute\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests send states for the user entity.
 *
 * @group mailmute
 */
class MuteUserTest extends MailmuteKernelTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('field', 'mailmute', 'user', 'system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installConfig(['mailmute', 'system']);
    \Drupal::configFactory()->getEditable('system.site')
      ->set('mail', 'admin@example.com')
      ->save();
  }

  /**
   * Tests send states for the user entity.
   */
  public function testStates() {
    // A Send state field should be added to User on install.
    $field_map = \Drupal::entityManager()->getFieldMap();
    $this->assertEqual($field_map['user']['sendstate']['type'], 'sendstate');

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->createUser();

    // Default plugin_id should be send.
    $this->assertEqual($user->sendstate->plugin_id, 'send');

    // Mails should be sent normally.
    $sent = $this->mail($user);
    $this->assertTrue($sent);

    // When plugin_id is onhold, mails should not be sent.
    $user->sendstate->plugin_id = 'onhold';
    $user->save();
    $sent = $this->mail($user);
    $this->assertFalse($sent);
  }

  /**
   * Tests the send state manager methods and the service mechanism.
   */
  public function testSendStateManager() {
    /** @var \Drupal\mailmute\SendStateManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.sendstate');
    $this->assertNotNull($manager, 'Send state manager is loaded');

    // Create a new user and assert default state.
    $user = $this->createUser();

    $this->assertEqual($manager->getState($user->getEmail())->getPluginId(), 'send');
    $this->assertFalse($manager->getState($user->getEmail())->isMute());

    // Set state to On Hold.
    $manager->transition($user->getEmail(), 'onhold');

    $this->assertEqual($manager->getState($user->getEmail())->getPluginId(), 'onhold');
    $this->assertTrue($manager->getState($user->getEmail())->isMute());

    // Reload the user and assert that field persists.
    $user = User::load($user->id());

    $this->assertEqual($manager->getState($user->getEmail())->getPluginId(), 'onhold');
    $this->assertTrue($manager->getState($user->getEmail())->isMute());

    // Set a state attribute (plugin configuration) by getState() and save().
    $configuration = array($this->randomMachineName() => $this->randomMachineName());
    $state = $manager->getState($user->getEmail());
    $state->setConfiguration($configuration);
    $manager->save($user->getEmail());

    $this->assertEqual($manager->getState($user->getEmail())->getConfiguration(), $configuration);
    $user = User::load($user->id());
    $this->assertEqual($user->sendstate->configuration, $configuration);

    // Set a state attribute (plugin configuration) by transition().
    $configuration = array($this->randomMachineName() => $this->randomMachineName());
    $manager->transition($user->getEmail(), 'send', $configuration);

    $this->assertEqual($manager->getState($user->getEmail())->getConfiguration(), $configuration);
    $user = User::load($user->id());
    $this->assertEqual($user->sendstate->configuration, $configuration);
  }

  /**
   * Tests that no suppressing is made for non-managed addresses.
   */
  public function testNonManagedAddress() {
    /** @var \Drupal\mailmute\SendStateManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.sendstate');

    // Create user only to pass into the mail mechanism. But don't save because
    // then the send state manager will be aware of it, and the point is that it
    // shouldn't.
    /** @var \Drupal\user\UserInterface $user */
    $user = User::create(array(
      'name' => 'stranger',
      'mail' => 'stranger@example.com',
    ));

    // isManaged() should return false.
    $this->assertFalse($manager->isManaged('stranger@example.com'));

    // Sending should not be suppressed.
    $sent = $this->mail($user);
    $this->assertTrue($sent);
  }

  /**
   * Attempts to send a Password reset mail, and indicates success.
   *
   * @param \Drupal\user\UserInterface $user
   *   User object to send email to.
   *
   * @return bool
   *   The result status.
   */
  protected function mail(UserInterface $user) {
    $params = array('account' => $user);
    $message = $this->mailManager->mail('user', 'password_reset', $user->getEmail(), LanguageInterface::LANGCODE_DEFAULT, $params);
    return $message['result'];
  }

  /**
   * Creates a user with a random name and email address.
   *
   * @return \Drupal\user\UserInterface
   *   The created user.
   */
  protected function createUser() {
    $name = $this->randomMachineName();
    $user = User::create(array(
      'name' => $name,
      'mail' => "$name@example.com",
    ));
    $user->save();
    return $user;
  }

}
