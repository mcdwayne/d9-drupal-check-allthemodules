<?php

namespace Drupal\restrict_ip\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\restrict_ip\Service\RestrictIpServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigForm extends ConfigFormBase
{
	/**
	 * The current user
	 *
	 * @var \Drupal\Core\Session\AccountProxyInterface
	 */
	private $currentUser;

	/**
	 * The Module Handler service
	 *
	 * @var \Drupal\Core\Extension\ModuleHandlerInterface
	 */
	protected $moduleHandler;

	/**
	 * The country manager service.
	 *
	 * @var \Drupal\Core\Locale\CountryManagerInterface
	 */
	protected $countryManager;

	/**
	 * The Restrict IP Service
	 *
	 * @var \Drupal\restrict_ip\Service\RestrictIpServiceInterface
	 */
	protected $restrictIpService;

	/**
	 * The IP addresses that have been whitelisted by the module
	 *
	 * @var array
	 */
	protected $whitelistedIpAddresses;

	/**
	 * The page paths that have been whitelisted by the module
	 *
	 * @var array
	 */
	protected $whitelistedPagePaths;

	/**
	 * The page paths that have been blacklisted by the module
	 *
	 * @var array
	 */
	protected $blacklistedPagePaths;

	/**
	 * Constructs a Restrict IP ConfigForm object
	 *
	 * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
	 *   The current user
	 * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
	 *   The Module Handler service
	 * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
	 *   The Country Manager service
	 * @param \Drupal\restrict_ip\Service\RestrictIpServiceInterface $restrictIpService
	 *   The Restrict IP service object
	 */
	public function __construct(AccountProxyInterface $currentUser, ModuleHandlerInterface $moduleHandler, CountryManagerInterface $countryManager, RestrictIpServiceInterface $restrictIpService)
	{
		$this->currentUser = $currentUser;
		$this->moduleHandler = $moduleHandler;
		$this->countryManager = $countryManager;
		$this->restrictIpService = $restrictIpService;

		$this->whitelistedIpAddresses = $this->restrictIpService->getWhitelistedIpAddresses();
		$this->whitelistedPagePaths = $this->restrictIpService->getWhitelistedPagePaths();
		$this->blacklistedPagePaths = $this->restrictIpService->getBlacklistedPagePaths();
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container)
	{
		return new static (
			$container->get('current_user'),
			$container->get('module_handler'),
			$container->get('country_manager'),
			$container->get('restrict_ip.service')
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFormId()
	{
		return 'restrict_ip_config_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEditableConfigNames()
	{
		return [
			'restrict_ip.settings',
		];
	}

	public function buildForm(array $form, FormStateInterface $form_state)
	{
		$config = $this->config('restrict_ip.settings');

		if($this->currentUser->hasPermission('administer permissions'))
		{
			$url = Url::fromRoute('user.admin_permissions');
			$permission_page_link = Link::fromTextAndUrl($this->t('permissions'), $url)->toString();
		}
		else
		{
			$permission_page_link = $this->t('permissions');
		}

		$form['address_description'] = [
			'#markup' => $this->t('Enter the list of allowed IP addresses below'),
			'#prefix' => '<h2>',
			'#suffix' => '</h2>',
		];

		$form['warning'] = [
			'#prefix' => '<p>',
			'#suffix' => '</p>',
			'#markup' => $this->t("Warning: If you enable IP restriction, and don't enter your current IP address into the list, you will immediately be locked out of the system upon save, and will not be able to access the system until you are in a location with an allowed IP address. Alternatively you can allow Restrict IP to be bypassed by role, and set at least one of your roles to be bypassed on the @permissions page.", ['@permissions' =>$permission_page_link]),
		];

		$form['current_ip'] = [
			'#prefix' => '<p><strong>',
			'#suffix' => '</strong></p>',
			'#markup' => $this->t('Your current IP address is: %ip_address', ['%ip_address' => $this->restrictIpService->getCurrentUserIp()]),
		];

		$form['enable'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Enable Restricted IPs'),
			'#description' => $this->t('IP addresses will only be enabled when this option is selected'),
			'#default_value' => $config->get('enable'),
		];

		$form['address_list'] = [
			'#title' => $this->t('Allowed IP Address List'),
			'#description' => $this->t('Enter the list of IP Addresses that are allowed to access the site. Enter one IP address per line, in IPv4 or IPv6 format. You may also enter a range of IPv4 addresses in the format AAA.BBB.CCC.XXX - AAA.BBB.CCC.YYY'),
			'#type' => 'textarea',
			'#default_value' => implode(PHP_EOL, $this->whitelistedIpAddresses),
		];

		$form['mail_address'] = [
			'#title' => $this->t('Email Address'),
			'#type' => 'textfield',
			'#description' => $this->t('If you would like to include a contact email address in the error message that is shown to users that do not have an allowed IP address, enter the email address here.'),
			'#default_value' => $config->get('mail_address'),
		];

		if($this->moduleHandler->moduleExists('dblog'))
		{
			$form['dblog'] = [
				'#title' => $this->t('Log access attempts to watchdog'),
				'#type' => 'checkbox',
				'#default_value' => $config->get('dblog'),
				'#description' => $this->t('When this box is checked, attempts to access the site will be logged to the Drupal log (Recent log entries)'),
			];
		}
		else
		{
			$form['dblog'] = [
				'#type' => 'value',
				'#value' => $config->get('dblog'),
			];
		}

		$form['allow_role_bypass'] = [
			'#title' => $this->t('Allow restrict IP to be bypassed by role'),
			'#type' => 'checkbox',
			'#default_value' => $config->get('allow_role_bypass'),
			'#description' => $this->t('When this box is checked, the permission "Bypass IP Restriction" will become available on the site @permissions page', ['@permissions' => $permission_page_link]),
		];

		$form['bypass_action'] = [
			'#title' => $this->t('Access denied action'),
			'#type' => 'radios',
			'#default_value' => $config->get('bypass_action'),
			'#description' => $this->t('Action to be performed when access is permitted by role, but the user is not logged in.'),
			'#options' => [
				'provide_link_login_page' => $this->t('Provide a link to the login page'),
				'redirect_login_page' => $this->t('Redirect to the login page'),
			],
			'#states' => [
				'visible' => [
					'#edit-allow-role-bypass' => ['checked' => TRUE],
				],
			],
		];

		$form['white_black_list'] = [
			'#type' => 'radios',
			'#options' => [
				$this->t('Check IP addresses on all paths'),
				$this->t('Check IP addresses on all paths except the following'),
				$this->t('Check IP addresses only on the following paths'),
			],
			'#default_value' => $config->get('white_black_list'),
		];

		$form['page_whitelist'] = [
			'#title' => $this->t('Whitelisted pages'),
			'#description' => $this->t("Enter a list of paths that will be allowed regardless of IP address. For example, to not check IP addresses on this page, you would enter <em>admin/config/people/restrict_ip</em>. All paths not included here will be checked. Do not include domain names.  The '*' character is a wildcard. An example path is /user/* for every user page."),
			'#type' => 'textarea',
			'#default_value' => implode(PHP_EOL, $this->whitelistedPagePaths),
			'#states' => [
				'visible' => [
					':input[name="white_black_list"]' => ['value' => 1],
				],
			],
		];

		$form['page_blacklist'] = [
			'#title' => $this->t('Blacklisted pages'),
			'#description' => $this->t("Enter a list of paths on which IP addresses will be checked. For example, to check IP addresses on this page, you would enter <em>admin/config/people/restrict_ip</em>. All paths not included here will not be checked. Do not include domain names.  The '*' character is a wildcard. An example path is /user/* for every user page."),
			'#type' => 'textarea',
			'#default_value' => implode(PHP_EOL, $this->blacklistedPagePaths),
			'#states' => [
				'visible' => [
					':input[name="white_black_list"]' => ['value' => 2],
				],
			],
		];

		if($this->moduleHandler->moduleExists('ip2country'))
		{
			$form['country_white_black_list'] = [
				'#title' => $this->t('Whitelist or blacklist IP addresses by country'),
				'#type' => 'radios',
				'#options' => [
					0 => $this->t('Disabled'),
					1 => $this->t('Whitelist selected countries'),
					2 => $this->t('Blacklist selected countries'),
				],
				'#default_value' => $config->get('country_white_black_list') ? $config->get('country_white_black_list') : 0,
			];

			$form['country_list'] = [
				'#title' => $this->t('Countries'),
				'#type' => 'checkboxes',
				'#options' => $this->countryManager->getList(),
				'#default_value' => explode(':', $config->get('country_list')),
				'#states' => [
					'invisible' => [
						':input[name="country_white_black_list"]' => ['value' => 0],
					],
				],
			];
		}
		else
		{
			$url = Url::fromUri('https://www.drupal.org/project/ip2country');
			$link = Link::FromTextAndUrl($this->t("IP-based Determination of a Visitor's Country"), $url);
			$form['country_white_black_list'] = [
				'#title' => $this->t('Whitelist or blacklist IP addresses by country'),
				'#type' => 'radios',
				'#options' => [
					0 => $this->t('Disabled'),
					1 => $this->t('Whitelist selected countries'),
					2 => $this->t('Blacklist selected countries'),
				],
				'#disabled' => TRUE,
				'#default_value' => 0,
				'#description' => $this->t('Enable the @ip2country module to use this feature', ['@ip2country' => $link->toString()]),
			];
		}

		return parent::buildForm($form, $form_state);
	}

	public function validateForm(array &$form, FormStateInterface $form_state)
	{
		$ip_addresses = $this->restrictIpService->cleanIpAddressInput($form_state->getValue('address_list'));
		if(count($ip_addresses))
		{
			foreach($ip_addresses as $ip_address)
			{
				if($ip_address != '::1')
				{
					// Check if IP address is a valid singular IP address (ie - not a range)
					if(!preg_match('~^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$~', $ip_address) && !preg_match('~^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$~', $ip_address))
					{
						// IP address is not a single IP address, so we need to check if it's a range of addresses
						$pieces = explode('-', $ip_address);
						// We only need to continue checking this IP address
						// if it is a range of addresses
						if(count($pieces) == 2)
						{
							$start_ip = trim($pieces[0]);
							if(!preg_match('~^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$~', $start_ip))
							{
								$form_state->setError($form['restrict_ip_address_list'], $this->t('@ip_address is not a valid IP address.', ['@ip_address' => $start_ip]));
							}
							else
							{
								$start_pieces = explode('.', $start_ip);
								$start_final_chunk = (int) array_pop($start_pieces);
								$end_ip = trim($pieces[1]);
								$end_valid = TRUE;
								if(preg_match('~^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$~', $end_ip))
								{
									$end_valid = TRUE;
									$end_pieces = explode('.', $end_ip);
									for($i = 0; $i < 3; $i++)
									{
										if((int) $start_pieces[$i] != (int) $end_pieces[$i])
										{
											$end_valid = FALSE;
										}
									}
									if($end_valid)
									{
										$end_final_chunk = (int) array_pop($end_pieces);
										if($start_final_chunk > $end_final_chunk)
										{
											$end_valid = FALSE;
										}
									}
								}
								elseif(!is_numeric($end_ip))
								{
									$end_valid = FALSE;
								}
								else
								{
									if($end_ip > 255)
									{
										$end_valid = FALSE;
									}
									else
									{
										$start_final_chunk = array_pop($start_pieces);
										if($start_final_chunk > $end_ip)
										{
											$end_valid = FALSE;
										}
									}
								}

								if(!$end_valid)
								{
									$form_state->setError($form['restrict_ip_address_list'], $this->t('@range is not a valid IP address range.', ['@range' => $ip_address]));
								}
							}
						}
						else
						{
							$form_state->setError($form['restrict_ip_address_list'], $this->t('@ip_address is not a valid IP address or range of addresses.', ['@ip_address' => $ip_address]));
						}
					}
				}
			}
		}

		$page_whitelist = $form_state->getValue('page_whitelist');
		$page_whitelist = trim($page_whitelist);
		if(strlen($page_whitelist))
		{
			$pages = [];
			$paths = explode(PHP_EOL, $page_whitelist);
			foreach($paths as $path)
			{
				$path = trim($path);
				if(strlen($path))
				{
					if(!preg_match('/^\//', $path))
					{
						$path = '/' . $path;
					}

					$pages[] = strtolower($path);
				}
			}

			$form_state->setValue('page_whitelist', $pages);
		}
		else
		{
			$form_state->setValue('page_whitelist', []);
		}

		$page_blacklist = $form_state->getValue('page_blacklist');
		$page_blacklist = trim($page_blacklist);
		if(strlen($page_blacklist))
		{
			$pages = [];
			$paths = explode(PHP_EOL, $page_blacklist);
			foreach($paths as $path)
			{
				$path = trim($path);
				if(strlen($path))
				{
					if(!preg_match('/^\//', $path))
					{
						$path = '/' . $path;
					}

					$pages[] = strtolower($path);
				}
			}

			$form_state->setValue('page_blacklist', $pages);
		}
		else
		{
			$form_state->setValue('page_blacklist', []);
		}
	}
		
	public function submitForm(array &$form, FormStateInterface $form_state)
	{
		if($this->moduleHandler->moduleExists('ip2country'))
		{
			$countries = [];
			foreach($form_state->getValue('country_list') as $country)
			{
				if($country)
				{
					$countries[] = $country;
				}
			}
			$country_list = implode(':', $countries);
		}
		else
		{
			$country_list = '';
		}

		$this->config('restrict_ip.settings')
			->set('enable', (bool) $form_state->getValue('enable'))
			->set('mail_address', (string) $form_state->getValue('mail_address'))
			->set('dblog', (bool) $form_state->getValue('dblog'))
			->set('allow_role_bypass', (bool) $form_state->getValue('allow_role_bypass'))
			->set('bypass_action', (string) $form_state->getValue('bypass_action'))
			->set('white_black_list', (int) $form_state->getValue('white_black_list'))
			->set('country_white_black_list', (int) $form_state->getValue('country_white_black_list'))
			->set('country_list', $country_list)
			->save();

		$this->restrictIpService->saveWhitelistedIpAddresses($this->restrictIpService->cleanIpAddressInput($form_state->getValue('address_list')));
		$this->restrictIpService->saveWhitelistedPagePaths($form_state->getValue('page_whitelist'));
		$this->restrictIpService->saveBlacklistedPagePaths($form_state->getValue('page_blacklist'));

		parent::submitForm($form, $form_state);
	}
}
