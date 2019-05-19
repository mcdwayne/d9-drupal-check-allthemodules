<?php

namespace Drupal\Tests\tableau_dashboard\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for TableauAPI class.
 *
 * @group tableau_dashboard
 */
class TableauTest extends UnitTestCase {
  private $api;
  private $adminUser;
  private $adminPassword;
  private $siteId;
  private $userId;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Setting up the mock class of the API, which has actually only one method
    // mocked, which is sendReq. Reason for this is that this method is actually
    // sending curl requests to the Tableau server API. Each test defines what
    // sendReq method should return in order to pass the test as defined in the
    // Tableau Server REST API docs:
    // https://onlinehelp.tableau.com/current/api/rest_api/en-us/help.htm#REST/rest_api_ref.htm
    $this->adminUser = "some_username";
    $this->adminPassword = "some_password";
    $this->siteId = "some-site-id";
    $this->userId = "some-user-id";
    $this->api = $this->getMockBuilder('Drupal\tableau_dashboard\TableauAPI')
      ->setConstructorArgs([
        "http://localhost", $this->adminUser, $this->adminPassword, $this->siteId,
      ])
      ->setMethods(['sendReq'])
      ->getMock();
  }

  /**
   * Testing faulty signIn() method.
   *
   * @expectedException Exception
   */
  public function testSignInFail() {
    // If from whatever reason JSON does not contain a token Exception should
    // be raised with a message of "Exception: Token is missing!".
    $sign_in_return = NULL;
    $api = $this->api;
    $sign_in_body = [
      "credentials" => [
        "name" => $this->adminUser,
        "password" => $this->adminPassword,
        "site" => [
          "contentUrl" => "",
        ],
      ],
    ];
    $api->expects($this->once())
      ->method('sendReq')
      ->with('auth/signin', $sign_in_body, "POST")
      ->will($this->returnValue($sign_in_return));

    $this->setExpectedException("Exception", "Token is missing!");
    $api->signIn();
  }

  /**
   * Testing signIn() and signOut() methods.
   */
  public function testSignInOut() {
    // If sign in is successful, Tableau API will return an JSON which sendReq
    // transforms into array and returns it.
    $sign_in_return = [
      'credentials' => [
        'token' => 'this_is_token',
      ],
    ];
    $sign_in_body = [
      "credentials" => [
        "name" => $this->adminUser,
        "password" => $this->adminPassword,
        "site" => [
          "contentUrl" => "",
        ],
      ],
    ];
    $api = $this->api;
    $api->expects($this->any())
      ->method('sendReq')
      ->will($this->returnValueMap([
        ['auth/signin', $sign_in_body, "POST", $sign_in_return],
        ['auth/signout', NULL, "POST", ""],
      ]));
    $this->assertTrue($api->signIn(), "Sign in succesful");
    $this->assertEquals($sign_in_return['credentials']['token'], $api->getToken());

    // Return value of signIn() method is not important for signOut() method.
    // So we are leaving it the same as for signIn().
    $api->signOut();
    $this->assertNull($api->getToken());
  }

  /**
   * Testing addUser() method.
   */
  public function testAddUser() {
    $username = "username";
    $siteRole = "siteRole";
    $body = [
      "user" => [
        "name" => $username,
        "siteRole" => $siteRole,
      ],
    ];
    $api = $this->api;
    $api->expects($this->once())
      ->method('sendReq')
      ->with("sites/$this->siteId/users", $body, "POST")
      ->will($this->returnValue(TRUE));
    $api->addUser($username, $siteRole);
  }

  /**
   * Testing removeUser() method.
   */
  public function testRemoveUser() {
    $api = $this->api;
    $api->expects($this->once())
      ->method('sendReq')
      ->with("sites/$this->siteId/users/$this->userId", NULL, "DELETE")
      ->will($this->returnValue(TRUE));
    $api->removeUser($this->userId);
  }

  /**
   * Testing addUserToGroup() method.
   */
  public function testAddUserToGroup() {
    $groupId = "some-group-id";
    $body = [
      "user" => [
        "id" => $this->userId,
      ],
    ];
    $api = $this->api;
    $api->expects($this->once())
      ->method('sendReq')
      ->with("sites/$this->siteId/groups/$groupId/users", $body, "POST")
      ->will($this->returnValue(TRUE));
    $api->addUserToGroup($this->userId, $groupId);
  }

}
