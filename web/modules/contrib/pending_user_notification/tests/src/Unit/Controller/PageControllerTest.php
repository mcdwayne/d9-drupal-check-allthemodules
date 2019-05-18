<?php

namespace Drupal\Tests\pending_user_notification\Controller;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\pending_user_notification\Controller\PageController;

/**
 * @coversDefaultClass \Drupal\pending_user_notification\Controller\PageController
 * @group pending_user_notification
 */
class PageControllerTest extends UnitTestCase
{
	/**
	 * {@inheritdoc}
	 */
	public function setUp()
	{
		// Create a dummy container.
		$container = new ContainerBuilder();
		$container->set('string_translation', $this->getStringTranslationStub());
		\Drupal::setContainer($container);
	}

	/**
	 * @covers ::allPendingAccountsPage
	 */
	public function testAllPendingAccountsPage()
	{
		$user_stub = $this->getMockBuilder('\Drupal\user\UserInterface')
			->disableOriginalConstructor()
			->getMock();
		$user_stub->expects($this->any())->method('id')->willReturn(1);
		$user_stub->expects($this->any())->method('getCreatedTime')->willReturn(1234567890);
		$user_stub->expects($this->any())->method('getDisplayName')->willReturn('Dave');

		$form_builder_mock = $this->getMockBuilder('\Drupal\Core\Form\FormBuilderInterface')
			->disableOriginalConstructor()
			->getMock();
		$redirect_destination_mock = $this->getMockBuilder('\Drupal\Core\Routing\RedirectDestinationInterface')
			->disableOriginalConstructor()
			->getMock();
		$date_formatter_mock = $this->getMockBuilder('\Drupal\Core\Datetime\DateFormatterInterface')
			->disableOriginalConstructor()
			->getMock();
		$date_formatter_mock->expects($this->at(0))->method('format')->willReturn('2000-01-01');
		$pending_user_notification_service_mock = $this->getMockBuilder('\Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface')
			->disableOriginalConstructor()
			->getMock();

		$pending_user_notification_service_mock->expects($this->at(0))->method('getPendingUsers')->with('10')->willReturn([]);
		$pending_user_notification_service_mock->expects($this->at(1))->method('getPendingUsers')->with('10')->willReturn([$user_stub]);

		$page_controller = new PageController($form_builder_mock, $redirect_destination_mock, $date_formatter_mock, $pending_user_notification_service_mock);

		$page_array = $page_controller->allPendingAccountsPage();
		$this->assertTrue(is_array($page_array), 'allPendingAccountsPage() does not return an array');
		$this->assertArrayHasKey('pending_user_accounts', $page_array, 'pending_user_accounts key does not exist in page array');
		$this->assertArrayHasKey('pager', $page_array, 'pager key does not exist in page array');
		$this->assertEmpty($page_array['pending_user_accounts']['#rows'], 'Rows is empty when no pending users exists');

		$page_array = $page_controller->allPendingAccountsPage();
		$this->assertInstanceOf('Drupal\Core\Link', $page_array['pending_user_accounts']['#rows'][0][0], 'First column is not instance of \Drupal\Core\Link. Actual: ' . print_r($page_array['pending_user_accounts']['#rows'][0][0], TRUE));
		$this->assertInstanceOf('Drupal\Core\Url', $page_array['pending_user_accounts']['#rows'][0][0]->getUrl(), 'First column Link URL is not instance of \Drupal\Core\Url. Actual: ' . print_r($page_array['pending_user_accounts']['#rows'][0][0]->getUrl(), TRUE));
		$this->assertEquals('entity.user.canonical', $page_array['pending_user_accounts']['#rows'][0][0]->getUrl()->getRouteName(), 'Url route is not entity.user.canonical. Actual: ' . $page_array['pending_user_accounts']['#rows'][0][0]->getUrl()->getRouteName());
		$this->assertEquals('2000-01-01', $page_array['pending_user_accounts']['#rows'][0][1], 'Second is date 2000-01-01. Actual: ' . $page_array['pending_user_accounts']['#rows'][0][1]);
		$this->assertInstanceOf('Drupal\Core\Link', $page_array['pending_user_accounts']['#rows'][0][2], 'Third column is not instance of \Drupal\Core\Link.');
		$this->assertInstanceOf('Drupal\Core\Url', $page_array['pending_user_accounts']['#rows'][0][2]->getUrl(), 'Third column Link URL is not instance of \Drupal\Core\Url. Actual: ' . print_r($page_array['pending_user_accounts']['#rows'][0][2]->getUrl(), TRUE));
		$this->assertEquals('pending_user_notification.user.activate', $page_array['pending_user_accounts']['#rows'][0][2]->getUrl()->getRouteName(), 'Url route is not pending_user_notification.user.activate. Actual: ' . $page_array['pending_user_accounts']['#rows'][0][2]->getUrl()->getRouteName());
		$this->assertInstanceOf('Drupal\Core\Link', $page_array['pending_user_accounts']['#rows'][0][3], 'Fourth column is not instance of \Drupal\Core\Link.');
		$this->assertInstanceOf('Drupal\Core\Url', $page_array['pending_user_accounts']['#rows'][0][3]->getUrl(), 'Fourth column Link URL is not instance of \Drupal\Core\Url. Actual: ' . print_r($page_array['pending_user_accounts']['#rows'][0][3]->getUrl(), TRUE));
		$this->assertEquals('entity.user.cancel_form', $page_array['pending_user_accounts']['#rows'][0][3]->getUrl()->getRouteName(), 'Url route is not entity.user.cancel_form. Actual: ' . $page_array['pending_user_accounts']['#rows'][0][3]->getUrl()->getRouteName());
	}

