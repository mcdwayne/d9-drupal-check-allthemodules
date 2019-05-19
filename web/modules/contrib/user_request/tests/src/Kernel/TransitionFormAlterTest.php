<?php

namespace Drupal\Tests\user_request\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user_request\Traits\RequestMockTrait;

/**
 * Tests altering the state transition form.
 *
 * @group user_request
 */
class TransitionFormAlterTest extends KernelTestBase {
  use RequestMockTrait {
    mockRequestType as traitMockRequestType;
  }

  const FORM_ID = 'state_machine_transition_form_user_request_state';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_request'];

  /**
   * Whether the user has permission to respond the request.
   *
   * @var boolean
   */
  protected $canRespond = true;

  /**
   * Response transitions.
   *
   * @var string[]
   */
  protected $responseTransitions = ['approve', 'reject'];

  /**
   * A request whose transition form will be built.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  /**
   * A mocked form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  public function testFormWithoutAnyTransitions() {
    $form = [];
    \user_request_form_state_machine_transition_form_alter($form, $this->formState, self::FORM_ID);
    $this->assertEmpty($form);
  }

  public function testFormWithoutResponseTransitions() {
    $form = [];
    $this->attachTransitionAction($form, 'cancel');
    \user_request_form_state_machine_transition_form_alter($form, $this->formState, self::FORM_ID);
    $this->assertFormHasTransitionAction('cancel', $form);
    $this->assertFormNotHasRespondAction($form);
  }

  public function testWithOneResponseTransitionAndSomeOtherTransition() {
    $form = [];
    $this->attachTransitionAction($form, 'cancel');
    $this->attachTransitionAction($form, 'approve');
    \user_request_form_state_machine_transition_form_alter($form, $this->formState, self::FORM_ID);
    $this->assertFormHasTransitionAction('cancel', $form);
    $this->assertFormNotHasTransitionAction('approve', $form);
    $this->assertFormHasRespondAction($form);
  }

  public function testWithTwoResponseTransitions() {
    $form = [];
    $this->attachTransitionAction($form, 'approve');
    $this->attachTransitionAction($form, 'reject');
    \user_request_form_state_machine_transition_form_alter($form, $this->formState, self::FORM_ID);
    $this->assertFormNotHasTransitionAction('approve', $form);
    $this->assertFormNotHasTransitionAction('reject', $form);
    $this->assertFormHasRespondAction($form);
    $this->assertCount(1, $form['actions']);
  }

  public function testWithResponseTransitionButWithoutPermissionToRespond() {
    $this->canRespond = FALSE;
    $form = [];
    $this->attachTransitionAction($form, 'approve');
    \user_request_form_state_machine_transition_form_alter($form, $this->formState, self::FORM_ID);
    $this->assertFormNotHasTransitionAction('approve', $form);
    $this->assertFormNotHasRespondAction($form);
  }

  public function testRedirectsToResponseForm() {
    $form = [];
    $this->formState
      ->expects($this->once())
      ->method('setRedirect')
      ->with(
        $this->equalTo('entity.user_request_response.add_form'),
        $this->logicalAnd(
          $this->arrayHasKey('user_request'),
          $this->contains($this->request->id())
        ),
        $this->arrayHasKey('query')
      );
    \user_request_respond_submit($form, $this->formState);
  }

  public function getCanRespond() {
    return $this->canRespond;
  }

  public function getResponseTransitions() {
    return $this->responseTransitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    // Mocks services.
    $access_manager = $this->mockAccessManager();
    $destination = $this->mockDestination();
    $container->set('access_manager', $access_manager);
    $container->set('redirect.destination', $destination);

    // Mocks the request.
    $this->request = $this->mockRequest([
      'user_request_type' => $this->mockRequestType(),
    ]);

    // Mocks a form state and its dependencies.
    $form_object = $this->mockFormObject($this->request);
    $this->formState = $this->mockFormState($form_object);
  }

  protected function assertFormHasRespondAction(array &$form) {
    $this->assertArrayHasKey('respond', $form['actions']);
    $this->assertEquals('submit', $form['actions']['respond']['#type']);
    $this->assertContains('user_request_respond_submit', $form['actions']['respond']['#submit']);
  }

  protected function assertFormNotHasRespondAction(array &$form) {
    if (!empty($form['actions'])) {
      $this->assertArrayNotHasKey('respond', $form['actions']);
    }
  }

  protected function assertFormHasTransitionAction($transition_id, array &$form) {
    $this->assertArrayHasKey($transition_id, $form['actions']);
  }

  protected function assertFormNotHasTransitionAction($transition_id, array &$form) {
    if (!empty($form['actions'])) {
      $this->assertArrayNotHasKey($transition_id, $form['actions']);
    }
  }

  protected function attachTransitionAction(array &$form, $transition_id) {
    $form['actions'][$transition_id] = [
      '#type' => 'submit',
    ];
  }

  protected function mockFormState(FormInterface $form_object) {
    $form_state = $this->getMock('\Drupal\Core\Form\FormStateInterface'); 
    $form_state
      ->expects($this->any())
      ->method('getFormObject')
      ->will($this->returnValue($form_object));
    return $form_state;
  }

  protected function mockFormObject(EntityInterface $entity) {
    $form_object = $this->getMock('\Drupal\Core\Entity\EntityFormInterface');
    $form_object
      ->expects($this->any())
      ->method('getEntity')
      ->will($this->returnValue($entity));
    return $form_object;
  }

  protected function mockAccessManager() {
    $access_manager = $this->getMock('\Drupal\Core\Access\AccessManagerInterface');
    $access_manager
      ->expects($this->any())
      ->method('checkNamedRoute')
      ->will($this->returnCallback([$this, 'getCanRespond']));
    return $access_manager;
  }

  protected function mockDestination(array $query = []) {
    if (!isset($query['destination'])) {
      $query['destination'] = [];
    }
    $destination = $this->getMock('\Drupal\Core\Routing\RedirectDestinationInterface');
    $destination
      ->expects($this->any())
      ->method('getAsArray')
      ->will($this->returnValue($query));
    return $destination;
  }

  protected function mockRequestType(array $values = []) {
    $request_type = $this->traitMockRequestType($values);
    $request_type
      ->expects($this->any())
      ->method('getResponseTransitions')
      ->will($this->returnCallback([$this, 'getResponseTransitions']));
    return $request_type;
  }

}
