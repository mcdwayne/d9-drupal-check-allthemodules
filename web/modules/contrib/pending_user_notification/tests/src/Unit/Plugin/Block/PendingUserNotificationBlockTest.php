<?php

namespace Drupal\Tests\pending_user_notification\Plugin\Block;

use Drupal\Tests\UnitTestCase;
use Drupal\pending_user_notification\Plugin\Block\PendingUserNotificationBlock;

/**
 * @coversDefaultClass \Drupal\pending_user_notification\Plugin\Block\PendingUserNotificationBlock
 * @group pending_user_notification
 */
class PendingUserNotificationBlockTest extends UnitTestCase
{
	const PLUGIN_ID = 'pending_user_notification_block';

	protected $configFactory;
	protected $currentUser;
	protected $redirectDestination;
	protected $pendingUserNotificationService;

	/**
	 * {@inheritdoc}
	 */
	public function setUp()
	{
		$this->configFactory = $this->getMockBuilder('\Drupal\Core\Config\ConfigFactoryInterface')
			->disableOriginalConstructor()
			->getMock();

		$this->currentUser = $this->getMockBuilder('\Drupal\Core\Session\AccountProxyInterface')
			->disableOriginalConstructor()
			->getMock();

		$this->redirectDestination = $this->getMockBuilder('\Drupal\Core\Routing\RedirectDestinationInterface')
			->disableOriginalConstructor()
			->getMock();

		$this->pendingUserNotificationService = $this->getMockBuilder('\Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @covers ::build
	 */
	public function testBuild()
	{
		$this->configFactory->expects($this->at(0))
			->method('get')
			->with('user.settings')
			->willReturn($this->configFactory);
		$this->configFactory->expects($this->at(1))
			->method('get')
			->with('register')
			->willReturn(FALSE);

		$pending_user_notification_block = new PendingUserNotificationBlock($this->getConfiguration(), self::PLUGIN_ID, $this->getPluginDefinition(), $this->configFactory, $this->currentUser, $this->redirectDestination, $this->pendingUserNotificationService);
		$block_contents = $pending_user_notification_block->build();
		$this->assertNull($block_contents, 'Null block contents when show site registration is not set to admin approval required ' . print_r($block_contents, TRUE));

		$this->configFactory->expects($this->at(0))
			->method('get')
			->with('user.settings')
			->willReturn($this->configFactory);
		$this->configFactory->expects($this->at(1))
			->method('get')
			->with('register')
			->willReturn('visitors_admin_approval');

		$this->currentUser->expects($this->at(0))
			->method('hasPermission')
			->with('administer users')
			->willReturn(FALSE);

		$pending_user_notification_block = new PendingUserNotificationBlock($this->getConfiguration(), self::PLUGIN_ID, $this->getPluginDefinition(), $this->configFactory, $this->currentUser, $this->redirectDestination, $this->pendingUserNotificationService);
		$block_contents = $pending_user_notification_block->build();
		$this->assertNull($block_contents, 'Null block contents when user does not have administer users permission ' . print_r($block_contents, TRUE));

		$this->configFactory->expects($this->at(0))
			->method('get')
			->with('user.settings')
			->willReturn($this->configFactory);
		$this->configFactory->expects($this->at(1))
			->method('get')
			->with('register')
			->willReturn('visitors_admin_approval');

		$this->currentUser->expects($this->at(0))
			->method('hasPermission')
			->with('administer users')
			->willReturn(TRUE);

		$this->pendingUserNotificationService->expects($this->at(0))
			->method('getPendingUsers')
			->willReturn([]);

		$pending_user_notification_block = new PendingUserNotificationBlock($this->getConfiguration(), self::PLUGIN_ID, $this->getPluginDefinition(), $this->configFactory, $this->currentUser, $this->redirectDestination, $this->pendingUserNotificationService);
		$block_contents = $pending_user_notification_block->build();
		$this->assertArrayNotHasKey('no_users', $block_contents, 'no_users exists when show_empty_block set to FALSE ' . print_r($block_contents, TRUE));
		$this->assertArrayNotHasKey('pending_users', $block_contents, 'pending_users exists when show_empty_block set to FALSE ' . print_r($block_contents, TRUE));

		$this->configFactory->expects($this->at(0))
			->method('get')
			->with('user.settings')
			->willReturn($this->configFactory);
		$this->configFactory->expects($this->at(1))
			->method('get')
			->with('register')
			->willReturn('visitors_admin_approval');

		$this->currentUser->expects($this->at(0))
			->method('hasPermission')
			->with('administer users')
			->willReturn(TRUE);

		$this->pendingUserNotificationService->expects($this->at(0))
			->method('getPendingUsers')
			->willReturn([]);

		$pending_user_notification_block = new PendingUserNotificationBlock($this->getConfiguration(1), self::PLUGIN_ID, $this->getPluginDefinition(), $this->configFactory, $this->currentUser, $this->redirectDestination, $this->pendingUserNotificationService);
		$block_contents = $pending_user_notification_block->build();
		$this->assertArrayNotHasKey('pending_users', $block_contents, 'Pending users element shown with no users and show empty message set to TRUE');
		$this->assertArrayHasKey('no_users', $block_contents, 'no_users does not exist in contents when show empty block set to TRUE');

		$this->configFactory->expects($this->at(0))
			->method('get')
			->with('user.settings')
			->willReturn($this->configFactory);
		$this->configFactory->expects($this->at(1))
			->method('get')
			->with('register')
			->willReturn('visitors_admin_approval');

		$this->currentUser->expects($this->at(0))
			->method('hasPermission')
			->with('administer users')
			->willReturn(TRUE);

		$user_stub = $this->getMockBuilder('\Drupal\user\UserInterface')
			->disableOriginalConstructor()
			->getMock();
		$user_stub->expects($this->any())
			->method('id')
			->willReturn(1);
		$user_stub->expects($this->any())
			->method('getDisplayName')
			->willReturn('Dave');

		$this->pendingUserNotificationService->expects($this->at(0))
			->method('getPendingUsers')
			->willReturn([$user_stub]);

		$this->redirectDestination->expects($this->any())
			->method('getAsArray')
			->willReturn([]);

		$pending_user_notification_block = new PendingUserNotificationBlock($this->getConfiguration(1), self::PLUGIN_ID, $this->getPluginDefinition(), $this->configFactory, $this->currentUser, $this->redirectDestination, $this->pendingUserNotificationService);
		$block_contents = $pending_user_notification_block->build();
		$this->assertArrayNotHasKey('no_users', $block_contents, 'no_users exists in contents when users exist');
		$this->assertArrayHasKey('pending_users', $block_contents, 'pending_users does not exist when users exist');
		$this->assertArrayHasKey('#rows', $block_contents['pending_users'], '#rows does not exist when users exist');
		$this->assertArrayHasKey('0', $block_contents['pending_users']['#rows'], '[#rows][0] does not exist when users exist');
		$this->assertArrayHasKey('0', $block_contents['pending_users']['#rows'][0], '[#rows][0][0] does not exist when users exist');
		$this->assertInstanceOf('Drupal\Core\Link', $block_contents['pending_users']['#rows'][0][0], 'First column of table contains an instance of Link');
		$this->assertArrayHasKey('1', $block_contents['pending_users']['#rows'][0], '[#rows][0][1] does not exist when users exist');
		$this->assertInstanceOf('Drupal\Core\Link', $block_contents['pending_users']['#rows'][0][1], 'Second column of table contains an instance of Link');
		$this->assertArrayHasKey('2', $block_contents['pending_users']['#rows'][0], '[#rows][0][2] does not exist when users exist');
		$this->assertInstanceOf('Drupal\Core\Link', $block_contents['pending_users']['#rows'][0][2], 'Third column of table contains an instance of Link');
		$this->assertArrayHasKey('view_all', $block_contents, 'view_all does not exist when users exist');
		$this->assertArrayHasKey('#type', $block_contents['view_all'], 'view_all does not have #type key when users exist');
		$this->assertArrayHasKey('#title', $block_contents['view_all'], 'view_all does not have #title key when users exist');
		$this->assertArrayHasKey('#url', $block_contents['view_all'], 'url does not have #title key when users exist');
		$this->assertInstanceOf('Drupal\Core\Url', $block_contents['view_all']['#url'], 'view_all #url is not an instance of URL when users exist');
		$this->assertEquals('pending_user_notification.pending_accounts_list', $block_contents['view_all']['#url']->getRouteName(), 'Url route for view all link is not pending_user_notification.pending_accounts_list');
	}

	private function getConfiguration($show_empty_block = 0)
	{
		$configuration = [
			'id' => 'pending_user_notification_block',
			'label' => 'Pending User Accounts',
			'provider' => 'pending_user_notification',
			'label_display' => 'visible',
			'show_empty_block' => $show_empty_block,
		];

		return $configuration;
	}

	private function getPluginDefinition()
	{
		$definition = [
			'admin_label' => 'Pending User Accounts',
			'category' => 'Pending User Notification',
			'id' => 'pending_user_notification_block',
			'class' => 'Drupal\pending_user_notification\Plugin\Block\PendingUserNotificationBlock',
			'provider' => 'pending_user_notification',
		];

		return $definition;
	}
}