	/**
	 * @covers ::allPendingAccountsPage
	 */
	public function testActivateUserPage()
	{
		$user_stub = $this->getMockBuilder('\Drupal\user\UserInterface')
			->disableOriginalConstructor()
			->getMock();
		$user_stub->expects($this->any())->method('id')->willReturn(1);
		$user_stub->expects($this->any())->method('getCreatedTime')->willReturn(1234567890);
		$user_stub->expects($this->any())->method('getDisplayName')->willReturn('Dave');

		$form_builder_mock = $this->getMockBuilder('\Drupal\Core\Form\FormBuilderInterface')
			->disableOriginalConstructor()
			->getMock();
		$form_builder_mock->expects($this->at(0))->method('getForm')->willReturn(['form']);
		$redirect_destination_mock = $this->getMockBuilder('\Drupal\Core\Routing\RedirectDestinationInterface')
			->disableOriginalConstructor()
			->getMock();
		$date_formatter_mock = $this->getMockBuilder('\Drupal\Core\Datetime\DateFormatterInterface')
			->disableOriginalConstructor()
			->getMock();
		$pending_user_notification_service_mock = $this->getMockBuilder('\Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface')
			->disableOriginalConstructor()
			->getMock();

		$page_controller = new PageController($form_builder_mock, $redirect_destination_mock, $date_formatter_mock, $pending_user_notification_service_mock);

		$user_stub = $this->getMockBuilder('\Drupal\user\UserInterface')
			->disableOriginalConstructor()
			->getMock();
		
		$page_array = $page_controller->activateUserPage($user_stub);
		$this->assertTrue(is_array($page_array), 'activateUserPage() does not return an array');
		$this->assertArrayHasKey('#prefix', $page_array, '#prefix key does not exist in page array');
		$this->assertEquals($page_array['#prefix'], '<div id="pending_user_notification_activate_user_page">', 'Page prefix is correct');
		$this->assertArrayHasKey('#suffix', $page_array, '#suffix key does not exist in page array');
		$this->assertEquals($page_array['#suffix'], '</div>', 'Page suffix is correct');
		$this->assertArrayHasKey('form', $page_array, 'form key does not exist in page array');
	}
}
