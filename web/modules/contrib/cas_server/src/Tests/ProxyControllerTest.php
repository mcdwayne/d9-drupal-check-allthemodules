<?php

namespace Drupal\cas_server\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\cas_server\Entity\CasServerService;

/**
 * Tests responses from the proxy ticket granting system.
 *
 * @group cas_server
 */
class ProxyControllerTest extends WebTestBase {

  public static $modules = ['cas_server'];

  protected function setUp() {
    parent::setUp();

    $this->exampleUser = $this->drupalCreateUser([], 'exampleUserName');
    $this->exampleUser->save();

    $this->ticketFactory = $this->container->get('cas_server.ticket_factory');
    $this->ticketStore = $this->container->get('cas_server.storage');

    $test_proxy = CasServerService::create([
      'id' => 'test_proxy',
      'label' => 'Proxyable Test Service',
      'service' => 'https://foo.example.com*',
      'sso' => TRUE,
      'attributes' => [],
    ]);
    $test_proxy->save();

    $test_no_proxy = CasServerService::create([
      'id' => 'test_no_proxy',
      'label' => 'Unproxyable Test Service',
      'service' => 'https://bar.example.com*',
      'sso' => FALSE,
      'attributes' => [],
    ]);
    $test_no_proxy->save();

  }

  /**
   * Tests an successful request.
   */
  function testProxySuccessRequest() {
    $this->drupalLogin($this->exampleUser);
    $pgt = $this->ticketFactory->createProxyGrantingTicket([]);
    $this->drupalLogout();
    $service = 'https://foo.example.com';

    $this->drupalGet('cas/proxy', ['query' => ['pgt' => $pgt->getId(), 'targetService' => $service]]);
    $this->assertRaw("<cas:proxySuccess>");
    $this->assertRaw("<cas:proxyTicket>");
    $this->assertResponse(200);
  }

  /**
   * Tests an invalid proxy request.
   */
  function testInvalidProxyRequest() {

    $this->drupalGet('cas/proxy');
    $this->assertRaw("<cas:proxyFailure code='INVALID_REQUEST'>");
    $this->assertResponse(200);

  }

  /**
   * Tests an unauthorized service request.
   */
  function testUnauthorizedServiceRequest() {
    $this->drupalLogin($this->exampleUser);
    $pgt = $this->ticketFactory->createProxyGrantingTicket([]);
    $this->drupalLogout();
    $service = 'https://bar.example.com';

    $this->drupalGet('cas/proxy', ['query' => ['pgt' => $pgt->getId(), 'targetService' => $service]]);
    $this->assertRaw("<cas:proxyFailure code='UNAUTHORIZED_SERVICE'>");
    $this->assertResponse(200);
  }

  /**
   * Tests an expired ticket request.
   */
  function testExpiredTicketRequest() {
    $this->config('cas_server.settings')->set('ticket.proxy_granting_ticket_timeout', -20)->save();
    $this->drupalLogin($this->exampleUser);
    $pgt = $this->ticketFactory->createProxyGrantingTicket([]);
    $this->drupalLogout();
    $service = 'https://foo.example.com';

    $this->drupalGet('cas/proxy', ['query' => ['pgt' => $pgt->getId(), 'targetService' => $service]]);
    $this->assertRaw("<cas:proxyFailure code='INVALID_TICKET'>");
    $this->assertResponse(200);

  }

  /**
   * Tests a missing ticket request.
   */
  function testMissingTicketRequest() {
    $this->drupalLogin($this->exampleUser);
    $pgt = $this->ticketFactory->createProxyGrantingTicket([]);
    $this->drupalLogout();
    $this->ticketStore->deleteProxyGrantingTicket($pgt);
    $service = 'https://foo.example.com';

    $this->drupalGet('cas/proxy', ['query' => ['pgt' => $pgt->getId(), 'targetService' => $service]]);
    $this->assertRaw("<cas:proxyFailure code='INVALID_REQUEST'>");
    $this->assertText("Ticket not found");
    $this->assertResponse(200);
    
  }

  /**
   * Tests a incorrect ticket type request.
   */
  function testWrongTicketTypeRequest() {
    $this->drupalLogin($this->exampleUser);
    $pgt = $this->ticketFactory->createTicketGrantingTicket();
    $this->drupalLogout();
    $service = 'https://foo.example.com';

    $this->drupalGet('cas/proxy', ['query' => ['pgt' => $pgt->getId(), 'targetService' => $service]]);
    $this->assertRaw("<cas:proxyFailure code='INVALID_REQUEST'>");
    $this->assertNoText("Ticket not found");
    $this->assertResponse(200);
    
  }
}
