<?php

namespace Drupal\restrict_ip\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PageController extends ControllerBase implements PageControllerInterface
{
	/**
	 * The Form Builder service
	 *
	 * @var \Drupal\Core\Form\FormBuilderInterface
	 */
	protected $formBuilder;

	/**
	 * The Config Factory service
	 *
	 * @var \Drupal\Core\Config\ConfigFactoryInterface
	 */
	protected $configFactory;

	/**
	 * The current user
	 *
	 * @var \Drupal\Core\Session\AccountProxyInterface
	 */
	protected $currentUser;

	/**
	 * The Module Handler service
	 *
	 * @var \Drupal\Core\Extension\ModuleHandlerInterface
	 */
	protected $moduleHandler;

	/**
	 * Constructs the PageController object for the Restrict IP module
	 *
	 * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
	 *   The Form Builder service
	 * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
	 *   The Config Factory service
	 * @param \Drupal\Core\Session\AccountProxyInterface $currentUser;
	 *   The current user
	 * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
	 *   The Module Handler service
	 */
	public function __construct(FormBuilderInterface $formBuilder, ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, ModuleHandlerInterface $moduleHandler)
	{
		$this->formBuilder = $formBuilder;
		$this->configFactory = $configFactory;
		$this->currentUser = $currentUser;
		$this->moduleHandler = $moduleHandler;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container)
	{
		return new static
		(
			$container->get('form_builder'),
			$container->get('config.factory'),
			$container->get('current_user'),
			$container->get('module_handler')
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function configPage()
	{
		$page = [
			'#prefix' => '<div id="restrict_ip_config_page">',
			'#suffix' => '</div.',
			'form' => $this->formBuilder->getForm('\Drupal\restrict_ip\Form\ConfigForm'),
		];

		return $page;
	}

	/**
	 * {@inheritdoc}
	 */
	public function accessDeniedPage()
	{
		if(!isset($_SESSION['restrict_ip']) || !$_SESSION['restrict_ip'])
		{
			return new RedirectResponse(Url::fromRoute('<front>')->toString());
		}

		$config = $this->configFactory->get('restrict_ip.settings');
		$page['access_denied'] = [
			'#markup' => $this->t('The page you are trying to access cannot be accessed from your IP address.'),
			'#prefix' => '<p>',
			'#suffix' => '</p>',
		];

		$contact_mail = $config->get('mail_address');
		if(strlen($contact_mail))
		{
			$contact_mail = str_replace('@', '[at]', $contact_mail);
			$mail_markup = new FormattableMarkup('<span id="restrict_ip_contact_mail">@address</span>', ['@address' => $contact_mail]);
			$page['contact_us'] = [
				'#prefix' => '<p>',
				'#suffix' => '</p>',
				'#markup' => $this->t('If you feel this is in error, please contact an administrator at @email.', ['@email' => $mail_markup]),
				'#attached' => [
					'library' => [
						'restrict_ip/mail_fixer',
					],
				],
			];
		}

		if($config->get('allow_role_bypass'))
		{
			if($this->currentUser->isAuthenticated())
			{
				$url = Url::fromRoute('user.logout');
				$link = Link::fromTextAndUrl($this->t('Logout'), $url);
				$page['logout_link'] = [
					'#markup' => $link->toString(),
					'#prefix' => '<p>',
					'#suffix' => '</p>',
				];
			}
			elseif($config->get('bypass_action') === 'provide_link_login_page')
			{
				$url = Url::fromRoute('user.login');
				$link = Link::fromTextAndUrl($this->t('Sign in'), $url);
				$page['login_link'] = [
					'#markup' => $link->toString(),
					'#prefix' => '<p>',
					'#suffix' => '</p>',
				];
			}
		}

		$this->moduleHandler->alter('restrict_ip_access_denied_page', $page);

		return $page;
	}
}
