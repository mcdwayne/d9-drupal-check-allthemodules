<?php

namespace Drupal\Tests\restrict_ip\Functional;

/**
 * @group restrict_ip
 */
class RestrictIpAdminUiTest extends RestrictIpBrowserTestBase
{
	/**
	 * Modules to enable.
	 *
	 * @var array
	 */
	protected static $modules = ['restrict_ip'];

	/**
	 * Test that the admin UI page is properly linked to, that all the required elements
	 * exist, and that the form is working properly
	 */
	public function testRestrictIpAdminPage()
	{
		$account = $this->drupalCreateUser(['administer restricted ip addresses', 'access administration pages', 'administer modules']);
		$this->drupalLogin($account);

		// Test link exists on admin page (restrict_ip.links.menu.yml)
		$this->drupalGet('admin/config');
		$this->assertStatusCodeEquals(200);
		$this->assertSession()->pageTextContains('Administer whitelisted IP addresses and related settings');
		$this->clickLink('Restrict IP');

		// Test admin page exists
		$this->assertSession()->addressMatches('/\/admin\/config\/people\/restrict_ip$/');
		$this->assertStatusCodeEquals(200);
		$this->assertSession()->pageTextContains('Enter the list of allowed IP addresses below');

		// Test that enable checkbox exists
		$this->assertElementExists('#edit-enable');
		$this->assertElementAttributeExists('#edit-enable', 'type');
		$this->assertElementAttributeContains('#edit-enable', 'type', 'checkbox');

		// Test that enable Allowed IP address textarea exists
		$this->assertElementExists('#edit-address-list');
		$this->assertElementAttributeExists('#edit-address-list', 'class');
		$this->assertElementAttributeContains('#edit-address-list', 'class', 'form-textarea');

		// Test that enable Allowed IP address textarea exists
		$this->assertElementExists('#edit-mail-address');
		$this->assertElementAttributeExists('#edit-mail-address', 'type');
		$this->assertElementAttributeContains('#edit-mail-address', 'type', 'text');

		// Test that the log accessess attempts checkbox doesn't exist
		$this->assertSession()->elementNotExists('css', '#edit-dblog');

		// Enable dblog module
		$this->drupalGet('admin/modules');
		$this->assertStatusCodeEquals(200);
		$this->checkCheckbox('edit-modules-core-dblog-enable');
		$this->click('#edit-submit');
		$this->drupalGet('admin/config/people/restrict_ip');

		// Test that the log accessess attempts checkbox now exists
		$this->assertElementExists('#edit-dblog');
		$this->assertElementAttributeExists('#edit-dblog', 'type');
		$this->assertElementAttributeContains('#edit-dblog', 'type', 'checkbox');

		// Test that allow role bypass checkbox exists
		$this->assertElementExists('#edit-allow-role-bypass');
		$this->assertElementAttributeExists('#edit-allow-role-bypass', 'type');
		$this->assertElementAttributeContains('#edit-allow-role-bypass', 'type', 'checkbox');

		// Test that provide link to login page radio exists
		$this->assertElementExists('#edit-bypass-action-provide-link-login-page');
		$this->assertElementAttributeExists('#edit-bypass-action-provide-link-login-page', 'type');
		$this->assertElementAttributeContains('#edit-bypass-action-provide-link-login-page', 'type', 'radio');

		// Test that redirect to login page radio exists
		$this->assertElementExists('#edit-bypass-action-redirect-login-page');
		$this->assertElementAttributeExists('#edit-bypass-action-redirect-login-page', 'type');
		$this->assertElementAttributeContains('#edit-bypass-action-redirect-login-page', 'type', 'radio');

		// Test that check IP addresses on all paths radio exists
		$this->assertElementExists('#edit-white-black-list-0');
		$this->assertElementAttributeExists('#edit-white-black-list-0', 'type');
		$this->assertElementAttributeContains('#edit-white-black-list-0', 'type', 'radio');

		// Test that check IP addresses on whitelisted paths radio exists
		$this->assertElementExists('#edit-white-black-list-1');
		$this->assertElementAttributeExists('#edit-white-black-list-1', 'type');
		$this->assertElementAttributeContains('#edit-white-black-list-1', 'type', 'radio');

		// Test that check IP addresses on blacklisted paths radio exists
		$this->assertElementExists('#edit-white-black-list-2');
		$this->assertElementAttributeExists('#edit-white-black-list-2', 'type');
		$this->assertElementAttributeContains('#edit-white-black-list-2', 'type', 'radio');

		// Test that whitelisted pages textarea exists
		$this->assertElementExists('textarea#edit-page-whitelist');
		
		// Test that blacklisted pages textarea exists
		$this->assertElementExists('textarea#edit-page-blacklist');

		// Fill in form values and submit
		$this->fillTextValue('edit-address-list', '// Address 1' . PHP_EOL . '1.1.1.1' . PHP_EOL . '# Address 2' . PHP_EOL . '2.2.2.2' . PHP_EOL . '/**' . PHP_EOL . ' * Address 3' . PHP_EOL . ' */' . PHP_EOL . '3.3.3.3');
		$this->fillTextValue('edit-mail-address', 'dave@example.com');
		$this->checkCheckbox('edit-dblog');
		$this->checkCheckbox('edit-allow-role-bypass');
		$this->selectRadio('edit-bypass-action-redirect-login-page');
		$this->selectRadio('edit-white-black-list-1');
		$this->fillTextValue('edit-page-whitelist', 'page/1' . PHP_EOL . '/page/2');
		$this->fillTextValue('edit-page-blacklist', 'page/3' . PHP_EOL . '/page/4');
		$this->click('#edit-submit');

		// Check form values contain proper values
		$this->assertSession()->elementExists('css', '#edit-address-list');
		$this->assertTextValue('edit-address-list', '1.1.1.1' . PHP_EOL . '2.2.2.2' . PHP_EOL . '3.3.3.3');
		$this->assertTextValue('edit-mail-address', 'dave@example.com');
		$this->assertCheckboxChecked('edit-dblog');
		$this->assertCheckboxChecked('edit-allow-role-bypass');
		$this->assertRadioSelected('edit-bypass-action-redirect-login-page');
	}
}
