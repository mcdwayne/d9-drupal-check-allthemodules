<?php

/**
 * @file contains \Drupal\pending_user_notification\Plugin\Block\PendingUserNotificationBlock
 */

namespace Drupal\pending_user_notification\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides the pending user notification block
 *
 * @Block(
 *   id = "pending_user_notification_block",
 *   admin_label = @Translation("Pending User Accounts"),
 * )
 */
class PendingUserNotificationBlock extends BlockBase implements ContainerFactoryPluginInterface
{
	/**
	 * The config factory object
	 *
	 * @var \Drupal\Core\Config\ConfigFactoryInterface
	 */
	protected $configFactory;

	/**
	 * The current user object
	 *
	 * @var \Drupal\Core\Session\AccountProxyInterface
	 */
	protected $currentUser;

	/**
	 * The redirect destination object
	 *
	 * @var \Drupal\Core\Routing\RedirectDestinationInterface
	 */
	protected $redirectDestination;

	/**
	 * The pending user notification service
	 *
	 * @var \Drupal\pending_user_notifiation\Service\PendingUserNotificationServiceInterface
	 */
	protected $pendingUserNotificationService;

	/**
	 * Creates an AdminForm object
	 *
	 * @param array $configuration
	 * @param string $plugin_id
	 * @param mixed $plugin_definition
	 * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
	 *   The config fatory object
	 * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
	 *   The current user object
	 * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
	 *   The redirect destination service
	 * @param \Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface $pendingUserNotificationService
	 *   The pending user notification service
	 */
	public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, RedirectDestinationInterface $redirectDestination, PendingUserNotificationServiceInterface $pendingUserNotificationService)
	{
		parent::__construct($configuration, $plugin_id, $plugin_definition);

		$this->configFactory = $configFactory;
		$this->currentUser = $currentUser;
		$this->redirectDestination = $redirectDestination;
		$this->pendingUserNotificationService = $pendingUserNotificationService;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
	{
		// Instantiates this form class.
		return new static(
			$configuration,
			$plugin_id,
			$plugin_definition,
			$container->get('config.factory'),
			$container->get('current_user'),
			$container->get('redirect.destination'),
			$container->get('pending_user_notification.service')
		);
	}

	public function build()
	{
		if($this->configFactory->get('user.settings')->get('register') == 'visitors_admin_approval' && $this->currentUser->hasPermission('administer users'))
		{
			$block = [
				'#cache' => ['max-age' => 0],
			];

			$output = [];

			$block_config = $this->getConfiguration();
			$users = $this->pendingUserNotificationService->getPendingUsers();
			if(count($users))
			{
				$header = [$this->t('Username'), $this->t('Activate'), $this->t('Delete')];

				$rows = [];
				foreach($users as $user)
				{
					$row = [];

					$url = Url::fromRoute('entity.user.canonical', ['user' => $user->id()]);
					$link = Link::fromTextAndUrl($user->getDisplayName(), $url);
					$row[] = $link;

					$url = Url::fromRoute('pending_user_notification.user.activate', ['user' => $user->id()], ['query' => $this->redirectDestination->getAsArray()]);
					$link = Link::fromTextAndUrl($this->t('Activate'), $url);
					$row[] = $link;

					$url = Url::fromRoute('entity.user.cancel_form', ['user' => $user->id()], ['query' => $this->redirectDestination->getAsArray()]);
					$link = Link::fromTextAndUrl($this->t('Delete'), $url);
					$row[] = $link;

					$rows[] = $row;
				}

				$output = [
					'pending_users' => [
						'#theme' => 'table',
						'#header' => $header,
						'#rows' => $rows,
					],
					'pager' => [
						'#type' => 'pager',
					],
				];
			}
			elseif(isset($block_config['show_empty_block']) && $block_config['show_empty_block'])
			{
				$output['no_users'] = [
					'#prefix' => '<p>',
					'#suffix' => '</p>',
					'#markup' => $this->t('There are currently no pending user accounts'),
				];
			}

			if(count($output))
			{
				$block += $output;

				if(count($users))
				{
					$block['view_all'] = [
						'#prefix' => '<p class="view_all_link">',
						'#suffix' => '</p>',
						'#type' => 'link',
						'#title' => $this->t('View all'),
						'#url' => Url::fromRoute('pending_user_notification.pending_accounts_list'),
					];
				}
			}

			return $block;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function blockForm($form, FormStateInterface $form_state)
	{
		$form = parent::blockForm($form, $form_state);

		$config = $this->getConfiguration();

		$form['show_empty_block'] = array
		(
			'#type' => 'checkbox',
			'#title' => $this->t('Show block even when no users are pending'),
			'#default_value' => isset($config['show_empty_block']) ? $config['show_empty_block'] : 0,
		);

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function blockSubmit($form, FormStateInterface $form_state)
	{
		$this->setConfigurationValue('show_empty_block', $form_state->getValue('show_empty_block'));
	}
}
