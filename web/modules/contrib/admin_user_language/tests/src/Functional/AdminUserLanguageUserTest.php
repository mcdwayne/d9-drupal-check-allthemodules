<?php

namespace Drupal\Tests\admin_user_language\Functional;

/**
 * Tests entity presave hook to check admin_user_language functionality.
 *
 * @group admin_user_language
 */
class AdminUserLanguageUserTest extends AdminUserLanguageBrowserTestBase {

  /**
   * Test if an admin language is correctly set
   * on user registration or edit.
   */
  public function testCoreFunctionalityOnUserRegistrationAndEdit() {
    $activeLanguages = $this->getActiveLanguages();
    $randomLanguage = array_rand($activeLanguages);

    $userAccount = $this->getuCurrentUserAccount();

    // 1 - Checking that the current user has no preferred administration language.
    $adminUserLang = $this->getAdminUserLang($userAccount->id());

    self::assertEquals($adminUserLang, [], 'User has no preferred admin language.');

    // 2 - Enabling the preferred admin language in the module.
    $this->setUserAdminPreferredLang($randomLanguage, TRUE);

    // Making a change in the User profile to trigger the presave hook and expecting
    // the user to have the preferred language to the default one
    $user = $this->getDrupalUser($userAccount->id());
    $user->set('name', mt_rand());
    $user->save();

    $adminUserLang = $this->getAdminUserLang($this->getuCurrentUserAccount()
                                                  ->id());
    self::assertEquals($adminUserLang, [['value' => $randomLanguage]], 'User has the preferred admin language set to ' . $randomLanguage . '.');

    // 3 - Changing the user preferred language to none and deactivating the prevent_user_override should result in a successful save
    $this->setUserAdminPreferredLang($randomLanguage, FALSE);

    $user = $this->getDrupalUser($userAccount->id());
    $user->set('preferred_admin_langcode', FALSE);
    $user->save();

    $adminUserLang = $this->getAdminUserLang($this->getuCurrentUserAccount()
                                                  ->id());
    self::assertEquals($adminUserLang, [['value' => '']], 'User has the preferred admin language set to "none".');

    // 4 - Creating a new user with the module enabled, this should force by default a preferred admin language
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    $adminUserLang = $this->getAdminUserLang($admin_user->id());

    self::assertEquals($adminUserLang, [['value' => $randomLanguage]], 'The newly created user has the preferred admin language set to ' . $randomLanguage . '.');

    // 5 - Creating another user by selecting no default language in the module settings
    $this->setUserAdminPreferredLang('-1', FALSE);
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    $adminUserLang = $this->getAdminUserLang($admin_user->id());

    self::assertEquals($adminUserLang, [], 'The newly created user has the preferred admin language set to "none".');
  }

  /**
   * @param string $langCode
   * @param bool $preventOverride
   */
  protected function setUserAdminPreferredLang($langCode, $preventOverride = FALSE) {
    // Overriding configuration
    $config = \Drupal::configFactory()
                     ->getEditable('admin_user_language.settings');
    $config->set('default_language_to_assign', $langCode)
           ->set('prevent_user_override', $preventOverride)
           ->save();
  }

  /**
   * @return \Drupal\Core\Session\AccountInterface|\Drupal\Core\Session\AnonymousUserSession
   */
  private function getuCurrentUserAccount() {
    return $this->container->get('current_user')->getAccount();
  }

  /**
   * @param $uid
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  private function getDrupalUser($uid) {
    return \Drupal::entityTypeManager()->getStorage('user')->load($uid);
  }

  /**
   * @param $uid
   * @return mixed
   */
  private function getAdminUserLang($uid) {
    $user = $this->getDrupalUser($uid);
    return $user->get('preferred_admin_langcode')->getValue();
  }

}
