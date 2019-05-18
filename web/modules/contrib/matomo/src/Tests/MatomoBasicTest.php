<?php

namespace Drupal\matomo\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test basic functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoBasicTest extends WebTestBase {

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
  public static $modules = ['matomo'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer matomo',
    ];

    // User to set up matomo.
    $this->noSnippetUser = $this->drupalCreateUser($permissions);
    $permissions[] = 'add js snippets for matomo';
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if configuration is possible.
   */
  public function testMatomoConfiguration() {
    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/matomo');
    $this->assertRaw(t('Matomo site ID'), '[testMatomoConfiguration]: Settings page displayed.');

    // Check for account code validation.
    $edit['matomo_site_id'] = $this->randomMachineName(2);
    $edit['matomo_url_http'] = 'http://www.example.com/matomo/';
    $this->drupalPostForm('admin/config/system/matomo', $edit, 'Save configuration');
    $this->assertRaw(t('A valid Matomo site ID is an integer only.'), '[testMatomoConfiguration]: Invalid Matomo site ID number validated.');

    // Verify that invalid URLs throw a form error.
    $edit = [];
    $edit['matomo_site_id'] = 1;
    $edit['matomo_url_http'] = 'http://www.example.com/matomo/';
    $edit['matomo_url_https'] = 'https://www.example.com/matomo/';
    $this->drupalPostForm('admin/config/system/matomo', $edit, t('Save configuration'));
    $this->assertRaw('The validation of "http://www.example.com/matomo/matomo.php" failed with an exception', '[testMatomoConfiguration]: HTTP URL exception shown.');
    $this->assertRaw('The validation of "https://www.example.com/matomo/matomo.php" failed with an exception', '[testMatomoConfiguration]: HTTPS URL exception shown.');

    // User should have access to code snippets.
    $this->assertFieldByName('matomo_codesnippet_before');
    $this->assertFieldByName('matomo_codesnippet_after');
    $this->assertNoFieldByXPath("//textarea[@name='matomo_codesnippet_before' and @disabled='disabled']", NULL, '"Code snippet (before)" is enabled.');
    $this->assertNoFieldByXPath("//textarea[@name='matomo_codesnippet_after' and @disabled='disabled']", NULL, '"Code snippet (after)" is enabled.');

    // Login as user without JS permissions.
    $this->drupalLogin($this->noSnippetUser);
    $this->drupalGet('admin/config/system/matomo');

    // User should *not* have access to snippets, but create fields.
    $this->assertFieldByName('matomo_codesnippet_before');
    $this->assertFieldByName('matomo_codesnippet_after');
    $this->assertFieldByXPath("//textarea[@name='matomo_codesnippet_before' and @disabled='disabled']", NULL, '"Code snippet (before)" is disabled.');
    $this->assertFieldByXPath("//textarea[@name='matomo_codesnippet_after' and @disabled='disabled']", NULL, '"Code snippet (after)" is disabled.');
  }

