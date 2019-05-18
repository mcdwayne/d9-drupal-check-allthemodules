<?php

namespace Drupal\restrict_ip\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\restrict_ip\Service\RestrictIpServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RestrictIpEventSubscriber implements EventSubscriberInterface
{
	/**
	 * The restrict IP change service
	 *
	 * @var Drupal\restrict_ip\Service\RestrictIpServiceInterface
	 */
	protected $restrictIpService;

	/**
	 * The Logger Factory service
	 *
	 * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
	 */
	protected $loggerFactory;

	/**
	 * The Module Handler service
	 *
	 * @var \Drupal\Core\Extension\ModuleHandlerInterface
	 */
	protected $moduleHandler;

	/**
	 * The Url Generator service
	 *
	 * @var \Drupal\Core\Routing\UrlGeneratorInterface
	 */
	protected $urlGenerator;

	/**
	 * The Restrict IP configuration settings
	 *
	 * @var \Drupal\Core\Config\ImmutableConfig
	 */
	protected $config;

	/**
	 * Creates an instance of the RestrictIpEventSubscriber class
	 *
	 * @param \Drupal\restrict_ip\Service\RestrictIpService $restrictIpService
	 *   The restrict IP service
	 * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
	 *   The Config Factory service
	 * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
	 *   The Logger Factory service
	 * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
	 *   The Module Handler service
	 * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
	 *   The Url Generator service
	 */
	public function __construct(RestrictIpServiceInterface $restrictIpService, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory, ModuleHandlerInterface $moduleHandler, UrlGeneratorInterface $urlGenerator)
	{
		$this->restrictIpService = $restrictIpService;
		$this->loggerFactory = $loggerFactory;
		$this->moduleHandler = $moduleHandler;
		$this->urlGenerator = $urlGenerator;

		$this->config = $configFactory->get('restrict_ip.settings');
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		// On page load, we need to check for whether the user should be blocked by IP
		$events[KernelEvents::REQUEST][] = ['checkIpRestricted'];

		return $events;
	}

	public function checkIpRestricted(GetResponseEvent $event)
	{
		unset($_SESSION['restrict_ip']);

		$this->restrictIpService->testForBlock();

		if($this->restrictIpService->userIsBlocked())
		{
			if($this->restrictIpService->getCurrentPath() != '/restrict_ip/access_denied')
			{
				if($this->moduleHandler->moduleExists('dblog') && $this->config->get('dblog'))
				{
					$this->loggerFactory->get('Restrict IP')->warning('Access to the path %path was blocked for the IP address %ip_address', ['%path' => $this->restrictIpService->getCurrentPath(), '%ip_address' => $this->restrictIpService->getCurrentUserIp()]);
				}

				if($this->config->get('allow_role_bypass') && $this->config->get('bypass_action') === 'redirect_login_page')
				{
					// Redirect the user to the change password page
					$url = Url::fromRoute('user.login');

					$event->setResponse(new RedirectResponse($url->toString()));
				}
				elseif(in_array($this->config->get('white_black_list'), [0, 1]))
				{
					$url = Url::fromRoute('restrict_ip.access_denied_page');

					$event->setResponse(new RedirectResponse($url->toString()));
				}
				else
				{
					$this->setMessage(t('The page you are trying to access cannot be accessed from your IP address.'));
					$url = $this->urlGenerator->generateFromRoute('<front>');
					$event->setResponse(new RedirectResponse($url));
				}
			}
		}
	}

	private function setMessage($message)
	{
		drupal_set_message($message);
	}
}
