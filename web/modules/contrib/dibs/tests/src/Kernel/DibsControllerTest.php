<?php

namespace Drupal\Tests\dibs\Kernel;

use Drupal\dibs\Controller\DibsPagesController;
use Drupal\dibs\Entity\DibsTransaction;
use Drupal\dibs\Event\DibsEvents;
use Drupal\dibs\Form\DibsCancelForm;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DibsControllerTest
 * @package Drupal\Tests\dibs\Kernel
 * @group dibs
 */
class DibsControllerTest extends DibsBaseKernelTest {

  public static $modules = ['dibs', 'dibs_test'];

  /**
   * @var DibsPagesController
   */
  protected $controller;

  /**
   * @var Request
   */
  protected $request;

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('dibs_transaction');
    $this->installConfig('dibs');
    $this->controller = new DibsPagesController($this->container->get('event_dispatcher'), $this->container->get('form_builder'));
    $this->request = Request::createFromGlobals();
  }

  public function testAcceptAction() {
    $this->request->request->set('transact', '123123123');
    $transaction = $this->getTransaction(['status' => 'CREATED']);
    $transaction->save();
    $actual_renderable = $this->controller->accept($this->request, $transaction->hash->value);
    $expected_renderable = [
      '#theme' => 'dibs_accept_page',
      '#transaction' => $transaction,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    $this->assertEquals($expected_renderable['#theme'], $actual_renderable['#theme']);
    $this->assertEquals($expected_renderable['#cache'], $actual_renderable['#cache']);
    $this->assertEquals($expected_renderable['#transaction']->order_id->value, $actual_renderable['#transaction']->order_id->value);
    $this->assertTrue($this->container->get('state')->get(DibsEvents::ACCEPT_TRANSACTION));
    $this->assertFalse($this->container->get('state')->get(DibsEvents::CANCEL_TRANSACTION));
    $this->assertFalse(FALSE, $this->container->get('state')->get(DibsEvents::APPROVE_TRANSACTION));
    $transaction = DibsTransaction::load($transaction->id());
    $this->assertEquals('ACCEPTED', $transaction->status->value);

    $this->config('dibs.settings')->set('advanced.capture_now', TRUE)->save();
    $this->controller->accept($this->request, $transaction->hash->value);
    $this->assertEquals(TRUE, $this->container->get('state')->get(DibsEvents::ACCEPT_TRANSACTION));
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::CANCEL_TRANSACTION));
    $this->assertEquals(TRUE, $this->container->get('state')->get(DibsEvents::APPROVE_TRANSACTION));
    $this->assertEquals('APPROVED', $transaction->status->value);
  }

  public function testCancelAction() {
    $this->request->request->set('transact', '123123123');
    $transaction = $this->getTransaction(['status' => 'CREATED']);
    $transaction->save();
    $actual_renderable = $this->controller->cancel($this->request, $transaction->hash->value);
    $expected_renderable = [
      '#theme' => 'dibs_cancel_page',
      '#transaction' => $transaction,
      '#form' => [],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    $this->assertEquals($expected_renderable['#theme'], $actual_renderable['#theme']);
    $this->assertEquals('dibs-cancel-form', $actual_renderable['#form']['#id']);
    $this->assertEquals($expected_renderable['#cache'], $actual_renderable['#cache']);
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::ACCEPT_TRANSACTION));
    $this->assertEquals(TRUE, $this->container->get('state')->get(DibsEvents::CANCEL_TRANSACTION));
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::APPROVE_TRANSACTION));
  }

  public function testCallbackAction() {
    $this->request->request->set('transact', '123123123');
    $transaction = $this->getTransaction(['status' => 'ACCEPTED']);
    $transaction->save();
    $actual_response = $this->controller->callback($this->request, $transaction->hash->value);
    $this->assertEquals(new Response(), $actual_response);
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::ACCEPT_TRANSACTION));
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::CANCEL_TRANSACTION));
    $this->assertEquals(TRUE, $this->container->get('state')->get(DibsEvents::APPROVE_TRANSACTION));
  }

  public function testRedirectAction() {
    $this->request->request->set('transact', '123123123');
    $transaction = $this->getTransaction(['status' => 'CREATED']);
    $transaction->save();
    $actual_response = $this->controller->redirectForm($transaction->hash->value);

    $this->assertEquals('dibs-redirect-form', $actual_response['#form']['#id']);
    $this->assertEquals('<script type="text/javascript">document.getElementById("dibs-redirect-form").submit()</script>', $actual_response['#inline_script']);
    $this->assertEquals(['max-age' => 0], $actual_response['#cache']);
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::ACCEPT_TRANSACTION));
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::CANCEL_TRANSACTION));
    $this->assertEquals(FALSE, $this->container->get('state')->get(DibsEvents::APPROVE_TRANSACTION));
  }

}
