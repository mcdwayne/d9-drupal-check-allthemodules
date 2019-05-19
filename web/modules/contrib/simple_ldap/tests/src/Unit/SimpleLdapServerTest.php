<?php

/**
 * @file
 *  Contains \Drupal\Tests\simple_ldap\Unit\SimpleLdapServer
 */

namespace Drupal\Tests\simple_ldap\Unit;

use Drupal\simple_ldap\SimpleLdapException;
use Drupal\Tests\UnitTestCase;
use Drupal\simple_ldap\SimpleLdapServer;
use Drupal\Tests\simple_ldap\Unit\SimpleLdapTestBase;

/**
 * @coversDefaultClass \Drupal\simple_ldap\SimpleLdapServer
 * @group simple_ldap
 */
class SimpleLdapServerTest extends SimpleLdapTestBase {

  /**
   * @var \Drupal\simple_ldap\SimpleLdap
   */
  protected $ldap;

  /**
   * @var string
   */
  protected $config_name = 'simple_ldap.server';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->ldap = $this->getMockBuilder('\Drupal\simple_ldap\SimpleLdap')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * @covers ::connect
   */
  public function testConnect() {
    $this->ldap->expects($this->exactly(2))
      ->method('connect');

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $this->assertTrue($server->connect());
  }

  /**
   * @covers ::connect
   */
  public function testConnectFail() {
    $this->ldap->expects($this->exactly(2))
      ->method('connect')
      ->will($this->throwException(new SimpleLdapException('', '')));

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $this->assertFalse($server->connect());
  }

  /**
   * Test a default call of bind().
   *
   * @covers ::bind
   * @depends testConnect
   */
  public function testBind() {
    $this->config->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(array('binddn'), array('bindpw'))
      ->willReturn('test_cred');

    $this->ldap->expects($this->exactly(2))
      ->method('isBound')
      ->will($this->onConsecutiveCalls(FALSE, TRUE));

    $this->ldap->expects($this->once())
      ->method('ldapBind')
      ->willReturn(TRUE);

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $this->assertTrue($server->bind());
  }

  /**
   * Test when credentials are provided in the bind() call.
   *
   * @covers ::bind
   * @depends testConnect
   */
  public function testBindProvided() {
    $this->ldap->expects($this->exactly(2))
      ->method('isBound')
      ->will($this->onConsecutiveCalls(FALSE, TRUE));

    $this->ldap->expects($this->once())
      ->method('ldapBind')
      ->with('testdn', 'testpw')
      ->willReturn(TRUE);

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $server->connect();
    $this->assertTrue($server->bind('testdn', 'testpw'));
  }

  /**
   * @covers ::search
   * @depends testBind
   * @dataProvider searchDataProvider
   */
  public function testSearch($test_results, $scope, $method) {

    $this->setUpSearchTestMocks($test_results);

    $this->ldap->expects($this->once())
      ->method($method)
      ->willReturn('12345678'); // Arbitrary value to simulate an LDAP search identifier resource.

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $server->connect();
    $server->bind();
    $results = $server->search('dc=local', 'cn=ldapuser', $scope);

    $this->assertArrayEquals($test_results, $results);
  }

  /**
   * Most of the Search tests expect the same things.
   *
   * @param $result_array
   */
  protected function setUpSearchTestMocks($result_array) {
    $this->ldap->expects($this->any())
      ->method('isBound')
      ->willReturn(TRUE);

    $this->ldap->expects($this->once())
      ->method('getEntries')
      ->willReturn($result_array);

    $this->ldap->expects($this->once())
      ->method('freeResult')
      ->willReturn(TRUE);

    $this->ldap->expects($this->once())
      ->method('clean')
      ->willReturn($result_array);
  }

  public function searchDataProvider() {
    return array(
      array(
        array('returned_value_1', 'returned_value_2'), 'base', 'ldapRead'
      ),
      array(
        array('returned_value_1', 'returned_value_2'), 'one', 'ldapList'
      ),
      array(
        array('returned_value_1', 'returned_value_2'), 'sub', 'ldapSearch'
      ),
    );
  }

  /**
   * @covers ::getPageSize
   * @covers ::setRootDse
   * @depends testSearch
   */
  public function testSetPageSize() {

    $test_results = array(
      '' => array('supportedcontrol' => array('1.2.840.113556.1.4.319', 'returned_value_2')),
      'count' => 1,
      0 => 'test_result',
    );

    $this->config->expects($this->once())
      ->method('get')
      ->with('pagesize')
      ->willReturn(10);

    $this->setUpSearchTestMocks($test_results);

    $this->ldap->expects($this->once())
      ->method('ldapRead')
      ->willReturn('12345678'); // Arbitrary value to simulate an LDAP search identifier resource.

    $this->ldap->expects($this->once())
      ->method('controlPageResultResponse')
      ->with('12345678', '');

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $server->connect();
    $server->bind();
    $result = $server->getPageSize();

    $this->assertEquals(10, $result);
  }

  /**
   * @covers ::getPageSize
   * @covers ::setRootDse
   * @depends testSearch
   */
  public function testSetPageSizeUnavailable() {
    $test_results = array(
      '' => array('supportedcontrol' => array('1.2.840.113556.1.4.xxxx', 'returned_value_2')),
      'count' => 1,
      0 => 'test_result',
    );

    $this->config->expects($this->once())
      ->method('get')
      ->with('pagesize')
      ->willReturn(10);

    $this->setUpSearchTestMocks($test_results);

    $this->ldap->expects($this->once())
      ->method('ldapRead')
      ->willReturn('12345678'); // Arbitrary value to simulate an LDAP search identifier resource.

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $server->connect();
    $server->bind();
    $result = $server->getPageSize();

    $this->assertFalse($result);
  }

  /**
   * @dataProvider serverTypeDataProvider
   */
  public function testGetServerType($expected, $rootdse_results) {
    $this->setUpSearchTestMocks($rootdse_results);

    $this->ldap->expects($this->once())
      ->method('ldapRead')
      ->willReturn('12345678'); // Arbitrary value to simulate an LDAP search identifier resource.

    $server = new SimpleLdapServer($this->config_factory, $this->ldap);
    $server->connect();
    $server->bind();
    $type = $server->getServerType();
  }

  public function serverTypeDataProvider() {
    return array(
      array(
        'Active Directory',
        array('' => array('rootdomainnamingcontext' => 'is_active_directory'))
      ),
      array(
        'OpenLDAP',
        array('' => array('objectclass' => array('OpenLDAProotDSE', 'Another record')))
      ),
      array(
        'LDAP',
        array('' => array('nothing' => 'is_default_ldap'))
      )
    );
  }
}
