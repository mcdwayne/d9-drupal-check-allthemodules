<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Tests\ViewsXMLBackendPagingTest.
 */

namespace Drupal\views_xml_backend\Tests;

/**
 * Tests paging functions from the Views XML Backend module.
 *
 * @group views_xml_backend
 */

class ViewsXMLBackendPagingTest extends ViewsXMLBackendBase {

  /**
   * Tests Views XML Backend View paging.
   */
  public function testPagingViewsXMLBackend() {
    $this->addStandardXMLBackendView();

    $this->drupalGet("admin/structure/views/view/{$this->viewsXMLBackendViewId}/edit");
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, $edit = array(), t('Update preview'));

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 10);
    $this->assertFieldByXPath("//nav[@class='pager']", NULL, "Pager nav found");
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', array(':class' => 'pager__items'));
    $this->assertTrue(!empty($elements), 'Pager elements found.');

    // Verify elements and links to pages.
    // We expect to find 4 elements: current page == 1, link to page 2
    // links to 'next >' and 'last >>' pages.
    $this->assertTrue(strpos($elements[0]['class'], 'is-active') !== FALSE, 'Element for current page has .is-active class.');
    $this->assertTrue($elements[0]->a, 'Element for current page has link.');
    $this->assertTrue(strpos($elements[1]['class'], 'pager__item') !== FALSE, 'Element for page 2 has .pager__item class.');
    $this->assertTrue($elements[1]->a, 'Link to page 2 found.');

    // Navigate to next page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager__item--next'));
    $url = $elements[0]['href'];
    $this->navigateViewsPager($url);

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 10);

    // Test that the pager is present and rendered.
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', array(':class' => 'pager__items'));
    $this->assertTrue(!empty($elements), 'Full pager found.');

    // Navigate to previous page.
    $elements = $this->xpath('//li[contains(@class, :class)]/a', array(':class' => 'pager__item--previous'));
    $url = $elements[0]['href'];
    $this->navigateViewsPager($url);

    $elements = $this->xpath('//div[@class = "view-content"]/div[contains(@class, views-row)]');
    $this->assertEqual(count($elements), 10);
  }

}
