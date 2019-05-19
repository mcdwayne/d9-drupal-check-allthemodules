<?php

namespace Drupal\Tests\views_ajax_get\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests basic AJAX functionality of Views exposed forms using GET requests.
 *
 * @group views_ajax_get
 */
class ExposedFilterViewsAjaxGetTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['views_ajax_get_cache_test'];

  /**
   * Tests if exposed filtering via AJAX works for the "Content" View.
   */
  public function testExposedFiltering() {
    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Page One']);
    $this->createNode(['title' => 'Page Two']);

    // Enable page cache.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Visit the test view page to test caching.
    $this->drupalGet('views-ajax-get-cache-test');
    $this->addAjaxCompleteHandler();

    $session = $this->getSession();

    // Search for "Page One".
    $this->submitForm(['title' => 'Page One'], t('Filter'));
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check that the AJAX request was a GET and cache missed first time.
    $drupal_settings = $this->getDrupalSettings();
    $this->assertEquals('GET', $drupal_settings['viewsAjaxGetMethod']);
    $this->assertEquals('MISS', $drupal_settings['viewsAjaxGetCacheHeader']);

    // Verify that only the "Page One" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertContains('Page One', $html);
    $this->assertNotContains('Page Two', $html);

    // Search for "Page Two".
    $this->submitForm(['title' => 'Page Two'], t('Filter'));
    $this->assertSession()->assertWaitOnAjaxRequest();

    $drupal_settings = $this->getDrupalSettings();
    $this->assertEquals('GET', $drupal_settings['viewsAjaxGetMethod']);
    $this->assertEquals('MISS', $drupal_settings['viewsAjaxGetCacheHeader']);

    // Verify that only the "Page Two" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertContains('Page Two', $html);
    $this->assertNotContains('Page One', $html);

    // Search for "Page One".
    $this->submitForm(['title' => 'Page One'], t('Filter'));
    $this->assertSession()->assertWaitOnAjaxRequest();

    // AJAX request should be a get and a hit now.
    $drupal_settings = $this->getDrupalSettings();
    $this->assertEquals('GET', $drupal_settings['viewsAjaxGetMethod']);
    // @todo Check for X-Drupal-Cache header is HIT. Right now it's coming
    // up in the XHR response header as MISS, event though it seems to be HIT
    // server side.

    // Verify that only the "Page One" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertContains('Page One', $html);
    $this->assertNotContains('Page Two', $html);

    // Search for "Page Two".
    $this->submitForm(['title' => 'Page Two'], t('Filter'));
    $this->assertSession()->assertWaitOnAjaxRequest();

    $drupal_settings = $this->getDrupalSettings();
    $this->assertEquals('GET', $drupal_settings['viewsAjaxGetMethod']);
    // @todo Check for X-Drupal-Cache header is HIT. Right now it's coming
    // up in the XHR response header as MISS, event though it seems to be HIT
    // server side.

    // Verify that only the "Page Two" Node is present.
    $html = $session->getPage()->getHtml();
    $this->assertContains('Page Two', $html);
    $this->assertNotContains('Page One', $html);
  }

  /**
   * Add an AJAX event handler to get the AJAX response information.
   */
  protected function addAjaxCompleteHandler() {
    $javascript = <<<JS
    (function($, drupalSettings) {
      $(document).on('ajaxComplete', function(event, xhr, settings) {
        drupalSettings.viewsAjaxGetMethod = settings.type;
        drupalSettings.viewsAjaxGetCacheHeader = xhr.getResponseHeader('X-Drupal-Cache');
      });
    }(jQuery, drupalSettings));
JS;
    $this->getSession()->executeScript($javascript);
  }

}
