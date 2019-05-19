<?php

namespace Drupal\Tests\snippet_manager\Functional;

/**
 * Test for snippet canonical pages.
 *
 * @group snippet_manager
 */
class SnippetCanonicalPagesTest extends TestBase {

  /**
   * Test callback.
   */
  public function testCanonicalPages() {
    $this->drupalGet('/admin/structure/snippet/alpha');
    $this->assertPageTitle('Alpha');
    $this->assertXpath('//h3[contains(text(), "Hello world")]');
    $this->assertXpath('//div[contains(., "3 + 5 = 8")]');
    $this->drupalGet('/admin/structure/snippet/alpha/source');
    $this->assertPageTitle('Alpha');
    $textarea = $this->xpath('//textarea[@data-drupal-selector = "snippet-html-source"]')[0];
    $this->assertEquals($textarea->getHtml(), htmlspecialchars("<h3>Hello world!</h3>\n<div>3 + 5 = <b>8</b></div>"));
    $pattern = '#<div class="snippet-render-time">Render time: <em class="placeholder">[\d\.]+</em> ms</div>#';
    $this->assertSession()->responseMatches($pattern);
  }

}
