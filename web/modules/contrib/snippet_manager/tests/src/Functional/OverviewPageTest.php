<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Test for overview page.
 *
 * @group snippet_manager
 */
class OverviewPageTest extends TestBase {

  /**
   * Test callback.
   */
  public function testOverviewPage() {

    $this->drupalGet('admin/structure/snippet');
    $this->assertPageTitle(t('Snippets'));

    $this->assertXpath('//main//input[@type="search" and @data-drupal-selector="sm-snippet-search" and @placeholder="Snippet name or ID"]');
    $this->assertXpath('//main//select[@data-drupal-selector="sm-snippet-usage"]');
    $this->assertXpath('//main//select[@data-drupal-selector="sm-snippet-status"]');

    $header_xpaths = [
      'th[1][text()="Name"]',
      'th[2][text()="ID"]',
      'th[3][text()="Status"]',
      'th[4][text()="Page"]',
      'th[5][text()="Block"]',
      'th[6][text()="Display variant"]',
      'th[7][text()="Layout"]',
      'th[8][text()="Operations"]',
    ];
    $this->assertXpaths($header_xpaths, '//main//table/thead/tr/');

    $alpha_row_prefix = '//main//table/tbody/tr';
    $alpha_row_xpaths = [
      '/td[1]/a[contains(@href, "/admin/structure/snippet/alpha") and text()="Alpha"]',
      '/td[2][text()="alpha"]',
      '/td[3][text()="Enabled"]',
      '/td[4]/a[contains(@href, "/alpha-page") and text()="alpha-page"]',
      '/td[5][text()="Alpha block"]',
      '/td[6][not(text())]',
      '/td[7][not(text())]',
    ];
    $this->assertXpaths($alpha_row_xpaths, $alpha_row_prefix);

    $links_xpaths = [
      '/a[contains(@href, "/admin/structure/snippet/alpha/edit") and text()="Edit"]',
      '/a[contains(@href, "/admin/structure/snippet/alpha/delete") and text()="Delete"]',
    ];
    $this->assertXpaths($links_xpaths, $alpha_row_prefix . '/td[8]//ul[@class="dropbutton"]/li');

  }

}
