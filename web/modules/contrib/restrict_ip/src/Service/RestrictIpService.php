<?php

namespace Drupal\restrict_ip\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\restrict_ip\Mapper\RestrictIpMapperInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RestrictIpService implements RestrictIpServiceInterface
{
	/**
	 * Indicates whether or not the user should be blocked
	 *
	 * @var bool
	 */
	private $blocked;

	/**
	 * The current user
	 *
	 * @var \Drupal\Core\Session\AccountProxyInterface
	 */
	protected $currentUser;

	/**
	 * The current path
	 *
	 * @var string
	 */
	protected $currentPath;

	/**
	 * The Restrict IP configuration settings
	 *
	 * @var \Drupal\Core\Config\ImmutableConfig
	 */
	protected $config;

	/**
	 * The current user's IP address
	 *
	 * @var string
	 */
	private $currentUserIp;

	/**
	 * The Restrict IP data mapper
	 *
	 * @var \Drupal\restrict_ip\Mapper\RestrictIpMapperInterface
	 */
	protected $mapper;

	/**
	 * The Path Matcher service
	 *
	 * @var \Drupal\Core\Path\PathMatcherInterface
	 */
	protected $pathMatcher;

	/**
	 * The Module Handler service
	 *
	 * @var \Drupal\Core\Extension\ModuleHandlerInterface
	 */
	protected $moduleHandler;

	/**
	 * The User Data service
	 *
	 * @var \Drupal\user\UserDataInterface
	 */
	protected $userData;

	/**
	 * Constructs a RestrictIpService object
	 *
	 * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
	 *   The current user
	 * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
	 *   The current path stack
	 * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
	 *   The Config Factory service
	 * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
	 *   The current HTTP request
	 * @param \Drupal\restrict_ip\Mapper\RestrictIpMapperInterface $restrictIpMapper
	 *   The Restrict IP data mapper object
	 * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
	 *   The Path Matcher service
	 * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
	 *   The Module Handler service
	 * @param \Drupal\user\UserDataInterface $userData
	 *   The User Data service
	 */
	public function __construct(AccountProxyInterface $currentUser, CurrentPathStack $currentPathStack, ConfigFactoryInterface $configFactory, RequestStack $requestStack, RestrictIpMapperInterface $restrictIpMapper, PathMatcherInterface $pathMatcher, ModuleHandlerInterface $moduleHandler, UserDataInterface $userData = NULL)
	{
		$this->currentUser = $currentUser;
		$this->mapper = $restrictIpMapper;
		$this->pathMatcher = $pathMatcher;
		$this->moduleHandler = $moduleHandler;
		$this->userData = $userData;

		$this->currentPath = strtolower($currentPathStack->getPath());
		$this->config = $configFactory->get('restrict_ip.settings');
		$this->currentUserIp = $requestStack->getCurrentRequest()->getClientIp();
	}

	/**
	 * {@inheritdoc}
	 */
	public function userIsBlocked()
	{
		if($this->allowAccessByPermission())
		{
			return FALSE;
		}

		return $this->blocked;
	}

