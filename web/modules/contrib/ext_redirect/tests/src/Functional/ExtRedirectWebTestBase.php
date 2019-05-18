<?php
/**
 * Created by PhpStorm.
 * User: marek.kisiel
 * Date: 22/08/2017
 * Time: 11:00
 */

namespace Drupal\Tests\ext_redirect\Functional;

use Drupal\simpletest\WebTestBase;

abstract class ExtRedirectWebTestBase extends WebTestBase {

  public static $modules = ['ext_redirect', 'system', 'user'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  private $adminPermissions = ['access administration pages', 'administer site configuration', 'manage redirect rule entities'];

  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser($this->adminPermissions);
  }


}