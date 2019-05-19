<?php

namespace Drupal\Tests\widget_engine\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\widget_engine\Traits\WidgetTypeCreationTrait;
use Drupal\Tests\widget_engine\Traits\EntityAddWidgetFieldTrait;

/**
 * Testing 'widget_reference_ief_complex' widget.
 *
 * @group widget_engine
 */
class WidgetReferenceIEFComplexTest extends JavascriptTestBase {

  use ContentTypeCreationTrait;
  use WidgetTypeCreationTrait;
  use EntityAddWidgetFieldTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'widget_engine',
    'image',
    'field',
    'field_ui',
    'entity_reference',
    'block',
    'inline_entity_form',
  ];

  /**
   * Node bundle object.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  public $nodeBundle;

  /**
   * Widget type.
   *
   * @var string
   */
  public $widgetBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->nodeBundle = $this->createContentType(['type' => 'widget_test']);
    $this->widgetBundle = $this->createWidgeType(['type' => 'widgettype']);

    $account = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node form display',
      'add widget entities',
      'administer widget entities',
      'delete widget entities',
      'edit widget entities',
      'access widget overview',
      'administer widget fields',
      'view published widget entities',
      'view unpublished widget entities',
    ]);
    $this->drupalLogin($account);
    $this->entityAddWidgetField('node', $this->nodeBundle->id(), 'widgets', 'Widgets', 'widget_reference_ief_complex');
  }

  /**
   * Tests the add widget button with modal form.
   */
  public function testAddWidgetButton() {
    // Check that field added for content type.
    $this->drupalGet('/admin/structure/types/manage/' . $this->nodeBundle->id() . '/fields');
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->elementContains('css', '#widgets .priority-medium', 'widgets');

    $this->drupalGet('/admin/structure/types/manage/' . $this->nodeBundle->id() . '/form-display');
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->fieldValueEquals('fields[widgets][type]', 'widget_reference_ief_complex');

    $this->drupalGet('/node/add/' . $this->nodeBundle->id());
    $this->assertSession()->statusCodeEquals('200');
    $node_page = $this->getSession()->getPage();
    $this->assertSession()->buttonExists('Add new widget');
    $node_page->pressButton('Add new widget');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $name = $this->randomString(50);
    $body = $this->randomString(255);
    $edit = [
      'widgets[form][inline_entity_form][name][0][value]' => $name,
      'widgets[form][inline_entity_form][body][0][value]' => $body,
    ];

    $this->drupalPostForm(NULL, $edit, 'Create widget');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $title = $this->randomString();

    $node_edit = [
      'title[0][value]' => $title,
    ];
    $this->drupalPostForm(NULL, $node_edit, 'edit-submit');
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->pageTextContains($name);
    $this->assertSession()->pageTextContains($body);
    $this->assertSession()->pageTextContains($title);
  }

  /**
   * Tests the widget reuse.
   */
  public function testReuseWidget() {
    $this->drupalGet('/admin/content/widgets');
    $this->assertSession()->statusCodeEquals('200');

    $this->drupalGet('/admin/content/widget/add/' . $this->widgetBundle);
    $this->assertSession()->statusCodeEquals('200');

    $name = 'Widgetname';
    $body = $this->randomString(255);
    $edit = [
      'name[0][value]' => $name,
      'body[0][value]' => $body,
    ];
    $this->drupalPostForm(NULL, $edit, 'edit-submit');
    $this->assertSession()->statusCodeEquals('200');

    $this->drupalGet('/admin/content/widgets');
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($name);

    $this->drupalGet('/node/add/' . $this->nodeBundle->id());
    $this->assertSession()->statusCodeEquals('200');
    $node_page = $this->getSession()->getPage();
    $this->assertSession()->buttonExists('Add existing widget');
    $node_page->pressButton('Add existing widget');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $autocomplete_field = $node_page->findField('widgets[form][entity_id]');
    $autocomplete_field->setValue($name);
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    $this->getSession()->getDriver()->click($node_page->find('css', '.ui-autocomplete li')->getXpath());
    $node_page->pressButton('Add widget');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $title = $this->randomString();
    $node_edit = [
      'title[0][value]' => $title,
    ];

    $this->drupalPostForm(NULL, $node_edit, 'edit-submit');
    $this->assertSession()->statusCodeEquals('200');
    $this->assertSession()->pageTextContains($name);
    $this->assertSession()->pageTextContains($body);
    $this->assertSession()->pageTextContains($title);
  }

}