	/**
	 * {@inheritdoc}
	 */
	public function testForBlock($runInCli = FALSE)
	{
		$this->blocked = FALSE;

		if($this->config->get('enable'))
		{
			$this->blocked = TRUE;

			// We don't want to check IP on CLI (likely drush) requests
			// unless explicitly declared to check by the $runInCli argument
			if(PHP_SAPI != 'cli' || $runInCli)
			{
				$access_denied = TRUE;
				if($this->allowAccessWhitelistedPath())
				{
					$access_denied = FALSE;
				}
				elseif($this->allowAccessBlacklistedPath())
				{
					$access_denied = FALSE;
				}
				elseif($this->allowAccessWhitelistedIp())
				{
					$access_denied = FALSE;
				}
				elseif($this->moduleHandler->moduleExists('ip2country'))
				{
					if($this->allowAccessWhitelistCountry())
					{
						$access_denied = FALSE;
					}
					elseif($this->allowAccessBlacklistCountry())
					{
						$access_denied = FALSE;
					}
				}

				// If the user has been denied access
				if($access_denied)
				{
					if(PHP_SAPI != 'cli')
					{
						$_SESSION['restrict_ip'] = TRUE;
					}
				}
				else
				{
					$this->blocked = FALSE;
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function cleanIpAddressInput($input)
	{
		$ip_addresses = trim($input);
		$ip_addresses = preg_replace('/(\/\/|#).+/', '', $ip_addresses);
		$ip_addresses = preg_replace('~/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/~', '', $ip_addresses);

		$addresses = explode(PHP_EOL, $ip_addresses);

		$return = [];
		foreach($addresses as $ip_address)
		{
			$trimmed = trim($ip_address);
			if(strlen($trimmed))
			{
				$return[] = $trimmed;
			}
		}

		return $return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCurrentUserIp()
	{
		return $this->currentUserIp;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCurrentPath()
	{
		return $this->currentPath;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getWhitelistedIpAddresses()
	{
		$ip_addresses = $this->mapper->getWhitelistedIpAddresses();

		return is_array($ip_addresses) ? $ip_addresses : [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveWhitelistedIpAddresses(array $ipAddresses, $overwriteExisting = TRUE)
	{
		$this->mapper->saveWhitelistedIpAddresses($ipAddresses, $overwriteExisting);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getWhitelistedPagePaths()
	{
		$whitelisted_paths = $this->mapper->getWhitelistedPaths();

		return is_array($whitelisted_paths) ? $whitelisted_paths : [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveWhitelistedPagePaths(array $whitelistedPaths, $overwriteExisting = TRUE)
	{
		$this->mapper->saveWhitelistedPaths($whitelistedPaths, $overwriteExisting);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlacklistedPagePaths()
	{
		$blacklisted_paths = $this->mapper->getBlacklistedPaths();

		return is_array($blacklisted_paths) ? $blacklisted_paths : [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveBlacklistedPagePaths(array $blacklistedPaths, $overwriteExisting = TRUE)
	{
		$this->mapper->saveBlacklistedPaths($blacklistedPaths, $overwriteExisting);
	}

    /**
     * Get the current user's country
     *
     * @return string
     *   The two-letter country code for the given IP address
     */
    protected function ip2CountryGetCurrentUserCountry()
    {
		return ip2country_get_country($this->currentUserIp);
    }

	/**
	 * Test to see if access should be granted based on 
	 */
	private function allowAccessByPermission()
	{
		static $allow_access;

		if(is_null($allow_access))
		{
			$allow_access = [];
		}

		if(!isset($allow_access[$this->currentPath]))
		{
			$allow_access[$this->currentPath] = FALSE;

			if($this->config->get('allow_role_bypass'))
			{
				$current_path = $this->currentPath;
				if($this->currentUser->hasPermission('bypass ip restriction') || in_array($current_path, array('/user', '/user/login', '/user/password', '/user/logout', '/user/register')) || strpos($current_path, '/user/reset/') === 0)
				{
					$allow_access[$this->currentPath] = TRUE;
				}
			}
		}

		return $allow_access[$this->currentPath];
	}

	/**
	 * Test if the current path is allowed based on whitelist settings
	 */
	private function allowAccessWhitelistedPath()
	{
		$allow_access = FALSE;
		if($this->config->get('white_black_list') == 1)
		{
			$whitelisted_pages = $this->getWhitelistedPagePaths();
			$current_whitelist = FALSE;

			if(count($whitelisted_pages))
			{
				foreach($whitelisted_pages as $whitelisted_page)
				{
					if($this->pathMatcher->matchPath($this->currentPath, $whitelisted_page))
					{
						$current_whitelist = TRUE;
					}
				}
			}

			if($current_whitelist)
			{
				$allow_access = TRUE;
			}
		}

		return $allow_access;
	}

	/**
	 * Test if the current path is allowed based on blacklist settings
	 */
	private function allowAccessBlacklistedPath()
	{
		$allow_access = FALSE;
		if($this->config->get('white_black_list') == 2)
		{
			$blacklisted_pages = $this->getBlacklistedPagePaths();
			$current_blacklist = FALSE;

			if(count($blacklisted_pages))
			{
				foreach($blacklisted_pages as $blacklisted_page)
				{
					if($this->pathMatcher->matchPath($this->currentPath, $blacklisted_page))
					{
						$current_blacklist = TRUE;
					}
				}
			}

			if(!$current_blacklist)
			{
				$allow_access = TRUE;
			}
		}

		return $allow_access;
	}

	/**
	 * Test to see if the current user has a whitelisted IP address
	 */
	private function allowAccessWhitelistedIp()
	{
		$ip_whitelist = $this->buildWhitelistedIpAddresses();

		if(count($ip_whitelist))
		{
			foreach($ip_whitelist as $whitelisted_address)
			{
				if($this->testWhitelistedIp($whitelisted_address))
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Test to see if the current user is whitelisted based on their country
	 */
	private function allowAccessWhitelistCountry()
	{
		$allow_access = FALSE;
		if($this->config->get('country_white_black_list') == 1)
		{
			$country_code = $this->ip2CountryGetCurrentUserCountry();

			if($country_code)
			{
				$countries = explode(':', $this->config->get('country_list'));

				return in_array(strtoupper($country_code), $countries);
			}
		}

		return FALSE;
	}

	/**
	 * Test to see if the current user is blacklist based on their country
	 */
	private function allowAccessBlacklistCountry()
	{
		$allow_access = FALSE;
		if($this->config->get('country_white_black_list') == 2)
		{
			$country_code = $this->ip2CountryGetCurrentUserCountry();
			if($country_code)
			{
				$countries = explode(':', $this->config->get('country_list'));

				return !in_array(strtoupper($country_code), $countries);
			}
		}

		return FALSE;
	}

	/**
	 * Build an array of whitelisted IP addresses based on site settings
	 */
	private function buildWhitelistedIpAddresses()
	{
		// Get the value saved to the system, and turn it into an array of IP addresses.
		$ip_addresses = $this->getWhitelistedIpAddresses();

		// Add any whitelisted IPs from the settings.php file to the whitelisted array
		$ip_whitelist = $this->config->get('ip_whitelist');
		if(count($ip_whitelist))
		{
			$ip_addresses = array_merge($ip_addresses, $ip_whitelist);
		}

		return $ip_addresses;
	}

	/**
	 * Test an ip address to see if the current user should be whitelisted based on
	 * that address.
	 *
	 * @param $whitelisted_address
	 *   The address to check
	 *
	 * @return bool
	 *   TRUE if the user should be allowed access based on the current IP
	 *   FALSE if they should not be allowed access based on the current IP
	 */
	private function testWhitelistedIp($whitelisted_ip)
	{
		// Check if the given IP address matches the current user
		if($whitelisted_ip == $this->getCurrentUserIp())
		{
			return TRUE;
		}

		$pieces = explode('-', $whitelisted_ip);
		// We only need to continue checking this IP address
		// if it is a range of addresses
		if(count($pieces) == 2)
		{
			$start_ip = $pieces[0];
			$end_ip = $pieces[1];
			$start_pieces = explode('.', $start_ip);
			// If there are not 4 sections to the IP then its an invalid
			// IPv4 address, and we don't need to continue checking
			if(count($start_pieces) === 4)
			{
				$user_pieces = explode('.', $this->currentUserIp);
				// We compare the first three chunks of the first IP address
				// With the first three chunks of the user's IP address
				// If they are not the same, then the IP address is not within
				// the range of IPs
				for($i = 0; $i < 3; $i++)
				{
					if((int) $user_pieces[$i] !== (int) $start_pieces[$i])
					{
						// One of the chunks has failed, so we can stop
						// checking this range
						return FALSE;
					}
				}

				// The first three chunks have past testing, so now we check the
				// range given to see if the final chunk is in this range
				// First we get the start of the range
				$start_final_chunk = (int) array_pop($start_pieces);
				$end_pieces = explode('.', $end_ip);
				// Then we get the end of the range. This will work
				// whether the user has entered XXX.XXX.XXX.XXX - XXX.XXX.XXX.XXX
				// or XXX.XXX.XXX.XXX-XXX
				$end_final_chunk = (int) array_pop($end_pieces);
				// Now we get the user's final chunk
				$user_final_chunk = (int) array_pop($user_pieces);
				// And finally we check to see if the user's chunk lies in that range
				if($user_final_chunk >= $start_final_chunk && $user_final_chunk <= $end_final_chunk)
				{
					// The user's IP lies in the range, so we don't grant access
					return TRUE;
				}
			}
		}

		return FALSE;
	}
}
