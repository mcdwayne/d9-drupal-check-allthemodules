<?php

namespace Drupal\cas_server\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\cas_server\Entity\CasServerService;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;

/**
 * Tests responses from the user action controller.
 *
 * @group cas_server
 */
class UserActionControllerTest extends WebTestBase {

  public static $modules = ['cas_server'];

  protected function setUp() {
    parent::setUp();

    $this->exampleUser = $this->drupalCreateUser([], 'exampleUserName');
    $this->exampleUser->save();

    $this->ticketFactory = $this->container->get('cas_server.ticket_factory');
    $this->ticketStore = $this->container->get('cas_server.storage');
    $this->connection = $this->container->get('database');

    $test = CasServerService::create([
      'id' => 'test',
      'label' => 'Test Service',
      'service' => 'https://foo.example.com*',
      'sso' => TRUE,
      'attributes' => [],
    ]);
    $test->save();
  }

  /**
   * Test the logout path.
   */
  public function testLogout() {
    $this->drupalLogin($this->exampleUser);
    $session_id = $this->sessionId;
    $this->curlCookies[] = 'cas_tgc=foo';

    // Proxy tickets take session ids in the constructor, so use those to test
    $this->ticketFactory->createProxyTicket('foo', FALSE, [], $session_id, 0, 'bar');
    $this->ticketFactory->createProxyTicket('baz', FALSE, [], $session_id, 0, 'quux');

    $this->drupalGet('cas/logout');
    $this->assertFalse($this->drupalUserIsLoggedIn($this->exampleUser));
    $this->assertText('You have been logged out');
    $this->assertEqual($this->cookies['cas_tgc']['value'], 'deleted');

    $tickets = $this->connection->select('cas_server_ticket_store', 'c')
      ->fields('c', array('id'))
      ->condition('session', Crypt::hashBase64($session_id))
      ->execute()
      ->fetchAll();

    $this->assertTrue(empty($tickets));

  }

  /**
   * Test redirecting if presented with a service ticket.
   */
  public function testLoginRedirect() {
    // This test sets the service to our own internal service url and checks to
    // see if we get redirected there.
    $test_all = CasServerService::create([
      'id' => 'test_all',
      'label' => 'Allow All Test Services',
      'service' => '*',
      'sso' => FALSE,
      'attributes' => [],
    ]);
    $test_all->save();
    $service = Url::fromRoute('cas_server.validate1');
    $service->setAbsolute();
    $this->drupalGet('cas/login', ['query' => ['service' => $service->toString(), 'ticket' => 'foo']]);
    $this->assertUrl('cas/validate', ['query' => ['ticket' => 'foo']]); 
    $this->assertEqual($this->redirectCount, 1);
  }

  /**
   * Check invalid service message.
   */
  public function testInvalidServiceMessage() {
    $this->drupalGet('cas/login', ['query' => ['service' => 'https://bar.example.com']]);
    $this->assertText('You have not requested a valid service');
    $this->assertResponse(200);
  }

  /**
   * Test already logged in message.
   */
  public function testAlreadyLoggedIn() {
    $this->drupalLogin($this->exampleUser);
    $this->drupalGet('cas/login');
    $this->assertResponse(200);
    $this->assertText('You are logged in to CAS single sign on');
  }

  /**
   * Test receiving form with no service and not logged in.
   */
  public function testNoServiceNoSession() {
    $this->drupalGet('cas/login');
    $this->assertResponse(200);
    $this->assertFieldByName('username');
    $this->assertFieldByName('password');
    $this->assertFieldByName('lt');
  }

  /**
   * Test receiving form with a service and not logged in.
   */
  public function testServiceNoSession() {
    $this->drupalGet('cas/login', ['query' => ['service' => 'https://foo.example.com']]);
    $this->assertResponse(200);
    $this->assertFieldByName('username');
    $this->assertFieldByName('password');
    $this->assertFieldByName('lt');
    $this->assertFieldByName('service', 'https://foo.example.com');
  }

  /**
   * Test gateway pass-through with no session.
   */
  public function testGatewayPassThrough() {
    $test_all = CasServerService::create([
      'id' => 'test_all',
      'label' => 'Allow All Test Services',
      'service' => '*',
      'sso' => FALSE,
      'attributes' => [],
    ]);
    $test_all->save();
    $service = Url::fromRoute('cas_server.validate1');
    $service->setAbsolute();
    $this->drupalGet('cas/login', ['query' => ['service' => $service->toString(), 'gateway' => 'true']]);
    $this->assertUrl('cas/validate', []); 
    $this->assertEqual($this->redirectCount, 1);
  }

  /**
   * Test single sign on redirect.
   */
  public function testSingleSignOn() {
    $test_all = CasServerService::create([
      'id' => 'test_all',
      'label' => 'Allow All Test Services',
      'service' => '*',
      'sso' => TRUE,
      'attributes' => [],
    ]);
    $test_all->save();
    $this->drupalLogin($this->exampleUser);
    $session_id = $this->sessionId;
    $service = Url::fromRoute('cas_server.validate1');
    $service->setAbsolute();
    $this->drupalGet('cas/login', ['query' => ['service' => $service->toString()]]);
    $this->assertEqual($this->redirectCount, 1);
    
    $ticket = $this->connection->select('cas_server_ticket_store', 'c')
      ->fields('c', array('id'))
      ->condition('session', Crypt::hashBase64($session_id))
      ->condition('type', 'service')
      ->execute()
      ->fetch();
    $tid = $ticket->id;

    $this->assertUrl('cas/validate', ['query' => ['ticket' => $tid]]);
  }


}
