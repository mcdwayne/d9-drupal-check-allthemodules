<?php

namespace Drupal\Tests\fancy_login\FunctionalJavascript;

use Drupal\fancy_login\TestBase\FancyLoginJavascriptTestBase;
use Drupal\user\Entity\Role;

/**
 * @group fancy_login
 */
class FancyLoginJavascriptTest extends FancyLoginJavascriptTestBase
{
	/**
	 * The admin user used for various tasks
	 *
	 * @var \Drupal\user\Entity\User
	 */
	protected $adminUser;

	/**
	 * {@inheritdoc}
	 */
	public static $modules = ['fancy_login', 'block', 'node'];

	public function setUp()
	{
		parent::setUp();

		$block = $this->placeBlock('fancy_login_login_block');
		$this->createcontentType(['type' => 'article']);
		$node = $this->createNode(['title' => 'Article 1', 'type' => 'article']);
		$anonymous_role = Role::load('anonymous');
		$this->grantPermissions($anonymous_role, ['access content']);
		$this->drupalGet(drupal_get_path('module', 'fancy_login') . '/js/fancy_login.js');
		$this->assertStatusCodeEquals(200);
		$this->drupalGet('/node/1');
		$this->assertStatusCodeEquals(200);
		$this->assertElementExistsXpath('//div[@id="block-' . $block->id() . '"]//a[@href="/user/login" and text()="Login"]');

		$this->openPopup();
	}

	public function testDimmerBackgroundColor()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		$script = $this->createScriptString('#fancy_login_dim_screen', 'background-color', 'rgb(255, 255, 255)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('background-color is not white');
		}

		$this->goToConfigPage();
		$this->getSession()->getPage()->find('xpath', '//summary[@role="button" and text()="Display"]')->click();
		$this->fillTextValue('#edit-screen-fade-color', '#000000');
		$this->click('#edit-actions .form-submit');

		drupal_flush_all_caches();

		$this->drupalLogout();
		$this->openPopup();

