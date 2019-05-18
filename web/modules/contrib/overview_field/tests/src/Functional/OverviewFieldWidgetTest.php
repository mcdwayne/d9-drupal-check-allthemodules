<?php

namespace Drupal\Tests\overview_field\Functional;

/**
 * Class OverviewFieldWidgetTest.
 *
 * @group overview_field
 */
class OverviewFieldWidgetTest extends OverviewFieldTestBase {

  /**
   * Tests file widget element.
   */
  public function testWidgetElement() {
    // Check for overview widget in node/add/article page.
    $field_name = strtolower($this->randomMachineName());

    $field_settings = [];
    $this->createOverviewField($field_name, 'article', [], $field_settings, [], [], 'Overview field test on [site:name]');
    $this->drupalGet('node/add/article');
    $this->assertNotEqual(0, count($this->xpath('//div[contains(@class, "field--widget-overview-field-widget")]')), 'Overveiw field widget found on add/node page', 'Browser');
  }

}
