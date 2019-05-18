<?php

namespace Drupal\Tests\pending_user_notification\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Tests\Core\Form\FormTestBase;
use Drupal\pending_user_notification\Form\ApproveUserForm;

/**
 * @coversDefaultClass \Drupal\pending_user_notification\Form\ApproveUserForm
 * @group pending_user_notification
 */
class ApproveUserFormTest extends FormTestBase
{
	/**
	 * The ApproveUserForm
	 */
	protected $form;

	/**
	 * The User stub
	 */
	protected $userStub;

	/**
	 * {@inheritdoc}
	 */
	public function setUp()
	{
		parent::setUp();

		// Create a dummy container.
		$container = new ContainerBuilder();
		$container->set('string_translation', $this->getStringTranslationStub());
		\Drupal::setContainer($container);

		$this->form = ApproveUserForm::create($container);
		$user_stub = $this->getMockBuilder('\Drupal\user\UserInterface')
			->disableOriginalConstructor()
			->getMock();
		$this->userStub = $user_stub;
	}

	/**
	 * @covers ::buildForm
	 */
	public function testBuildForm()
	{
		$form = $this->formBuilder->getForm($this->form);
		$this->assertArrayHasKey('user_not_provided', $form, 'user_not_provided key does not exist in form when user is not provided');

		$form = $this->formBuilder->getForm($this->form);

		$this->assertEquals('pending_user_notification_approve_user_form', $form['#form_id']);

		$user_stub = $this->userStub;
		$user_stub->expects($this->at(0))->method('isActive')->willReturn(TRUE);
		$user_stub->expects($this->at(1))->method('isActive')->willReturn(FALSE);

		$form = $this->formBuilder->getForm($this->form, $user_stub);
		$this->assertArrayHasKey('user_already_approved', $form, 'user_already_approved key does not exist in form when user is activated');

		$form = $this->formBuilder->getForm($this->form, $user_stub);
		$this->assertArrayNotHasKey('user_already_approved', $form, 'user_already_approved key does exist in form when user has not been activated');		
		$this->assertArrayHasKey('#user', $form, '#user key does not exist in form');
		$this->assertInstanceOf('Drupal\user\UserInterface', $form['#user'], '$form[#user] is not an instance of UserInterface. Actual: ' . get_class($form['#user']));
		$this->assertArrayHasKey('activate', $form['actions'], 'activate key does not exist in form[actions]');
	}
}
