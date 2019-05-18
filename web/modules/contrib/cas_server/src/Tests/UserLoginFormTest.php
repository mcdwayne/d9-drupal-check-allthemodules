<?php

namespace Drupal\cas_server\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\cas_server\Entity\CasServerService;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;

/**
 * Tests responses from the user login form.
 *
 * @group cas_server
 */
class UserLoginFormTest extends WebTestBase {

  public static $modules = ['cas_server'];

  protected function setUp() {
    parent::setUp();

    $this->exampleUser = $this->drupalCreateUser([], 'exampleUserName');
    $this->exampleUser->save();

    $this->ticketFactory = $this->container->get('cas_server.ticket_factory');
    $this->ticketStore = $this->container->get('cas_server.storage');
    $this->connection = $this->container->get('database');
    $this->tempStoreFactory = $this->container->get('user.private_tempstore');
    $this->entityStorage = $this->container->get('entity_type.manager')->getStorage('cas_server_service');

    $test = CasServerService::create([
      'id' => 'test',
      'label' => 'Test Service',
      'service' => '*',
      'sso' => TRUE,
      'attributes' => [],
    ]);
    $test->save();
  }

  /**
   * Test submitting with bad username/password.
   */
  public function testBadCredentials() {
    $this->drupalGet('cas/login');
    $edit = [
      'username' => $this->exampleUser->getAccountName(),
      'password' => $this->exampleUser->pass_raw + 'foadasd',
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $this->assertTrue(empty($this->cookies['cas_tgc']));
    $this->assertResponse(200);
    $this->assertText('Bad username/password combination given.');

  }

  /**
   * Test submitting with correct values but no service.
   */
  public function testCorrectNoService() {
    $this->drupalGet('cas/login');
    $edit = [
      'username' => $this->exampleUser->getAccountName(),
      'password' => $this->exampleUser->pass_raw,
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $this->assertTrue(!empty($this->cookies['cas_tgc']));
    $this->assertResponse(200);
    $this->assertText('You are logged in to CAS single sign on.');

  }

  /**
   * Test submitting without a valid login ticket.
   */
  public function testInvalidLoginTicket() {
    // We want to cause the session lt to no longer be valid.
    $this->drupalGet('cas/login');
    $this->curlClose();
    $this->curlInitialize();
    $edit = [
      'username' => $this->exampleUser->getAccountName(),
      'password' => $this->exampleUser->pass_raw,
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $this->assertResponse(200);
    $this->assertTrue(empty($this->cookies['cas_tgc']));
    $this->assertText('Login ticket invalid. Please try again');
    
  }


  /**
   * Test submitting with correct values and a service.
   */
  public function testCorrectWithService() {
    $service = Url::fromRoute('cas_server.validate1');
    $service->setAbsolute();
    $this->drupalGet('cas/login', ['query' => ['service' => $service->toString()]]);
    $edit = [
      'username' => $this->exampleUser->getAccountName(),
      'password' => $this->exampleUser->pass_raw,
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $this->assertTrue(!empty($this->cookies['cas_tgc']));
    $this->assertResponse(200);
    $this->assertEqual($this->redirectCount, 2);

    $ticket = $this->connection->select('cas_server_ticket_store', 'c')
      ->fields('c', array('id'))
      ->condition('session', Crypt::hashBase64($this->sessionId))
      ->condition('type', 'service')
      ->execute()
      ->fetch();
    $tid = $ticket->id;

    $this->assertUrl('cas/validate', ['query' => ['ticket' => $tid]]);

  }

  /**
   * Test submitting with correct values and a service not configured for SSO.
   */
  public function testCorrectWithServiceNoSso() {
    $test = $this->entityStorage->load('test');
    $test->setSso(FALSE);
    $test->save();
    
    $service = Url::fromRoute('cas_server.validate1');
    $service->setAbsolute();
    $this->drupalGet('cas/login', ['query' => ['service' => $service->toString()]]);
    $edit = [
      'username' => $this->exampleUser->getAccountName(),
      'password' => $this->exampleUser->pass_raw,
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $this->assertTrue(empty($this->cookies['cas_tgc']));
    $this->assertResponse(200);
    $this->assertEqual($this->redirectCount, 2);

    $ticket = $this->connection->select('cas_server_ticket_store', 'c')
      ->fields('c', array('id'))
      ->condition('session', Crypt::hashBase64($this->sessionId))
      ->condition('type', 'service')
      ->execute()
      ->fetch();
    $tid = $ticket->id;

    $this->assertUrl('cas/validate', ['query' => ['ticket' => $tid]]);

  }
}
