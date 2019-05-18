<?php

namespace Drupal\passwd_only\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class for all tests.
 */
abstract class PasswdOnlyWebTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['passwd_only'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * A administration user with the permissions 'admin passwd only'.
   *
   * @var object
   */
  protected $userAdminPasswdOnly;

  /**
   * A user with the permissions 'user passwd only'.
   *
   * @var object
   */
  protected $userUserPasswdOnly;

  /**
   * A normal authenticated user without special permissions.
   *
   * @var object
   */
  protected $userAuthenticated;

  /**
   * A user which can be configured as a password only login account.
   *
   * @var object
   */
  protected $userPasswdOnly;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->userAdminPasswdOnly = $this->drupalCreateUser([
      'access administration pages',
      'admin passwd only',
    ]);
    $this->userUserPasswdOnly = $this->drupalCreateUser([
      'use passwd only',
    ]);
    $this->userAuthenticated = $this->drupalCreateUser();
    $this->userPasswdOnly = $this->drupalCreateUser();
  }

  /**
   * Configure the module using the web pages.
   */
  protected function configureModule() {
    $this->drupalLogin($this->userAdminPasswdOnly);
    $edit = [
      'user' => $this->userPasswdOnly->getUsername(),
      'description' => 'Some description text.',
    ];
    $this->drupalPostForm('admin/config/system/passwd-only', $edit, t('Save'));
  }

}
