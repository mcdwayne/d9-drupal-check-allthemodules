<?php

namespace Drupal\Tests\dbee\Functional;

use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Base class for the dbee modules tests.
 *
 * Method to easily install/unistall dbee module, create users.
 */
abstract class DbeeWebSwitchTestBase extends DbeeWebTestBase {

  /**
   * User with permission to enable/uninstall modules.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminModulesAccount;

  /**
   * Number of basic user to create.
   *
   * @var array
   */
  protected $nUsers = 10;

  /**
   * Array keyed by uid providing users mail and int original values.
   *
   * @var array
   */
  protected $usersInfo = [];

  /**
   * Number of users stored in db.
   *
   * @var int
   */
  protected $totalUsers = 0;

  /**
   * Number of users stored in db that should be encrypted (valid email).
   *
   * @var int
   */
  protected $nUpdatedUsers = 0;

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // Create a user who can enable the dbee module.
    $this->adminModulesAccount = $this->drupalCreateUser(['administer modules']);

    // Create many users.
    $this->dbeeCreateManyUsers();
  }

  /**
   * Enable or disable dbee module.
   *
   * @param bool $enabling
   *   Enable module, not - otherwise.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function dbeeEnablingDisablingDbeeModule($enabling) {

    $logged_user_uid = ($this->loggedInUser) ? $this->loggedInUser->id() : 0;
    if (!$logged_user_uid || $logged_user_uid != $this->adminModulesAccount->id()) {
      $this->drupalLogin($this->adminModulesAccount);
      if ($enabling && empty($this->totalUsers)) {
        // Set the count of users.
        $this->dbeeGetUsersInfo();
      }
    }

    $uri = ($enabling) ? 'admin/modules' : 'admin/modules/uninstall';
    $selector = ($enabling) ? 'modules[dbee][enable]' : 'uninstall[dbee]';
    $edit0 = [$selector => 'dbee'];
    $submit_text = ($enabling) ? 'Install' : 'Uninstall';
    $confirm_page_route = ($enabling) ? 'system.modules_list_confirm' : 'system.modules_uninstall_confirm';
    $confirm_submit_text = ($enabling) ? 'Continue' : 'Uninstall';
    $success_text = ($enabling) ? 'been enabled' : 'The selected modules have been uninstalled.';
    $this->drupalPostForm($uri, $edit0, $submit_text);
    // Check if dependencies needs to be loaded.
    if ($this->getUrl() == Url::fromRoute($confirm_page_route)->setAbsolute()->toString()) {
      $this->drupalPostForm(NULL, [], $confirm_submit_text);
    }
    $arg_enabled = ($enabling) ? 'enabled' : 'disabled';
    $session = $this->assertSession();
    // Assert is module has correct state.
    $session->pageTextContains($success_text);
    if ($enabling) {
      $this->rebuildContainer();
      $module_enabled = $this->container->get('module_handler')->moduleExists('dbee');
      $this->assertTrue(($module_enabled == $enabling), "dbee module is {$arg_enabled} on container.");
    }
    if (!$enabling && empty($this->totalUsers)) {
      // Set the count of users.
      $this->dbeeGetUsersInfo();
    }

    $crypted_state = ($enabling) ? 'encrypted' : 'decrypted';
    // The dbee module claims to have {$crypted_state} all users
    // ({$this->nUpdatedUsers} of {$this->totalUsers} users)
    $session->pageTextContains("All users email addresses have been {$crypted_state} (concerning {$this->nUpdatedUsers} of {$this->totalUsers} users)");
    // Test all email address.
    if (!empty($this->usersInfo)) {
      $test_message = ($enabling) ? 'All users are encrypted and can be decrypted back' : 'All users are uncrypted and valid';
      $this->assertTrue($this->dbeeAllUsersValid($this->usersInfo, $enabling), $test_message);
    }
  }

  /**
   * Create many users with warious values of mail and init properties.
   *
   * Create as $this->nUsers users. it recommanded to create at least 10 users
   * to have all possibles kind of values of mail and init. In order to create
   * thoses users, simply set the $this->nUsers into the class that extends the
   * DbeeWebTestBase class and call this method from the setUp. This function
   * will set $this->usersInfo, $this->totalUsers, $this->nUpdatedUsers,
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function dbeeCreateManyUsers() {
    // First create 10 users with variant email addresses : sensitive case,
    // lowercase, empty and invalid.
    $n_created = 0;
    $provider = '@eXample.com';
    for ($i = 1; $i <= $this->nUsers; $i++) {
      // Create users, sensitive case and lower case.
      $edit = [];
      if (isset($account)) {
        unset($account);
      }
      $edit['name'] = $this->randomMachineName();
      $edit['mail'] = $edit['name'] . $provider;

      // 3 sensitive case email addresses (5 - 2).
      if (ceil($i / 2) == $i / 2) {
        // 4 lowercase email addresses (5-1).
        $edit['mail'] = mb_strtolower($edit['mail']);
      }

      if (ceil($i / 9) == $i / 9) {
        // 1 empty email addresses.
        $edit['mail'] = '';
      }
      if (ceil($i / 5) == $i / 5) {
        // 2 invalid email address.
        $edit['mail'] = 'This-is-an-invalid-email-' . $this->randomMachineName();
        // The second one is lower case.
        if ($i > 5) {
          $edit['mail'] = mb_strtolower($edit['mail']);
        }
      }

      $edit['pass'] = user_password();
      $edit['status'] = 1;
      $edit['init'] = $edit['mail'];

      $account = User::create($edit);
      $account->save();
      $account_uid = (!empty($account->id()));
      $this->assertTrue($account_uid, "User creation succeed for email {$edit['mail']}");
      if ($account_uid) {
        $n_created++;
        $this->usersInfo[$account_uid]['mail'] = $edit['mail'];
        $this->usersInfo[$account_uid]['init'] = $edit['init'];
      }
    }

    if ($n_created > 0) {
      $this->assertTrue($n_created == $this->nUsers, "{$this->nUsers} users have been created successfully");
    }
  }

  /**
   * Store infos for all users.
   */
  protected function dbeeGetUsersInfo() {
    $total_users = $no_update_user = 0;
    if (!function_exists('dbee_email_to_alter')) {
      module_load_include('module', 'dbee');
    }
    $all_users = dbee_stored_users();
    foreach ($all_users as $uid => $values) {
      $total_users++;
      $update = FALSE;
      foreach (['mail', 'init'] as $field) {
        $value = (isset($values[$field])) ? $values[$field] : '';
        if ($value && dbee_email_to_alter($value)) {
          // This si a valid email address.
          $update = TRUE;
        }
        // @TODO check if the dbee module is not enabled. In the meantime, call
        // this method only if the dbee module is not enabled.
        $this->usersInfo[$uid][$field] = $value;

      }
      if (!$update) {
        $no_update_user++;
      }
    }
    $updated_users = $total_users - $no_update_user;

    // Set the numbers of total and updated users.
    $this->totalUsers = $total_users;
    $this->nUpdatedUsers = $updated_users;
  }

}
