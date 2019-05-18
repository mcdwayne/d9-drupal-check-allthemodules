<?php

namespace Drupal\piwik\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test basic functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikBasicTest extends WebTestBase {

  /**
   * User without permissions to use snippets.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noSnippetUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['piwik'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer piwik',
    ];

    // User to set up piwik.
    $this->noSnippetUser = $this->drupalCreateUser($permissions);
    $permissions[] = 'add JS snippets for piwik';
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if configuration is possible.
   */
  public function testPiwikConfiguration() {
    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/piwik');
    $this->assertRaw(t('Piwik site ID'), '[testPiwikConfiguration]: Settings page displayed.');

    // Check for account code validation.
    $edit['piwik_site_id'] = $this->randomMachineName(2);
    $edit['piwik_url_http'] = 'http://www.example.com/piwik/';
    $this->drupalPostForm('admin/config/system/piwik', $edit, 'Save configuration');
    $this->assertRaw(t('A valid Piwik site ID is an integer only.'), '[testPiwikConfiguration]: Invalid Piwik site ID number validated.');

    // Verify that invalid URLs throw a form error.
    $edit = [];
    $edit['piwik_site_id'] = 1;
    $edit['piwik_url_http'] = 'http://www.example.com/piwik/';
    $edit['piwik_url_https'] = 'https://www.example.com/piwik/';
    $this->drupalPostForm('admin/config/system/piwik', $edit, t('Save configuration'));
    $this->assertRaw('The validation of "http://www.example.com/piwik/piwik.php" failed with an exception', '[testPiwikConfiguration]: HTTP URL exception shown.');
    $this->assertRaw('The validation of "https://www.example.com/piwik/piwik.php" failed with an exception', '[testPiwikConfiguration]: HTTPS URL exception shown.');

    // User should have access to code snippets.
    $this->assertFieldByName('piwik_codesnippet_before');
    $this->assertFieldByName('piwik_codesnippet_after');
    $this->assertNoFieldByXPath("//textarea[@name='piwik_codesnippet_before' and @disabled='disabled']", NULL, '"Code snippet (before)" is enabled.');
    $this->assertNoFieldByXPath("//textarea[@name='piwik_codesnippet_after' and @disabled='disabled']", NULL, '"Code snippet (after)" is enabled.');

    // Login as user without JS permissions.
    $this->drupalLogin($this->noSnippetUser);
    $this->drupalGet('admin/config/system/piwik');

    // User should *not* have access to snippets, but create fields.
    $this->assertFieldByName('piwik_codesnippet_before');
    $this->assertFieldByName('piwik_codesnippet_after');
    $this->assertFieldByXPath("//textarea[@name='piwik_codesnippet_before' and @disabled='disabled']", NULL, '"Code snippet (before)" is disabled.');
    $this->assertFieldByXPath("//textarea[@name='piwik_codesnippet_after' and @disabled='disabled']", NULL, '"Code snippet (after)" is disabled.');
  }

  /**
   * Tests if page visibility works.
   */
  public function testPiwikPageVisibility() {
    $site_id = '1';
    $this->config('piwik.settings')->set('site_id', $site_id)->save();
    $this->config('piwik.settings')->set('url_http', 'http://www.example.com/piwik/')->save();
    $this->config('piwik.settings')->set('url_https', 'https://www.example.com/piwik/')->save();

    // Show tracking on "every page except the listed pages".
    $this->config('piwik.settings')->set('visibility.request_path_mode', 0)->save();
    // Disable tracking one "admin*" pages only.
    $this->config('piwik.settings')->set('visibility.request_path_pages', "/admin\n/admin/*")->save();
    // Enable tracking only for authenticated users only.
    $this->config('piwik.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is displayed for authenticated users.');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is not displayed on admin page.');
    $this->drupalGet('admin/config/system/piwik');
    // Checking for tracking URI here, as $site_id is displayed in the form.
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is not displayed on admin subpage.');

    // Test whether tracking code display is properly flipped.
    $this->config('piwik.settings')->set('visibility.request_path_mode', 1)->save();
    $this->drupalGet('admin');
    $this->assertRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is displayed on admin page.');
    $this->drupalGet('admin/config/system/piwik');
    // Checking for tracking URI here, as $site_id is displayed in the form.
    $this->assertRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is displayed on admin subpage.');
    $this->drupalGet('');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is NOT displayed on front page.');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is NOT displayed for anonymous.');

