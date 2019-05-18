<?php

namespace Drupal\Tests\restrict_ip\Unit\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\restrict_ip\Service\RestrictIpService;

/**
 * @coversDefaultClass \Drupal\restrict_ip\Service\RestrictIpService
 * @group restrict_ip
 */
class RestrictIpServiceTest extends UnitTestCase
{
	protected $currentUser;
	protected $currentPathStack;
	protected $requestStack;
	protected $request;
	protected $mapper;
	protected $pathMatcher;

	/**
	 * {@inheritdoc}
	 */
	public function setUp()
	{
		$this->currentUser = $this->getMockBuilder('Drupal\Core\Session\AccountProxyInterface')
			->disableOriginalConstructor()
			->getMock();

		$this->currentPathStack = $this->getMockBuilder('Drupal\Core\Path\CurrentPathStack')
			->disableOriginalConstructor()
			->getMock();

		$this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
			->disableOriginalConstructor()
			->getMock();

		$this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
			->disableOriginalConstructor()
			->getMock();

		$this->mapper = $this->getMockBuilder('Drupal\restrict_ip\Mapper\RestrictIpMapper')
			->disableOriginalConstructor()
			->getMock();

		$this->mapper->expects($this->any())
			->method('getWhitelistedPaths')
			->willReturn(['/node/1']);

		$this->mapper->expects($this->any())
			->method('getBlacklistedPaths')
			->willReturn(['/node/1']);

		$this->pathMatcher = $this->getMockbuilder('Drupal\Core\Path\PathMatcherInterface')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @covers ::userIsBlocked
	 */
	public function testUserHasRoleBypassPermission()
	{
		$this->currentUser->expects($this->at(0))
			->method('hasPermission')
			->with('bypass ip restriction')
			->willReturn(TRUE);

		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn('/restricted/path');

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);

		$configFactory = $this->getConfigFactory(['allow_role_bypass' => TRUE]);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);

