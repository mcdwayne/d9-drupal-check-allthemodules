<?php

namespace Drupal\Tests\restrict_ip\Functional;

/**
 * @group restrict_ip
 */
class RestrictIpAccessTest extends RestrictIpBrowserTestBase
{
	/**
	 * Modules to enable.
	 *
	 * @var array
	 */
	protected static $modules = ['restrict_ip', 'node'];

	/**
	 * Test that a user is blocked when the module is enabled
	 */
	public function testModuleEnabled()
	{
		$adminUser = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);

		$this->drupalLogin($adminUser);
		$this->drupalGet('admin/config/people/restrict_ip');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('#edit-enable');
		$this->click('#edit-submit');
		$this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');
	}

	/**
	 * Test that a user is not blocked if their IP address is whitelisted when the module is enabled
	 */
	public function testIpWhitelist()
	{
		$adminUser = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);

		$this->drupalLogin($adminUser);
		$this->drupalGet('admin/config/people/restrict_ip');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('#edit-enable');
		$this->fillTextValue('edit-address-list', $_SERVER['REMOTE_ADDR'] . PHP_EOL . '::1');
		$this->click('#edit-submit');
		$this->assertSession()->pageTextNotContains('The page you are trying to access cannot be accessed from your IP address.');

		return $adminUser;
	}

	public function testEmailAddressDisplays()
	{
		$adminUser = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);

		$this->drupalLogin($adminUser);
		$this->drupalGet('admin/config/people/restrict_ip');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('#edit-enable');
		$this->fillTextValue('edit-mail-address', 'dave@example.com');
		$this->click('#edit-submit');

		$this->assertSession()->pageTextContains('dave[at]example.com');
	}

	public function testAccessBypassByRole()
	{
		$adminUser = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);

		$this->createArticleContentType();
		$this->createArticle();

		$this->drupalLogin($adminUser);

		$this->drupalGet('admin/config/people/restrict_ip');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('#edit-allow-role-bypass');
		$this->click('#edit-submit');

		$admin_role = $this->createRole(['bypass ip restriction']);
		$adminUser->addRole($admin_role);
		$this->checkCheckbox('#edit-enable');
		$this->click('#edit-submit');

		$this->drupalLogout();
		$this->drupalGet('node/1');
		$this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');
		$this->assertSession()->linkExists('Sign in');

		$this->drupalGet('user');
		$this->assertStatusCodeEquals(200);
		$this->drupalGet('user/login');
		$this->assertStatusCodeEquals(200);
		$this->drupalGet('user/password');
		$this->assertStatusCodeEquals(200);
		$this->drupalGet('user/register');
		$this->assertStatusCodeEquals(200);
		$this->drupalGet('user/reset/1');
		$this->assertStatusCodeEquals(403);
	}

	public function testRedirectToLoginWhenBypassByRoleEnabled()
	{
		$adminUser = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);

		$this->createArticleContentType();
		$this->createArticle();

		$this->drupalLogin($adminUser);

		$this->drupalGet('admin/config/people/restrict_ip');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('#edit-allow-role-bypass');
		$this->selectRadio('#edit-bypass-action-redirect-login-page');
		$this->click('#edit-submit');

		$admin_role = $this->createRole(['bypass ip restriction']);
		$adminUser->addRole($admin_role);
		$this->checkCheckbox('#edit-enable');
		$this->click('#edit-submit');

		$this->drupalLogout();
		$this->drupalGet('node/1');
		$this->assertElementExists('#edit-name');
	}

	public function testWhitelistedPaths()
	{
		$adminUser = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);

		$this->drupalLogin($adminUser);

		$this->createArticleContentType();
		$this->createArticle();
		$this->createArticle();

		$this->drupalGet('admin/config/people/restrict_ip');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('#edit-enable');
		$this->selectRadio('#edit-white-black-list-1');
		$this->fillTextValue('edit-page-whitelist', 'node/1');
		$this->click('#edit-submit');

		$this->drupalGet('node/1');
		$this->assertSession()->pageTextNotContains('The page you are trying to access cannot be accessed from your IP address.');

		$this->drupalGet('node/2');
		$this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');
	}

	public function testBlacklistedPaths()
	{
		$adminUser = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);

		$this->drupalLogin($adminUser);

		$this->createArticleContentType();
		$this->createArticle();
		$this->createArticle();

		$this->drupalGet('admin/config/people/restrict_ip');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('#edit-enable');
		$this->selectRadio('#edit-white-black-list-2');
		$this->fillTextValue('edit-page-blacklist', 'node/1');
		$this->click('#edit-submit');

		$this->drupalGet('node/1');
		$this->assertSession()->pageTextContains('The page you are trying to access cannot be accessed from your IP address.');

		$this->drupalGet('node/2');
		$this->assertSession()->pageTextNotContains('The page you are trying to access cannot be accessed from your IP address.');
	}

	private function createArticleContentType()
	{
		$type = $this->container->get('entity_type.manager')->getStorage('node_type')
			->create([
				'type' => 'article',
				'name' => 'Article',
			]);

		$type->save();
	}

	private function createArticle()
	{
		static $counter;

		if(!$counter)
		{
			$counter = 1;
		}

		$node = $this->container->get('entity.manager')->getStorage('node')
			->create([
				'type' => 'article',
				'title' => 'Article ' . $counter,
			]);

		$node->save();

		$this->container->get('router.builder')->rebuild();

		$counter += 1;
	}
}