    // Switch back to every page except the listed pages.
    $this->config('piwik.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('piwik.settings')->set('visibility.user_role_roles', [])->save();

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertRaw('"403/URL = "', '[testPiwikPageVisibility]: 403 Forbidden tracking code shown if user has no access.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomMachineName(64));
    $this->assertRaw('"404/URL = "', '[testPiwikPageVisibility]: 404 Not Found tracking code shown on non-existent page.');
  }

  /**
   * Tests if tracking code is properly added to the page.
   */
  public function testPiwikTrackingCode() {
    $site_id = '2';
    $this->config('piwik.settings')->set('site_id', $site_id)->save();
    $this->config('piwik.settings')->set('url_http', 'http://www.example.com/piwik/')->save();
    $this->config('piwik.settings')->set('url_https', 'https://www.example.com/piwik/')->save();

    // Show tracking code on every page except the listed pages.
    $this->config('piwik.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('piwik.settings')->set('visibility.user_role_roles', [])->save();

    /* Sample JS code as added to page:
    <script type="text/javascript">
    var _paq = _paq || [];
    (function(){
        var u=(("https:" == document.location.protocol) ? "https://{$PIWIK_URL}" : "http://{$PIWIK_URL}");
        _paq.push(['setSiteId', {$IDSITE}]);
        _paq.push(['setTrackerUrl', u+'piwik.php']);
        _paq.push(['trackPageView']);
        var d=document,
            g=d.createElement('script'),
            s=d.getElementsByTagName('script')[0];
            g.type='text/javascript';
            g.defer=true;
            g.async=true;
            g.src=u+'piwik.js';
            s.parentNode.insertBefore(g,s);
    })();
    </script>
    */

    // Test whether tracking code uses latest JS.
    $this->config('piwik.settings')->set('cache', 0)->save();
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikTrackingCode]: Latest tracking code used.');

    // Test if tracking of User ID is enabled.
    $this->config('piwik.settings')->set('track.userid', 1)->save();
    $this->drupalGet('');
    $this->assertRaw('_paq.push(["setUserId", ', '[testPiwikTrackingCode]: Tracking code for User ID is enabled.');

    // Test if tracking of User ID is disabled.
    $this->config('piwik.settings')->set('track.userid', 0)->save();
    $this->drupalGet('');
    $this->assertNoRaw('_paq.push(["setUserId", ', '[testPiwikTrackingCode]: Tracking code for User ID is disabled.');

    // Test whether single domain tracking is active.
    $this->drupalGet('');
    $this->assertNoRaw('_paq.push(["setCookieDomain"', '[testPiwikTrackingCode]: Single domain tracking is active.');

    // Enable "One domain with multiple subdomains".
    $this->config('piwik.settings')->set('domain_mode', 1)->save();
    $this->drupalGet('');

    // Test may run on localhost, an ipaddress or real domain name.
    // TODO: Workaround to run tests successfully. This feature cannot tested
    // reliable.
    global $cookie_domain;
    if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $this->assertRaw('_paq.push(["setCookieDomain"', '[testPiwikTrackingCode]: One domain with multiple subdomains is active on real host.');
    }
    else {
      // Special cases, Localhost and IP addresses don't show 'setCookieDomain'.
      $this->assertNoRaw('_paq.push(["setCookieDomain"', '[testPiwikTrackingCode]: One domain with multiple subdomains may be active on localhost (test result is not reliable).');
    }

    // Test whether the BEFORE and AFTER code is added to the tracker.
    $this->config('piwik.settings')->set('codesnippet.before', '_paq.push(["setLinkTrackingTimer", 250]);')->save();
    $this->config('piwik.settings')->set('codesnippet.after', '_paq.push(["t2.setSiteId", 2]);if(1 == 1 && 2 < 3 && 2 > 1){console.log("Piwik: Custom condition works.");}_gaq.push(["t2.trackPageView"]);')->save();
    $this->drupalGet('');
    $this->assertRaw('setLinkTrackingTimer', '[testPiwikTrackingCode]: Before codesnippet has been found with "setLinkTrackingTimer" set.');
    $this->assertRaw('t2.trackPageView', '[testPiwikTrackingCode]: After codesnippet with "t2" tracker has been found.');
    $this->assertRaw('if(1 == 1 && 2 < 3 && 2 > 1){console.log("Piwik: Custom condition works.");}', '[testPiwikTrackingCode]: JavaScript code is not HTML escaped.');
  }

}
