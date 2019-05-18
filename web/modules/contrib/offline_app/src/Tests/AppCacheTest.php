<?php

/**
 * @file
 * Contains \Drupal\offline_app\Tests\AppCacheTest
 */

namespace Drupal\offline_app\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests AppCache integration for Offline App.
 *
 * @group OfflineApp
 */
class AppCacheTest extends WebTestBase {

  protected $dumpHeaders = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['offline_app', 'node', 'block', 'views', 'offline_app_test', 'filter'];

  /**
   * User with permissions to administer offline application.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor;

  /**
   * Implements setup().
   */
  protected function setUp() {
    parent::setUp();

    // Allow anonymous to access manifest and offline pages.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access appcache manifest', 'access offline content', 'access homescreen manifest']);

    // Create admin user.
    $this->editor = $this->drupalCreateUser(['administer offline app', 'administer blocks']);
  }

  /**
   * Test appcache integration.
   */
  public function testAppCache() {
    // Configure the content, pages & menu.
    $this->drupalLogin($this->editor);
    // Explicitly use \r\n for line breaks.
    $edit = [
      'pages' => "about/node:1\r\ncontact/node:2\r\nnode-does-not-exist/node:20",
      'menu' => "homepage/Home\r\nabout/About this site\r\n",
      'homepage_title' => 'Welcome offline!',
    ];
    $this->drupalPostForm('admin/config/services/offline-app/content', $edit, 'Save configuration');

    // Place branding block.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'offline_header']);

    // See that the extra regions are available.
    $this->drupalGet('admin/structure/block');
    $this->assertText('Offline content');
    $this->assertText('Offline header');
    $this->assertText('Offline footer');

    // Expose read more.
    $this->drupalPostForm('admin/config/services/offline-app/settings', ['expose_read_more' => TRUE], 'Save configuration');

    // Add launcher icon.
    $this->drupalPostForm('admin/config/services/offline-app/homescreen', ['icon_192' => '/core/themes/bartik/logo.svg', 'icon_192_type' => 'svg'], 'Save configuration');

    // Logout.
    $this->drupalLogout();

    // Test caching of manifest.
    $this->drupalGet('manifest.appcache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertRaw('CACHE MANIFEST');
    $this->assertRaw('offline/about');
    $this->assertRaw('offline/node-does-not-exist');
    $this->assertRaw('offline/contact');
    $this->assertRaw('offline/css-from-default-theme.css');
    $this->assertRaw('offline-app.js');
    $this->assertRaw('FALLBACK:');
    $this->assertRaw('offline/appcache-fallback');
    $this->assertRaw('NETWORK:');

    $this->drupalGet('manifest.appcache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertRaw('CACHE MANIFEST');
    $this->assertRaw('FALLBACK:');
    $this->assertRaw('offline/appcache-fallback');
    $this->assertRaw('NETWORK:');

    // Invalidate manifest.
    Cache::invalidateTags(['appcache.manifest']);
    $this->drupalGet('manifest.appcache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');

    // Test css generation.
    $this->drupalGet('offline/css-from-default-theme.css');
    $this->assertResponse(200);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet('offline/css-from-default-theme.css');
    $this->assertResponse(200);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');

    // Test caching of iframe.
    $this->drupalGet('offline/appcache-iframe');
    $this->assertRaw('<html manifest="/manifest.appcache"></html>');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet('offline/appcache-iframe');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');

    // Test caching of homepage (which acts as the main fallback).
    $this->drupalGet('offline/appcache-fallback');
    $this->assertRaw('offline-frontpage');
    $this->assertRaw('<h1 class="page-title">Welcome offline!</h1>');
    $this->assertText('Welcome to the offline version of this site!');
    $this->assertRaw('The content is now available offline!');
    $this->assertRaw('The content of the app has been updated!');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet('offline/appcache-fallback');
    $this->assertText('Welcome to the offline version of this site!');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->assertNoRaw('<meta name="apple-mobile-web-app-capable" content="yes"');
    $this->assertNoRaw('manifest="/manifest.appcache');

    // Test that '/offline' also serves homepage.
    $this->drupalGet('offline');
    $this->assertRaw('offline-frontpage');
    $this->assertRaw('<h1 class="page-title">Welcome offline!</h1>');
    $this->assertText('Welcome to the offline version of this site!');

    // Test caching of manifest.json.
    $this->drupalGet('manifest.json');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertRaw('"name":"Drupal"');
    $this->assertRaw('"start_url":"offline\/appcache-fallback"');
    $this->assertRaw('"display":"standalone"');
    $this->assertRaw('"type":"image\/svg"');
    $this->assertRaw('\/core\/themes\/bartik\/logo.svg');
    $this->drupalGet('manifest.json');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    Cache::invalidateTags(['homescreen']);
    $this->drupalGet('manifest.json');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');

    // Create an about page.
    $about_content_input = 'This page will tell us a little bit about ourselves. <a href="/node/internal-link">Links will be stripped</a>. <strong>Other tags can stay though.</strong>';
    $about_content = 'This page will tell us a little bit about ourselves. Links will be stripped. Other tags can stay though.';
    $about = [
      'value' => $about_content_input,
      'format' => 'full_html',
    ];
    $about_node = $this->drupalCreateNode(['title' => 'About', 'body' => $about]);
    $this->drupalGet('node/' . $about_node->id());
    $this->assertRaw("node/internal-link");
    $this->assertRaw('<meta name="mobile-web-app-capable" content="yes"');
    $this->assertRaw('<meta name="apple-mobile-web-app-capable" content="yes"');
    $this->assertRaw('<link rel="manifest" href="/manifest.json');
    $this->assertRaw('<link rel="apple-touch-startup-image" href="/core/themes/bartik/logo.svg"');

    // Create an contact page.
    $contact_content = 'You can contact us by mail: info@example.com';
    $contact = [
      'value' => $contact_content,
      'format' => 'plain_text',
    ];
    $this->drupalCreateNode(['title' => 'Contact', 'body' => $contact]);

    // Test generation of offline page.
    $this->drupalGet('offline/about');
    $this->assertRaw("offline/appcache-fallback\" title=\"Home\" rel=\"home\">");
    $this->assertNoRaw("node/internal-link");
    $this->assertRaw("<strong>");
    $this->assertNoRaw('offline-frontpage');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertText($about_content);
    $this->drupalGet('offline/about');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');

    // Verify we have different caches.
    $this->drupalGet('offline/contact');
    $this->assertText($contact_content);

    // Update the node and make sure the cache is wiped, also from the manifest.
    $about_node->set('title', 'About the application');
    $about_node->save();
    $this->drupalGet('offline/about');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertText('About the application');
    $this->drupalGet('offline/about');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');
    $this->drupalGet('manifest.appcache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet('manifest.appcache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');

    // Invalidate all at once.
    Cache::invalidateTags(['appcache.manifest', 'appcache']);
    $this->drupalGet('manifest.appcache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet('offline/appcache-fallback');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet('offline/appcache-iframe');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->drupalGet('offline/about');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');

    // Non existing alias in settings.
    $this->drupalGet('offline/non-existing-alias');
    $this->assertText('Following alias was not found in the pages configuration: non-existing-alias');

    // Non existing content, but existing alias in settings.
    $this->drupalGet('offline/node-does-not-exist');
    $this->assertText('No content was found for following alias and configuration: node-does-not-exist - node:20');

    // Verify page titles are there.
    $this->drupalGet('offline/about');
    $this->assertRaw('<title>About the application | Drupal</title>');
    $this->assertRaw('<h1 class="page-title">About the application</h1>');

    // Verify the menu is there.
    $this->assertRaw('>Home</a>');
    // Figure out a different test, this doesn't work on the testbot.
    // But it exposes a flaw that we have anyway.
    //$this->assertRaw("class=\"is-active\">About this site</a>");

    // Verify the h2 of the node is not there.
    $this->assertNoRaw('<h2>');
    $this->assertNoRaw('rel="bookmark">');

    // Verify the stylesheet is there.
    $this->assertRaw('offline/css-from-default-theme.css');

    // Verify the javascript is there.
    $this->assertRaw('offline-app.js');

    // Verify changing configuration on "content" invalidates the cache.
    $this->drupalLogin($this->editor);
    // Explicitly use \r\n for line breaks.
    $edit = [
      'pages' => "about/node:1\r\nnode-does-not-exist/node:20\r\nhowdy/node:21",
      'menu' => "appcache-fallback/Home\r\nabout/About this site title\r\n",
    ];
    $this->drupalPostForm('admin/config/services/offline-app/content', $edit, 'Save configuration');
    $this->drupalLogout();
    $this->drupalGet('offline/about');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertRaw('>About this site title</a>');

    $this->drupalGet('manifest.appcache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertRaw('offline/howdy');

    // Test views pages.
    $this->drupalLogin($this->editor);
    $edit = [
      'pages' => "about/node:1\r\ncontact/node:2\r\nlist/view:list:offline_1",
      'menu' => "homepage/Home\r\nabout/About this site\r\n",
    ];
    $this->drupalPostForm('admin/config/services/offline-app/content', $edit, 'Save configuration');
    $this->drupalLogout();

    $this->drupalGet('manifest.appcache');
    $this->assertRaw('offline/list');

    $this->drupalGet('offline/list');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertText($about_content);
    $this->assertText($contact_content);
    $this->assertRaw('<h1 class="page-title">Views title</h1>');
    $this->assertRaw('class="node-readmore');

    // Cache it.
    $this->drupalGet('offline/list');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT');

    // Remove read more.
    $this->drupalLogin($this->editor);
    $this->drupalPostForm('admin/config/services/offline-app/settings', ['expose_read_more' => FALSE], 'Save configuration');
    $this->drupalLogout();
    $this->drupalGet('offline/list');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS');
    $this->assertNoRaw('class="node-readmore');

    // Click on about - the link should be an 'offline' link so it goes to
    // the right page.
    $this->assertRaw("offline/about\" rel=\"bookmark\">");
    $this->clickLink('About the application');
    $this->assertText($about_content);
    $this->assertRaw('<h1 class="page-title">About the application</h1>');
    $this->assertRaw('>About this site</a>');

    // Create a page for homepage.
    $homepage_content = 'This will be in the body';
    $homepage = [
      'value' => $homepage_content,
      'format' => 'plain_text',
    ];
    $homepage_node = $this->drupalCreateNode(['title' => 'Node homepage', 'body' => $homepage]);
    // Test views pages.
    $this->drupalLogin($this->editor);
    $edit = [
      'homepage_type' => 'page',
      'homepage_page' => 'node:' . $homepage_node->id(),
    ];
    $this->drupalPostForm('admin/config/services/offline-app/content', $edit, 'Save configuration');

    $this->drupalLogout();
    $this->drupalGet('offline/appcache-fallback');
    $this->assertRaw('offline-frontpage');
    $this->assertText($homepage_content);
    $this->assertRaw('<h1 class="page-title">Node homepage</h1>');

    // Turn off/on homescreen.
    $this->drupalLogin($this->editor);
    $this->drupalPostForm('admin/config/services/offline-app', ['tag_on_offline' => TRUE], 'Save configuration');
    $this->drupalPostForm('admin/config/services/offline-app/homescreen', ['online_pages' => FALSE, 'offline_pages' => TRUE], 'Save configuration');
    $this->drupalLogout();

    $this->drupalGet('node/' . $about_node->id());
    $this->assertNoRaw('<meta name="mobile-web-app-capable" content="yes"');
    $this->assertNoRaw('<meta name="apple-mobile-web-app-capable" content="yes"');
    $this->assertNoRaw('<link rel="manifest" href="/manifest.json');
    $this->assertNoRaw('<link rel="apple-touch-startup-image" href="/core/themes/bartik/logo.svg"');
    $this->drupalGet('offline/appcache-fallback');
    $this->assertRaw('<meta name="mobile-web-app-capable" content="yes"');
    $this->assertRaw('<meta name="apple-mobile-web-app-capable" content="yes"');
    $this->assertRaw('<link rel="manifest" href="/manifest.json');
    $this->assertRaw('<link rel="apple-touch-startup-image" href="/core/themes/bartik/logo.svg"');
    $this->assertRaw('manifest="/manifest.appcache');

    // Check access on nodes.
    $homepage_node->setPublished(FALSE)->save();
    $this->drupalGet('offline/appcache-fallback');
    $this->assertNoRaw('<h1 class="page-title">Node homepage</h1>');
  }

}
