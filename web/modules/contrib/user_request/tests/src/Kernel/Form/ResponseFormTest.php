<?php

namespace Drupal\Tests\user_request\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user_request\Entity\Request;
use Drupal\user_request\Entity\Response;
use Drupal\user_request\Form\ResponseForm;

/**
 * @coversDefaultClass \Drupal\user_request\Form\ResponseForm
 * @group user_request
 */
class ResponseFormTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['state_machine', 'user_request'];

  /**
   * The form object.
   *
   * @var \Drupal\user_request\Form\ResponseForm
   */
  protected $form;

  /**
   * The (mocked) request whose response will be edited.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  /**
   * The response being edited.
   *
   * @var \Drupal\user_request\Entity\ResponseInterface
   */
  protected $response;

  public function testTransitionFieldIsRequiredForNewResponses() {
    $form_array = $this->createFormArray();
    $form_state = $this->createFormState();
    $form_array = $this->form->buildForm($form_array, $form_state);

    $this->assertTrue($this->response->isNew());
    $this->assertArrayHasKey('transition', $form_array);
    $this->assertNotEmpty($form_array['transition']['#required']);
  }

  public function testTransitionFieldNotIncludedForExistingResponses() {
    // Saves the response so that it is not new anymore.
    $this->response->save();
    $this->request->respond('approve', $this->response);
    $this->request->save();

    $form_array = $this->createFormArray();
    $form_state = $this->createFormState();
    $form_array = $this->form->buildForm($form_array, $form_state);

    $this->assertFalse($this->response->isNew());
    $this->assertArrayNotHasKey('transition', $form_array);
  }

  public function testOnlyResponseTransitionOptions() {
    $form_array = $this->createFormArray();
    $form_state = $this->createFormState();
    $form_array = $this->form->buildForm($form_array, $form_state);

    $request_type = $this->request->getRequestType();
    $response_transitions = $request_type->getResponseTransitions();
    $this->assertArraySubset(array_keys($form_array['transition']['#options']),
      $response_transitions);
  }

  public function testTransitionIsPerformedForNewResponses() {
    $this->assertEmpty($this->request->id());
    $this->assertEmpty($this->response->id());
    $this->assertEquals('pending', $this->request->getStateString());

    $form_array = $this->createFormArray();
    $form_state = $this->mockSubmittedFormState('approve');
    $this->form->save($form_array, $form_state);

    $this->assertNotEmpty($this->request->id());
    $this->assertNotEmpty($this->response->id());
    $this->assertEquals('approved', $this->request->getStateString());
    $this->assertEquals($this->response, $this->request->getResponse());
  }

  public function testTransitionIsNotPerformedWhenEditingResponse() {
    // Saves the response so that it is not new anymore.
    $this->response->save();
    $this->request->respond('approve', $this->response);
    $this->request->save();
    $this->assertEquals('approved', $this->request->getStateString());

    $form_array = $this->createFormArray();
    $form_state = $this->mockSubmittedFormState();
    $this->form->save($form_array, $form_state);

    $this->assertEquals('approved', $this->request->getStateString());
    $this->assertEquals($this->response, $this->request->getResponse());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user_request']);
    $this->installEntitySchema('user_request');
    $this->installEntitySchema('user_request_response');

    // Creates a pending request and saves it.
    $this->request = Request::create([
      'type' => 'user_request',
    ]);

    // Creates a new response for the request.
    $this->response = Response::create([
      'type' => 'user_request_response',
    ]);

    // Instantiates a form.
    $entity_manager = \Drupal::service('entity.manager');
    $module_handler = \Drupal::service('module_handler');
    $request_stack = $this->mockRequestStack();
    $this->form = new ResponseForm($entity_manager, $this->request);
    $this->form->setEntity($this->response);
    $this->form->setModuleHandler($module_handler);
    $this->form->setRequestStack($request_stack);
  }

  protected function createResponseForm(ResponseInterface $response, RequestInterface $request = NULL) {
    if (empty($request)) {
      $request = $response->getRequest();
    }
    $entity_manager = \Drupal::service('entity.manager');
    $module_handler = \Drupal::service('module_handler');
    $request_stack = $this->mockRequestStack();
    $form = new ResponseForm($entity_manager, $request);
    $form->setEntity($response);
    $form->setModuleHandler($module_handler);
    $form->setRequestStack($request_stack);
    return $form;
  }

  protected function createFormArray() {
    return [];
  }

  protected function createFormState() {
    return new FormState();
  }

  protected function mockSubmittedFormState($transition = NULL) {
    $form_state = $this->getMock('\Drupal\Core\Form\FormStateInterface');
    if (isset($transition)) {
      $form_state
        ->expects($this->any())
        ->method('getValue')
        ->with($this->equalTo('transition'))
        ->will($this->returnValue($transition));
    }
    return $form_state;
  }

  protected function mockRequestStack() {
    $request = $this->mockCurrentRequest();
    $stack = $this->getMock('\Symfony\Component\HttpFoundation\RequestStack');
    $stack
      ->expects($this->any())
      ->method('getCurrentRequest')
      ->will($this->returnValue($request));
    return $stack;
  }

  protected function mockCurrentRequest() {
    $query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
    $query
      ->expects($this->any())
      ->method('has')
      ->will($this->returnValue(FALSE));
    $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
    $request->query = $query;
    return $request;
  }

}
