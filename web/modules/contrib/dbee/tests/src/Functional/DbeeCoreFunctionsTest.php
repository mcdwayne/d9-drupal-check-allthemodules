<?php

namespace Drupal\Tests\dbee\Functional;

/**
 * Check : loading user by id, by email, loading current user, saving user.
 *
 * Verify if the user_save(), user_load(), user_load_by_mail() core functions
 * are not altered by the dbee module.
 *
 * @group dbee
 */
class DbeeCoreFunctionsTest extends DbeeWebSwitchTestBase {

  /**
   * User to load.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userToLoad;

  /**
   * User save user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userSaveUser;

  /**
   * Connected user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $connectedUser;

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   */
  public function setUp() {
    // Enable any modules required for the test.
    parent::setUp();
    $this->userToLoad = $this->drupalCreateUser();
    // drupalCreateUser() set an empty 'init' value. Fix it.
    $this->userToLoad->set('init', $this->randomMachineName() . '@eXample.com');
    $this->userToLoad->save();

    // Create a user who can enable the dbee module.
    $this->adminModulesAccount = $this->drupalCreateUser(['administer modules']);
  }

  /**
   * Test Drupal core functions with emails.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testCoreFunctions() {
    // Test the user_save(), user_load(), user_load_by_mail() core functions.
    $this->drupalLogin($this->userToLoad);

    $mail_to_load = $this->userToLoad->getEmail();
    // Store a loaded user from mail without the dbee module.
    // Current user.
    $connected_user_mail_original = $this->container->get('current_user')->getEmail();

    // Install dbee module.
    $this->dbeeEnablingDisablingDbeeModule(TRUE);
    // Current user.
    $this->drupalLogout();
    $this->drupalLogin($this->userToLoad);

    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = $this->container->get('entity_type.manager')
      ->getStorage('user');
    // Test the user_load().
    /** @var \Drupal\user\UserInterface $user_load_dbee */
    $user_load_dbee = $user_storage->load($this->userToLoad->id());
    $this->assertEquals($user_load_dbee->getEmail(), $mail_to_load, 'On loading a user, its email is avalaible.');

    // Test the user_load_by_mail().
    /** @var \Drupal\user\UserInterface[] $user_load_by_mail_dbee */
    $user_load_by_mail_dbee = $user_storage->loadByProperties(['mail' => $mail_to_load]);
    $mail_to_load_dbee = (!empty($user_load_by_mail_dbee)) ? reset($user_load_by_mail_dbee)->getEmail() : FALSE;
    $this->assertEquals($mail_to_load_dbee, $mail_to_load, 'the user_load_by_mail() fonction is not altered by the dbee module');

    $connected_user_mail_dbee = $this->container->get('current_user')->getEmail();
    $this->assertEquals($connected_user_mail_dbee, $connected_user_mail_original, 'the connected user is not altered by the dbee module');

    // Save user.
    $user_load_dbee->save();
    $saved_mail = (!empty($user_load_dbee)) ? $user_load_dbee->getEmail() : FALSE;
    $this->assertEquals($saved_mail, $mail_to_load, 'After saving a user : its email is available.');
  }

}