		$user_is_blocked = $restrictIpService->userIsBlocked();
		$this->assertFalse($user_is_blocked, 'User is not blocked when they have the permission bypass access restriction');
	}
	
	/**
	 * @covers ::userIsBlocked
	 * @dataProvider pathInAllowedPathsDataProvider
	 */
	public function testPathInAllowedPaths($path, $expectedResult)
	{
		$this->currentUser->expects($this->at(0))
			->method('hasPermission')
			->willReturn(FALSE);

		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn($path);

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);

		$configFactory = $this->getConfigFactory(['allow_role_bypass' => TRUE]);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);

		$user_is_blocked = $restrictIpService->userIsBlocked();
		$this->assertSame($expectedResult, $user_is_blocked, 'User is not blocked when they are on the allowed path: ' . $path);
	}

	/**
	 * Data provider for testPathInAllowedPaths()
	 */
	public function pathInAllowedPathsDataProvider()
	{
		return [
			['/user', FALSE],
			['/user/login', FALSE],
			['/user/password', FALSE],
			['/user/logout', FALSE],
			['/user/reset/something', FALSE],
			['/invalid/path', NULL],
		];
	}

	/**
	 * @covers ::testForBlock
	 * @dataProvider whitelistDataProvider
	 */
	public function testWhitelist($pathToCheck, $pathAllowed, $expectedResult, $message)
	{
		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn($pathToCheck);

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);

		$configFactory = $this->getConfigFactory([
			'enable' => TRUE,
			'white_black_list' => 1,
		]);

		$this->pathMatcher->expects($this->at(0))
			->method('matchPath')
			->willReturn($pathAllowed);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);

		$restrictIpService->testForBlock(TRUE);

		$this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
	}

	/**
	 * Data provider for testWhitelist()
	 */
	public function whitelistDataProvider()
	{
		return [
			['/node/1', TRUE, FALSE, 'User is allowed on whitelisted path'],
			['/node/2', FALSE, TRUE, 'User is blocked on non-whitelisted path'],
		];
	}

	/**
	 * @covers ::testForBlock
	 * @dataProvider blacklistDataProvider
	 */
	public function testBlacklist($pathToCheck, $pathNotAllowed, $expectedResult, $message)
	{
		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn($pathToCheck);

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);

		$configFactory = $this->getConfigFactory([
			'enable' => TRUE,
			'white_black_list' => 2,
		]);

		$this->pathMatcher->expects($this->at(0))
			->method('matchPath')
			->willReturn($pathNotAllowed);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);
		$restrictIpService->testForBlock(TRUE);

		$this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
	}

	/**
	 * Data provider for testBlacklist()
	 */
	public function blacklistDataProvider()
	{
		return [
			['/node/1', TRUE, TRUE, 'User is blocked on blacklisted path'],
			['/node/2', FALSE, FALSE, 'User is not blocked on non-blacklisted path'],
		];
	}

	/**
	 * @covers ::testForBlock
	 * @dataProvider whitelistedIpAddressesTestDataProvider
	 */
	public function testWhitelistedIpAddresses($ipAddressToCheck, $expectedResult, $message)
	{
		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn('/some/path');

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn($ipAddressToCheck);

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);

		$configFactory = $this->getConfigFactory([
			'enable' => TRUE,
			'white_black_list' => 0,
		]);

		$mapper = $this->getMockBuilder('Drupal\restrict_ip\Mapper\RestrictIpMapper')
			->disableOriginalConstructor()
			->getMock();

		$mapper->expects($this->any())
			->method('getWhitelistedIpAddresses')
			->willReturn(['::1']);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $mapper, $this->pathMatcher);
		$restrictIpService->testForBlock(TRUE);

		$this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
	}

	/**
	 * Data provider for testWhitelistedIpAddresses()
	 */
	public function whitelistedIpAddressesTestDataProvider()
	{
		return [
			['::1', FALSE, 'User is not blocked when IP address has been whitelisted in through admin interface'],
			['::2', TRUE, 'User is blocked when IP address has not been whitelisted in through admin interface'],
		];
	}

	/**
	 * @covers ::testForBlock
	 * @dataProvider settingsIpAddressesDataProvider
	 */
	public function testSettingsIpAddresses($ipAddressToCheck, $configFactory, $expectedResult, $message)
	{
		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn('/some/path');

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);
		$restrictIpService->testForBlock(TRUE);

		$this->assertSame($expectedResult, $restrictIpService->userIsBlocked(), $message);
	}

	/**
	 * Data provider for testSettingsIpAddresses()
	 */
	public function settingsIpAddressesDataProvider()
	{
		return [
			['::1', $this->getConfigFactory([
				'enable' => TRUE,
				'white_black_list' => 0,
				'ip_whitelist' => ['::1'],
			]), FALSE, 'User is not blocked when IP address has been whitelisted in settings.php'],
			['::1', $this->getConfigFactory([
				'enable' => TRUE,
				'white_black_list' => 0,
				'ip_whitelist' => ['::2'],
			]), TRUE, 'User is blocked when IP address has not been whitelisted through settings.php'],
		];
	}

	/**
	 * @covers ::cleanIpAddressInput
	 * @dataProvider cleanIpAddressInputDataProvider
	 */
	public function testCleanIpAddressInput($input, $expectedResult, $message)
	{
		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn('/some/path');

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);
		
		$configFactory = $this->getConfigFactory([]);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);

		$this->assertSame($expectedResult, $restrictIpService->cleanIpAddressInput($input), $message);
	}

	/**
	 * Data provider for testCleanIpAddressInput()
	 */
	public function cleanIpAddressInputDataProvider()
	{
		return [
			['111.111.111.111
			111.111.111.112',
			['111.111.111.111', '111.111.111.112'],
			'Items properly parsed when separated by new lines'],
			['// This is a comment
			111.111.111.111',
			['111.111.111.111'],
			'Items properly parsed when comment starting with // exists'],
			['# This is a comment
			111.111.111.111',
			['111.111.111.111'],
			'Items properly parsed when comment starting with # exists'],
			['/**
			 *This is a comment
			 */
			111.111.111.111',
			['111.111.111.111'],
			'Items properly parsed when multiline comment exists'],
		];
	}

	/**
	 * @covers ::getCurrentUserIp
	 */
	public function testGetCurrentUserIp()
	{
		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn('/some/path');

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);
		
		$configFactory = $this->getConfigFactory([]);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);

		$this->assertSame('::1', $restrictIpService->getCurrentUserIp(), 'User IP address is properly reported');
	}

	/**
	 * @covers ::getCurrentPath
	 */
	public function testGetCurrentPath()
	{
		$this->currentPathStack->expects($this->at(0))
			->method('getPath')
			->willReturn('/some/path');

		$this->request->expects($this->at(0))
			->method('getClientIp')
			->willReturn('::1');

		$this->requestStack->expects($this->at(0))
			->method('getCurrentRequest')
			->willReturn($this->request);
		
		$configFactory = $this->getConfigFactory([]);

		$restrictIpService = New RestrictIpService($this->currentUser, $this->currentPathStack, $configFactory, $this->requestStack, $this->mapper, $this->pathMatcher);

		$this->assertSame('/some/path', $restrictIpService->getCurrentPath(), 'Correct current path is properly reported');
	}

	private function getConfigFactory(array $settings)
	{
		return $this->configFactory = $this->getConfigFactoryStub([
			'restrict_ip.settings' => $settings,
		]);
	}
}
