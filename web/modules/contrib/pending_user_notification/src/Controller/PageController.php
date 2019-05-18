<?php

/**
 * @file Contains Drupal\force_password_change\Controller\ForcePasswordChangeController
 *
 * Handles page requests for pages in the Force Password Change module
 */

namespace Drupal\pending_user_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PageController extends ControllerBase
{
	/**
	 * A form builder object
	 *
	 * @var \Drupal\Core\Form\FormBuilderInterface
	 */
	protected $formBuilder;

	/**
	 * The redirect destination object
	 *
	 * @var \Drupal\Core\Routing\RedirectDestinationInterface
	 */
	protected $redirectDestination;

	/**
	 * The date formatter service
	 *
	 * @var \Drupal\Core\Datetime\DateFormatterInterface
	 */
	protected $dateFormatter;

	/**
	 * The pending user notification service
	 *
	 * @var \Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface
	 */
	protected $pendingUserNotificationService;

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container)
	{
		return new static
		(
			$container->get('form_builder'),
			$container->get('redirect.destination'),
			$container->get('date.formatter'),
			$container->get('pending_user_notification.service')
		);
	}

	/**
	 * Constructs a FriendController object.
	 *
	 * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
	 *   The form builder service.
	 * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
	 *   The redirect destination service
	 * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
	 *   The DateFormatter service
	 * @param \Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface $pendingUserNotificationService
	 *   The pending user notification service
	 */
	public function __construct(FormBuilderInterface $formBuilder, RedirectDestinationInterface $redirectDestination, DateFormatterInterface $dateFormatter, PendingUserNotificationServiceInterface $pendingUserNotificationService)
	{
		$this->formBuilder = $formBuilder;
		$this->redirectDestination = $redirectDestination;
		$this->dateFormatter = $dateFormatter;
		$this->pendingUserNotificationService = $pendingUserNotificationService;
	}

	public function allPendingAccountsPage()
	{
		$page = [
			'#prefix' => '<div id="pending_user_notification_listing_page">',
			'#suffix' => '</div>',
		];

		$pending_user_accounts = $this->pendingUserNotificationService->getPendingUsers(10);

		$header = [$this->t('User'), $this->t('Created'), $this->t('Approve'), $this->t('Delete')];
		$rows = [];
		foreach($pending_user_accounts as $user)
		{
			$row = [];

			$url = Url::fromRoute('entity.user.canonical', ['user' => $user->id()]);
			$link = Link::fromTextAndUrl($user->getDisplayName(), $url);
			$row[] = $link;

			$row[] = $this->dateFormatter->format($user->getCreatedTime(), 'short');

			$url = Url::fromRoute('pending_user_notification.user.activate', ['user' => $user->id()], ['query' => $this->redirectDestination->getAsArray()]);
			$link = Link::fromTextAndUrl($this->t('Activate'), $url);
			$row[] = $link;

			$url = Url::fromRoute('entity.user.cancel_form', ['user' => $user->id()], ['query' => $this->redirectDestination->getAsArray()]);
			$link = Link::fromTextAndUrl($this->t('Delete'), $url);
			$row[] = $link;

			$rows[] = $row;
		}

		$page['pending_user_accounts'] = [
			'#type' => 'table',
			'#header' => $header,
			'#rows' => $rows,
			'#empty' => $this->t('No pending users found'),
		];

		$page['pager'] = [
			'#type' =>  'pager',
		];

		return $page;
	}

	public function activateUserPage(UserInterface $user)
	{
		$page = [
			'#prefix' => '<div id="pending_user_notification_activate_user_page">',
			'#suffix' => '</div>',
		];

		if($user)
		{
			$page['form'] = $this->formBuilder->getForm('\Drupal\pending_user_notification\Form\ApproveUserForm', $user);
		}
		else
		{
			$page['#markup'] = $this->t('User not provided');
		}

		return $page;
	}
}
