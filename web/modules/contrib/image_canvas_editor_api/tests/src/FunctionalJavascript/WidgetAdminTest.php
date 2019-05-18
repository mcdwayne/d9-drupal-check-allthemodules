<?php

namespace Drupal\Tests\image_canvas_editor_api\FunctionalJavascript;

use Drupal\Tests\image\FunctionalJavascript\ImageFieldTestBase;

/**
 * @group image_canvas_editor_api
 */
class WidgetAdminTest extends ImageFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'image',
    'field_ui',
    'image_canvas_editor_api',
  ];

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(['bypass node access', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display']);
    $this->drupalLogin($web_user);
  }

  /**
   * Test the fact that we can select image canvas editor as widget.
   */
  public function testAdminWidget() {
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', ['cardinality' => -1]);
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->getSession()->getPage()->selectFieldOption("fields[$field_name][type]", 'image_canvas_editor');
  }

}
