<?php

namespace Drupal\cas_server\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests responses from the ticket validation system.
 *
 * @group cas_server
 */
class TicketValidationTest extends WebTestBase {

  public static $modules = ['cas_server'];

  protected function setUp() {
    parent::setUp();

    $this->exampleUser = $this->drupalCreateUser([], 'exampleUserName');
    $this->exampleUser->save();

    $this->ticketFactory = $this->container->get('cas_server.ticket_factory');
    $this->ticketStore = $this->container->get('cas_server.storage');

  }

  /**
   * Test failure with an invalid Pgt callback url.
   */
  function testInvalidPgtCallback() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', 6000)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', 6000)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';
    $mangled_pgt_callback = 'h;ad;;//asdcx.otcz';
    
    // Protocol version 2
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/serviceValidate', ['query' => ['pgtUrl' => $mangled_pgt_callback, 'service' => $service, 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationFailure code="INVALID_PROXY_CALLBACK">');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/p3/serviceValidate', ['query' => ['pgtUrl' => $mangled_pgt_callback, 'service' => $service, 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationFailure code="INVALID_PROXY_CALLBACK">');
    $this->assertResponse(200);
}

  /**
   * Test failure when renew is set but ticket doesn't comply.
   */
  function testRenewMismatch() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', 6000)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', 6000)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';

    // Protocol version 1
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/validate', ['query' => ['renew' => 'true', 'service' => $service, 'ticket' => $st->getId()]]);
    $this->assertText('no');
    $this->assertNoRaw('html');
    $this->assertResponse(200);
    
    // Protocol version 2
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/serviceValidate', ['query' => ['renew' => 'true', 'service' => $service, 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET">');
    $this->assertText('renew');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/p3/serviceValidate', ['query' => ['renew' => 'true', 'service' => $service, 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET">');
    $this->assertText('renew');
    $this->assertResponse(200);
}


  /**
   * Test failure when service doesn't match.
   */
  function testServiceMismatch() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', 6000)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', 6000)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';

    // Protocol version 1
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/validate', ['query' => ['service' => $service . 'adfasd', 'ticket' => $st->getId()]]);
    $this->assertText('no');
    $this->assertNoRaw('html');
    $this->assertResponse(200);
    
    // Protocol version 2
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/serviceValidate', ['query' => ['service' => $service . 'adasdf', 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationFailure code="INVALID_SERVICE">');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/p3/serviceValidate', ['query' => ['service' => $service . 'adfasdf', 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationFailure code="INVALID_SERVICE">');
    $this->assertResponse(200);
}

  /**
   * Test failure when ticket is expired.
   */
  function testExpiredTicket() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', -20)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', -20)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';

    // Protocol version 1
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/validate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);
    $this->assertText('no');
    $this->assertNoRaw('html');
    $this->assertResponse(200);
    
    // Protocol version 2
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET">');
    $this->assertText('expired');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/p3/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET">');
    $this->assertText('expired');
    $this->assertResponse(200);
}

  /**
   * Test failure when ticket is missing from ticket store.
   */
  function testMissingTicket() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', 6000)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', 6000)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';

    // Protocol version 1
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->ticketStore->deleteServiceTicket($st);
    $this->drupalGet('cas/validate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);
    $this->assertText('no');
    $this->assertNoRaw('html');
    $this->assertResponse(200);
    
    // Protocol version 2
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->ticketStore->deleteServiceTicket($st);
    $this->drupalGet('cas/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET">');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->ticketStore->deleteServiceTicket($st);
    $this->drupalGet('cas/p3/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET">');
    $this->assertResponse(200);
}

  /**
   * Test proxy validation.
   */
  function testProxyValidation() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', 6000)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', 6000)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';
    
    // Protocol version 2
    $st = $this->ticketFactory->createProxyTicket($service, FALSE, [], 'foo', $this->exampleUser->id(), $this->ticketFactory->getUsernameAttribute($this->exampleUser));
    $this->drupalGet('cas/proxyValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationSuccess>');
    $this->assertRaw('<cas:user>' . $this->ticketFactory->getUsernameAttribute($this->exampleUser) . '</cas:user>');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createProxyTicket($service, FALSE, [], 'foo', $this->exampleUser->id(), $this->ticketFactory->getUsernameAttribute($this->exampleUser));
    $this->drupalGet('cas/p3/proxyValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationSuccess');
    $this->assertRaw('<cas:user>' . $this->ticketFactory->getUsernameAttribute($this->exampleUser) . '</cas:user>');
    $this->assertResponse(200);

  }

  /**
   * Test failure when giving a proxy ticket to service validation.
   */
  function testWrongTicketType() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', 6000)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', 6000)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';
    
    // Protocol version 2
    $st = $this->ticketFactory->createProxyTicket($service, FALSE, [], 'foo', $this->exampleUser->id(), $this->ticketFactory->getUsernameAttribute($this->exampleUser));
    $this->drupalGet('cas/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET_SPEC">');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createProxyTicket($service, FALSE, [], 'foo', $this->exampleUser->id(), $this->ticketFactory->getUsernameAttribute($this->exampleUser));
    $this->drupalGet('cas/p3/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationFailure code="INVALID_TICKET_SPEC">');
    $this->assertResponse(200);
}

  /**
   * Test a simple valid request.
   */
  function testSimpleSuccess() {
    $this->config('cas_server.settings')->set('ticket.service_ticket_timeout', 6000)->save();
    $this->config('cas_server.settings')->set('ticket.proxy_ticket_timeout', 6000)->save();
    $this->drupalLogin($this->exampleUser);
    $service = 'https://example.com';
    
    // Protocol version 1
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/validate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);
    $this->assertText('yes');
    $this->assertText($this->ticketFactory->getUsernameAttribute($this->exampleUser));
    $this->assertNoRaw('html');
    $this->assertResponse(200);

    // Protocol version 2
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);   
    $this->assertRaw('<cas:authenticationSuccess>');
    $this->assertRaw('<cas:user>' . $this->ticketFactory->getUsernameAttribute($this->exampleUser) . '</cas:user>');
    $this->assertResponse(200);

    // Protocol version 3
    $st = $this->ticketFactory->createServiceTicket($service, FALSE);
    $this->drupalGet('cas/p3/serviceValidate', ['query' => ['service' => $service, 'ticket' => $st->getId()]]);      
    $this->assertRaw('<cas:authenticationSuccess>');
    $this->assertRaw('<cas:user>' . $this->ticketFactory->getUsernameAttribute($this->exampleUser) . '</cas:user>');
    $this->assertResponse(200);
  }

  /**
   * Test a simple request without the correct parameters.
   */
  function testMissingParameters() {
    // Protocol version 1
    $this->drupalGet('cas/validate');
    $this->assertText('no');
    $this->assertNoRaw('html');
    $this->assertResponse(200);

    // Protocol version 2
    $this->drupalGet('cas/serviceValidate');
    $this->assertRaw('<cas:authenticationFailure code="INVALID_REQUEST">');
    $this->assertResponse(200);
    
    // Protocol version 3
    $this->drupalGet('cas/p3/serviceValidate');
    $this->assertRaw('<cas:authenticationFailure code="INVALID_REQUEST">');
    $this->assertResponse(200);

  }

}