  /**
   * Tests if page visibility works.
   */
  public function testMatomoPageVisibility() {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Show tracking on "every page except the listed pages".
    $this->config('matomo.settings')->set('visibility.request_path_mode', 0)->save();
    // Disable tracking one "admin*" pages only.
    $this->config('matomo.settings')->set('visibility.request_path_pages', "/admin\n/admin/*")->save();
    // Enable tracking only for authenticated users only.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw('/matomo/js/matomo.js', '[testMatomoPageVisibility]: Custom tracking script is is displayed for authenticated users.');
    $this->assertRaw('u+"matomo.php"', '[testMatomoPageVisibility]: Tracking code is displayed for authenticated users.');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoPageVisibility]: Tracking code is not displayed on admin page.');
    $this->drupalGet('admin/config/system/matomo');
    // Checking for tracking URI here, as $site_id is displayed in the form.
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoPageVisibility]: Tracking code is not displayed on admin subpage.');

    // Test whether tracking code display is properly flipped.
    $this->config('matomo.settings')->set('visibility.request_path_mode', 1)->save();
    $this->drupalGet('admin');
    $this->assertRaw('u+"matomo.php"', '[testMatomoPageVisibility]: Tracking code is displayed on admin page.');
    $this->drupalGet('admin/config/system/matomo');
    // Checking for tracking URI here, as $site_id is displayed in the form.
    $this->assertRaw('u+"matomo.php"', '[testMatomoPageVisibility]: Tracking code is displayed on admin subpage.');
    $this->drupalGet('');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoPageVisibility]: Tracking code is NOT displayed on front page.');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoPageVisibility]: Tracking code is NOT displayed for anonymous.');

    // Switch back to every page except the listed pages.
    $this->config('matomo.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertRaw('"403/URL = "', '[testMatomoPageVisibility]: 403 Forbidden tracking code shown if user has no access.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomMachineName(64));
    $this->assertRaw('"404/URL = "', '[testMatomoPageVisibility]: 404 Not Found tracking code shown on non-existent page.');
  }

  /**
   * Tests if tracking code is properly added to the page.
   */
  public function testMatomoTrackingCode() {
    $site_id = '2';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Show tracking code on every page except the listed pages.
    $this->config('matomo.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    /* Sample JS code as added to page:
    <script type="text/javascript">
    var _paq = _paq || [];
    (function(){
        var u=(("https:" == document.location.protocol) ? "https://{$MATOMO_URL}" : "http://{$MATOMO_URL}");
        _paq.push(['setSiteId', {$IDSITE}]);
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        _paq.push(['trackPageView']);
        var d=document,
            g=d.createElement('script'),
            s=d.getElementsByTagName('script')[0];
            g.type='text/javascript';
            g.defer=true;
            g.async=true;
            g.src=u+'matomo.js';
            s.parentNode.insertBefore(g,s);
    })();
    </script>
    */

    // Test whether tracking code uses latest JS.
    $this->config('matomo.settings')->set('cache', 0)->save();
    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoTrackingCode]: Latest tracking code used.');

    // Test if tracking of User ID is enabled.
    $this->config('matomo.settings')->set('track.userid', 1)->save();
    $this->drupalGet('');
    $this->assertRaw('_paq.push(["setUserId", ', '[testMatomoTrackingCode]: Tracking code for User ID is enabled.');

    // Test if tracking of User ID is disabled.
    $this->config('matomo.settings')->set('track.userid', 0)->save();
    $this->drupalGet('');
    $this->assertNoRaw('_paq.push(["setUserId", ', '[testMatomoTrackingCode]: Tracking code for User ID is disabled.');

    // Test whether single domain tracking is active.
    $this->drupalGet('');
    $this->assertNoRaw('_paq.push(["setCookieDomain"', '[testMatomoTrackingCode]: Single domain tracking is active.');

    // Enable "One domain with multiple subdomains".
    $this->config('matomo.settings')->set('domain_mode', 1)->save();
    $this->drupalGet('');

    // Test may run on localhost, an ipaddress or real domain name.
    // TODO: Workaround to run tests successfully. This feature cannot tested
    // reliable.
    global $cookie_domain;
    if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $this->assertRaw('_paq.push(["setCookieDomain"', '[testMatomoTrackingCode]: One domain with multiple subdomains is active on real host.');
    }
    else {
      // Special cases, Localhost and IP addresses don't show 'setCookieDomain'.
      $this->assertNoRaw('_paq.push(["setCookieDomain"', '[testMatomoTrackingCode]: One domain with multiple subdomains may be active on localhost (test result is not reliable).');
    }

    // Test whether the BEFORE and AFTER code is added to the tracker.
    $this->config('matomo.settings')->set('codesnippet.before', '_paq.push(["setLinkTrackingTimer", 250]);')->save();
    $this->config('matomo.settings')->set('codesnippet.after', '_paq.push(["t2.setSiteId", 2]);if(1 == 1 && 2 < 3 && 2 > 1){console.log("Matomo: Custom condition works.");}_gaq.push(["t2.trackPageView"]);')->save();
    $this->drupalGet('');
    $this->assertRaw('setLinkTrackingTimer', '[testMatomoTrackingCode]: Before codesnippet has been found with "setLinkTrackingTimer" set.');
    $this->assertRaw('t2.trackPageView', '[testMatomoTrackingCode]: After codesnippet with "t2" tracker has been found.');
    $this->assertRaw('if(1 == 1 && 2 < 3 && 2 > 1){console.log("Matomo: Custom condition works.");}', '[testMatomoTrackingCode]: JavaScript code is not HTML escaped.');
  }

}