		$script = $this->createScriptString('#fancy_login_dim_screen', 'background-color', 'rgb(0, 0, 0)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('background-color is not black');
		}
	}

	public function testDimmerZIndex()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		$selector = '#fancy_login_dim_screen';
		$property = 'z-index';

		$script = $this->createScriptString($selector, $property, '10');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('z-index is not 10');
		}

		$this->goToConfigPage();
		$this->getSession()->getPage()->find('xpath', '//summary[@role="button" and text()="Display"]')->click();
		$this->fillTextValue('#edit-screen-fade-z-index', '12');
		$this->click('#edit-actions .form-submit');

		drupal_flush_all_caches();

		$this->drupalLogout();
		$this->openPopup();

		$script = $this->createScriptString($selector, $property, '12');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('z-index is not 12');
		}
	}

	public function testLoginBoxBackgroundColor()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		$selector = '#fancy_login_login_box';
		$property = 'background-color';

		$script = $this->createScriptString($selector, $property, 'rgb(255, 255, 255)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box background color is not white');
		}

		$this->goToConfigPage();
		$this->getSession()->getPage()->find('xpath', '//summary[@role="button" and text()="Display"]')->click();
		$this->fillTextValue('#edit-login-box-background-color', '#000000');
		$this->click('#edit-actions .form-submit');

		drupal_flush_all_caches();

		$this->drupalLogout();
		$this->openPopup();

		$script = $this->createScriptString($selector, $property, 'rgb(0, 0, 0)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box background color is not black');
		}
	}

	public function testLoginBoxTextColor()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		$selector = '#fancy_login_login_box';
		$property = 'color';

		$script = $this->createScriptString($selector, $property, 'rgb(0, 0, 0)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box text color is not black');
		}

		$this->goToConfigPage();
		$this->getSession()->getPage()->find('xpath', '//summary[@role="button" and text()="Display"]')->click();
		$this->fillTextValue('#edit-login-box-text-color', '#FFFFFF');
		$this->click('#edit-actions .form-submit');

		drupal_flush_all_caches();

		$this->drupalLogout();
		$this->openPopup();

		$script = $this->createScriptString($selector, $property, 'rgb(255, 255, 255)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box text color is not white');
		}
	}

	public function testLoginBoxBorderColor()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		$selector = '#fancy_login_login_box';
		$property = 'border-color';

		$script = $this->createScriptString($selector, $property, 'rgb(0, 0, 0)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box border color is not black');
		}

		$this->goToConfigPage();
		$this->getSession()->getPage()->find('xpath', '//summary[@role="button" and text()="Display"]')->click();
		$this->fillTextValue('#edit-login-box-border-color', '#FFFFFF');
		$this->click('#edit-actions .form-submit');

		drupal_flush_all_caches();

		$this->drupalLogout();
		$this->openPopup();

		$script = $this->createScriptString($selector, $property, 'rgb(255, 255, 255)');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box text color is not white');
		}
	}

	public function testLoginBoxBorderWidth()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		$selector = '#fancy_login_login_box';
		$property = 'border-width';

		$script = $this->createScriptString($selector, $property, '3px');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box border width is not 3px');
		}

		$this->goToConfigPage();
		$this->getSession()->getPage()->find('xpath', '//summary[@role="button" and text()="Display"]')->click();
		$this->fillTextValue('#edit-login-box-border-width', '5px');
		$this->click('#edit-actions .form-submit');

		drupal_flush_all_caches();

		$this->drupalLogout();
		$this->openPopup();

		$script = $this->createScriptString($selector, $property, '5px');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box text color is not 5px');
		}
	}

	public function testLoginBoxBorderStyle()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		$selector = '#fancy_login_login_box';
		$property = 'border-style';

		$script = $this->createScriptString($selector, $property, 'solid');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box border width is not solid');
		}

		$this->goToConfigPage();
		$this->getSession()->getPage()->find('xpath', '//summary[@role="button" and text()="Display"]')->click();
		$this->fillTextValue('#edit-login-box-border-style', 'dotted');
		$this->click('#edit-actions .form-submit');

		drupal_flush_all_caches();

		$this->drupalLogout();
		$this->openPopup();

		$script = $this->createScriptString($selector, $property, 'dotted');
		if(!$this->getSession()->evaluateScript($script))
		{
			throw new \Exception('Login box text color is not dotted');
		}
	}

	public function testPopupCloseButton()
	{
		// Ensure screen is ready
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Click the login link
		$this->click('#fancy_login_close_button');

		// Confirm that the dimmer and form are hidden
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":visible")');
	}

	public function testPopupDimmerClickClose()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Click the login link
		// Note - bug in the system means we need to simulate a click with JS, rather than
		// using $this->click();
		$script = "jQuery('#fancy_login_dim_screen').click();";
		$this->getSession()->evaluateScript($script);

		// Confirm that the dimmer and form are hidden
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":visible")');
	}

	public function testPopupKeyboardClose()
	{
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Press the keyboard keys which simulate ctrl + .
		$key = 17;
		$script = "jQuery.event.trigger({ type : 'keydown', which : '" . $key . "' });";
		$this->getSession()->evaluateScript($script);
		$key = 190;
		$script = "jQuery.event.trigger({ type : 'keydown', which : '" . $key . "' });";
		$this->getSession()->evaluateScript($script);

		// Confirm that the dimmer and form are hidden
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":visible")');
	}

	public function testLinks()
	{
		// Confirm popup opened
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Click link
		$this->clickLink('Request new password');

		// Wait until new form has loaded
		$this->assertJsCondition('jQuery("#fancy-login-user-pass").length');
		$this->assertJsCondition('jQuery("#fancy-login-user-pass").not(":animated")');

		// Click Link to return to original form
		$this->clickLink('Sign in');

		// Wait until new form has loaded
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").length');
		$this->assertJsCondition('jQuery("#fancy-login-user-pass").not(":animated")');

		// Click the create account link and confirm redirect goes to correct page
		$this->clickLink('Create new account');
		$this->getSession()->wait(5000);
		$this->assertStatusCodeEquals(200);
		$this->assertSession()->addressMatches('/\/user\/register$/');

		// Go back to node 1
		$this->drupalGet('/node/1');
		$this->assertStatusCodeEquals(200);

		// Next confirm that the create new account link works on the password reset form
		$this->openPopup();

		// Ensure popup is open
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Switch forms
		$this->clickLink('Request new password');

		// Wait until form is loaded
		$this->assertJsCondition('jQuery("#fancy-login-user-pass").length');
		$this->assertJsCondition('jQuery("#fancy-login-user-pass").not(":animated")');

		// Click the create new account link and confirm correct page loads
		$this->clickLink('Create new account');
		$this->getSession()->wait(5000);
		$this->assertStatusCodeEquals(200);
		$this->assertSession()->addressMatches('/\/user\/register$/');
	}

	public function testInclusionLink()
	{
		// Ensure screen is ready
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Click the login link
		$this->click('#fancy_login_close_button');

		// Ensure screen is ready
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Create the inclusion link
		$this->getSession()->executeScript('jQuery("<a/>", {class:"fancy_login_show_popup", href:"/node/1"}).text("open popup").appendTo("body")');
		$link = $this->getSession()->getPage()->find('css', 'a.fancy_login_show_popup');
		if(!$link)
		{
			throw new \Exception('Could not create inclusion link');
		}

		$this->getSession()->executeScript('Drupal.attachBehaviors()');

		// Confirm that the dimmer and form are hidden
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":visible")');

		$this->getSession()->executeScript('jQuery("#fancy_login_dim_screen").hide()');

		// Trigger the popup
		$this->getSession()->executeScript('jQuery("a.fancy_login_show_popup:first").click()');

		// Confirm that the dimmer and form are visible
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").is(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").is(":visible")');
	}

	public function testExclusionLink()
	{
		// Ensure screen is ready
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Click the login link
		$this->click('#fancy_login_close_button');

		// Ensure screen is ready
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":animated")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":animated")');

		// Confirm that the dimmer and form are hidden
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":visible")');

		// Create the inclusion link
		$this->getSession()->executeScript('jQuery("<a/>", {class:"fancy_login_disable", href:"/user/login"}).text("do not open popup").prependTo("body")');
		$link = $this->getSession()->getPage()->find('css', 'a.fancy_login_disable');
		if(!$link)
		{
			throw new \Exception('Could not create exclusion link');
		}

		$this->getSession()->executeScript('Drupal.attachBehaviors()');

		// Confirm that the dimmer and form are hidden
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":visible")');

		$this->getSession()->executeScript('jQuery("#fancy_login_dim_screen").remove()');

		// Click the exclusion link
		$this->click("a.fancy_login_disable");

		$this->getSession()->wait(5000);
		$this->assertStatusCodeEquals(200);
		$this->assertSession()->addressMatches('/\/user\/login$/');
	}

	private function goToConfigPage()
	{
		$user = $this->createUser(['Administer fancy login', 'access administration pages']);

		$this->fillTextValue('#edit-name', $user->getAccountName());
		$this->fillTextValue('#edit-pass', $user->passRaw);
		$this->click('#fancy-login-user-login-form .form-submit');
		$this->assertSession()->assertWaitOnAjaxRequest();
		$this->getSession()->wait(5000);
		$this->assertStatusCodeEquals(200);
		$this->assertSession()->addressMatches('/\/user\/2$/');

		// Test link exists on admin page (restrict_ip.links.menu.yml)
		$this->drupalGet('admin/config');
		$this->assertStatusCodeEquals(200);
		$this->assertSession()->pageTextContains('Fancy Login');
		$this->assertSession()->pageTextContains('Settings for Fancy Login');
		$this->clickLink('Fancy Login');

		// Test admin page exists
		$this->assertSession()->addressMatches('/\/admin\/config\/people\/fancy_login$/');
		$this->assertStatusCodeEquals(200);
	}

	private function openPopup()
	{
		// Go to a node page where we can see the login link
		$this->drupalGet('/node/1');
		$this->assertStatusCodeEquals(200);

		// Confirm that the dimmer and form are hidden
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").not(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").not(":visible")');

		// Trigger the popup
		$this->clickLink('Login');

		// Confirm that the dimmer and form are visible
		$this->assertJsCondition('jQuery("#fancy_login_dim_screen").is(":visible")');
		$this->assertJsCondition('jQuery("#fancy-login-user-login-form").is(":visible")');
	}

	private function createScriptString($selector, $property, $value)
	{
		return '(function($){return $("' . $selector . '").css("' . $property . '") === "' . $value . '";}(jQuery));';
	}

	private function debugCssProperty($selector, $property)
	{
		$script = '(function($){return $("' . $selector . '").css("' . $property . '");}(jQuery));';
		print_r($this->getSession()->evaluateScript($script));
	}
}
