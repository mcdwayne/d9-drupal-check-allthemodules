<?php
/**
 * @file
 *  Contains \Drupal\Tests\simple_ldap\Unit\SimpleLdapUserManagerTest
 */

namespace Drupal\Tests\simple_ldap\Unit;

use Drupal\simple_ldap\SimpleLdap;
use Drupal\simple_ldap\SimpleLdapException;
use Drupal\Tests\UnitTestCase;
use Drupal\simple_ldap\SimpleLdapServer;
use Drupal\simple_ldap_user\SimpleLdapUserManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Tests\simple_ldap\Unit\SimpleLdapTestBase;
use Drupal\simple_ldap_user\SimpleLdapUser;
use Drupal\user\UserStorage;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * @coversDefaultClass \Drupal\simple_ldap_user\SimpleLdapUserManager
 * @group simple_ldap
 */
class SimpleLdapUserManagerTest extends SimpleLdapTestBase {

  /**
   * @var SimpleLdapServer
   */
  protected $server;

  /**
   * @var QueryFactory
   */
  protected $query_factory;

  /**
   * @var EntityTypeManager
   */
  protected $entity_manager;

  /**
   * @var string
   */
  protected $config_name = 'simple_ldap.user';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->server = $this->getMockBuilder('\Drupal\simple_ldap\SimpleLdapServer')
      ->disableOriginalConstructor()
      ->getMock();

    $this->query_factory = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->entity_manager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * @covers ::getLdapUser
   */
  public function testGetLdapUser() {

    $this->setUpGetLdapUserTests();

    $this->server->expects($this->exactly(2))
      ->method('search')
      ->will($this->onConsecutiveCalls(
        array('dn="johnsmith"' => array('cn' => array('johnsmith'), 'mail' => array('john@example.com')), 'dn="johnAsmith"' => array('cn' => array('johnAsmith'), 'mail' => array('johnA@example.com'))),
        array('dn="johnsmith"' => array('cn' => array('johnsmith'), 'mail' => array('john@example.com')))
      ));

    $user_manager = new SimpleLdapUserManager($this->server, $this->config_factory, $this->query_factory, $this->entity_manager);
    $user = $user_manager->getLdapUser('johnsmith');
    $this->assertInstanceOf(SimpleLdapUser::class, $user);

    return $user;
  }

  /**
   * @covers ::getLdapUser
   */
  public function testGetLdapUserNotFound() {

    $this->setUpGetLdapUserTests();

    $this->server->expects($this->exactly(2))
      ->method('search')
      ->willReturn(array());

    $user_manager = new SimpleLdapUserManager($this->server, $this->config_factory, $this->query_factory, $this->entity_manager);
    $user = $user_manager->getLdapUser('johnsmith');
    $this->assertFalse($user);
  }

  /**
   * @covers ::loadDrupalUser
   * @depends testGetLdapUser
   */
  public function testLoadDrupalUser(SimpleLdapUser $ldap_user) {
    $attributes = $ldap_user->getAttributes();
    $this->setUpDrupalUserTests();

    $query = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->exactly(2))
      ->method('condition')
      ->willReturn($query);
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(array(5 => 5));

    $this->query_factory->expects($this->once())
      ->method('get')
      ->willReturn($query);

    $user_storage = $this->getMockBuilder('\Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $user_storage->expects($this->once())
      ->method('load')
      ->with(5)
      ->willReturn(TRUE);

    $this->entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $user_manager = new SimpleLdapUserManager($this->server, $this->config_factory, $this->query_factory, $this->entity_manager);
    $user = $user_manager->loadDrupalUser($ldap_user);
    $this->assertTrue(TRUE);

  }

  /**
   * @covers ::createDrupalUser
   * @depends testGetLdapUser
   */
  public function testCreateDrupalUser(SimpleLdapUser $ldap_user) {
    $attributes = $ldap_user->getAttributes();

    $this->config->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(
        ['name_attribute'],
        ['mail_attribute']
      )
      ->will($this->onConsecutiveCalls(
        'cn',
        'mail'
      ));

    $user_storage = $this->getMockBuilder('\Drupal\user\UserStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $user = $this->getMockBuilder('\Drupal\user\User')
      ->disableOriginalConstructor()
      ->setMethods(array('enforceIsNew', 'activate', 'save'))
      ->getMock();
    $user->expects($this->once())
      ->method('enforceIsNew');
    $user->expects($this->once())
      ->method('activate');
    $user->expects($this->once())
      ->method('save');

    $user_storage->expects($this->once())
      ->method('create')
      ->with(array(
        'name' => $attributes['cn'][0],
        'mail' => $attributes['mail'][0],
      ))
      ->willReturn($user);

    $this->entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('user')
      ->willReturn($user_storage);

    $user_manager = new SimpleLdapUserManager($this->server, $this->config_factory, $this->query_factory, $this->entity_manager);
    $user = $user_manager->createDrupalUser($ldap_user);
    $this->assertNotEmpty($user);
  }

  /**
   * Helper method for testCreateDrupalUser() and testLoadDrupalUser().
   */
  protected function setUpDrupalUserTests() {
    $this->config->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(
        ['name_attribute'],
        ['mail_attribute']
      )
      ->will($this->onConsecutiveCalls(
        'cn',
        'mail'
      ));
  }

  /**
   * Helper method for getLdapUser tests.
   */
  protected function setUpGetLdapUserTests() {
    $this->config->expects($this->exactly(5))
      ->method('get')
      ->withConsecutive(
        ['name_attribute'],
        ['mail_attribute'],
        ['basedn'],
        ['user_scope'],
        ['object_class']
      )
      ->will($this->onConsecutiveCalls(
        'cn',
        'mail',
        'dc=local',
        'sub',
        array('inetOrgPerson', 'person')
      ));

    $this->server->expects($this->once())
      ->method('bind')
      ->willReturn(TRUE);
  }
}
