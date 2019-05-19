<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Tests\ViewsXMLBackendFilteringTest.
 */

namespace Drupal\views_xml_backend\Tests;

/**
 * Tests filtering functions from the Views XML Backend module.
 *
 * @group views_xml_backend
 */

class ViewsXMLBackendFilteringTest extends ViewsXMLBackendBase {

  /**
   * Tests Views XML Backend View filtering.
   */
  public function testFilteringViewsXMLBackend() {
    $this->addStandardXMLBackendView();

    // Check add filtering ability
    $this->drupalPostForm("admin/structure/views/nojs/add-handler/{$this->viewsXMLBackendViewId}/default/filter", array('name[views_xml_backend.text]' => 'views_xml_backend.text'), t('Add and configure @handler', array('@handler' => t('filter criteria'))));
    $this->assertField('options[xpath_selector]', "The XML input 'options[xpath_selector]' was found");
    $fields = [
      'options[xpath_selector]' => 'version_major',
      'options[operator]' => '!=',
      'options[value]' => '3',
    ];
    $this->drupalPostForm(NULL, $fields, t('Apply'));

    $this->drupalGet("admin/structure/views/nojs/handler/{$this->viewsXMLBackendViewId}/default/filter/text");
    $this->assertFieldByXPath("//input[@id='edit-options-xpath-selector']", 'version_major', "Value 'version_major' found in field 'edit-options-xpath-selector'");
    $this->assertFieldChecked('edit-options-operator---2');
    $this->assertFieldByXPath("//input[@id='edit-options-value']", '3', "Value '3' found in field 'edit-options-value'");
  }

}
