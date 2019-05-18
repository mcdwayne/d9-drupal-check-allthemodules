<?php

namespace Drupal\Tests\field_group_ajaxified_multipage\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Tests\field_group_ajaxified_multipage\Functional\FieldGroupAjaxifiedTrait;

/**
 * JavaScript tests.
 *
 * @ingroup field_group_ajaxified_multipage
 *
 * @group field_group_ajaxified_multipage
 */
class FieldGroupAjaxifiedTest extends JavascriptTestBase {

  use FieldGroupTestTrait;
  use FieldGroupAjaxifiedTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_test',
    'field_group',
    'field_group_ajaxified_multipage',
  ];

  /**
   * Fields created.
   *
   * @var array
   */
  public $fields = [];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Node type.
   *
   * @var string
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Move this to a trait.
    $this->user = $this->drupalCreateUser([
      'administer content types',
      'bypass node access',
    ]);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $this->nodeType = $type->id();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $type_name . '.default');

    $this->createField('field_test', $type_name, $display, ['required' => TRUE]);
    $this->createField('field_test_2', $type_name, $display, ['required' => FALSE]);

    $data = [
      'format_type' => 'multipage',
      'label' => 'Multipage step 1',
      'children' => [
        0 => 'field_test',
      ],
      'format_settings' => [
        'label' => 'Multipage step 1',
      ],
    ];

    $step_1_fieldgroup = $this->createGroup('node', $this->nodeType, 'form', 'default', $data);

    $data = [
      'label' => 'Multipage step 2',
      'children' => [
        0 => 'field_test_2',
      ],
      'format_type' => 'multipage',
      'format_settings' => [
        'label' => 'Multipage step 2',
      ],
    ];

    $step_2_fieldgroup = $this->createGroup('node', $this->nodeType, 'form', 'default', $data);

    $data = [
      'label' => 'Multipage group',
      'children' => [
        0 => $step_1_fieldgroup->group_name,
        1 => $step_2_fieldgroup->group_name,
      ],
      'format_type' => 'multipage_group',
      'format_settings' => [
        'ajaxify' => '1',
        'nonjs_multistep' => '0',
        'classes' => ' group-steps field-group-multipage-group',
        'page_header' => '3',
        'page_counter' => '1',
      ],
    ];

    $group = $this->createGroup('node', $this->nodeType, 'form', 'default', $data);
    $this->fields['group'] = $group->group_name;

    // Save display + create node.
    $type->save();
    $display->save();
    $this->drupalLogin($this->user);
  }

  /**
   * Main workflow.
   *
   * It will test that the fields are showed in the right way and you can
   * navigate through the steps.
   */
  public function testFieldgroupAjaxifiedMainWorkflow() {
    $this->drupalGet('/node/add/' . $this->nodeType);
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $this->assertResponse(200);

    // Check fields from first step.
    $assert->elementExists('xpath', '//input[@name="field_test[0][value]"]');
    $assert->elementNotExists('xpath', '//input[@name="field_test_2[0][value]"]');

    $assert->pageTextContains('Step 1 of 2 Multipage step 1');
    $assert->pageTextContains('Multipage step 1');

    $page->fillField('title[0][value]', 'FIELD GROUP AJAXIFIED MULTIPAGE');
    $n1 = mt_rand(0, 1000);
    $page->fillField('field_test[0][value]', strval($n1));
    $page->pressButton('Next step');

    // Wait for ajax.
    $assert->assertWaitOnAjaxRequest();
    // @code $this->createScreenshot('/tmp/test.jpg');
    // Check fields from second step.
    $assert->elementNotExists('xpath', '//input[@name="field_test[0][value]"]');
    $assert->elementExists('xpath', '//input[@name="field_test_2[0][value]"]');

    $assert->pageTextContains('Step 2 of 2 Multipage step 2');
    $assert->pageTextContains('Multipage step 2');

    $n2 = mt_rand(0, 1000);
    $page->fillField('field_test_2[0][value]', strval($n2));

    // Save form.
    $page->pressButton('Save');

    $assert->pageTextContains(strval($n1));
    $assert->pageTextContains(strval($n2));
  }

  /**
   * Test required fields.
   */
  public function testRequiredFields() {
    $this->drupalGet('/node/add/' . $this->nodeType);
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $this->assertResponse(200);

    // Check fields from first step.
    $assert->elementExists('xpath', '//input[@name="field_test[0][value]"]');
    $assert->elementNotExists('xpath', '//input[@name="field_test_2[0][value]"]');

    $assert->pageTextContains('Step 1 of 2 Multipage step 1');
    $assert->pageTextContains('Multipage step 1');

    $page->fillField('title[0][value]', 'FIELD GROUP AJAXIFIED MULTIPAGE');
    $page->pressButton('Next step');

    // Wait for ajax.
    $assert->assertWaitOnAjaxRequest();

    $assert->pageTextContains('Step 1 of 2 Multipage step 1');
    $assert->pageTextContains('Multipage step 1');

    // Check fields from first step.
    $assert->elementExists('xpath', '//input[@name="field_test[0][value]"]');
    $assert->elementNotExists('xpath', '//input[@name="field_test_2[0][value]"]');

    $n1 = mt_rand(0, 1000);
    $page->fillField('field_test[0][value]', strval($n1));
    $page->pressButton('Next step');

    // Wait for ajax.
    $assert->assertWaitOnAjaxRequest();

    // Check fields from second step.
    $assert->elementNotExists('xpath', '//input[@name="field_test[0][value]"]');
    $assert->elementExists('xpath', '//input[@name="field_test_2[0][value]"]');

    $assert->pageTextContains('Step 2 of 2 Multipage step 2');
    $assert->pageTextContains('Multipage step 2');

  }

  /**
   * Test that the values are kept when you come back the step.
   */
  public function testKeptValues() {
    $this->drupalGet('/node/add/' . $this->nodeType);
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $this->assertResponse(200);

    // Check fields from first step.
    $assert->elementExists('xpath', '//input[@name="field_test[0][value]"]');
    $assert->elementNotExists('xpath', '//input[@name="field_test_2[0][value]"]');

    $assert->pageTextContains('Step 1 of 2 Multipage step 1');
    $assert->pageTextContains('Multipage step 1');

    $page->fillField('title[0][value]', 'FIELD GROUP AJAXIFIED MULTIPAGE');

    $n1 = mt_rand(0, 1000);
    $page->fillField('field_test[0][value]', strval($n1));
    $page->pressButton('Next step');

    // Wait for ajax.
    $assert->assertWaitOnAjaxRequest();

    $assert->pageTextContains('Multipage step 2');

    // Previous step form.
    $page->pressButton('Previous step');

    // Wait for ajax.
    $assert->assertWaitOnAjaxRequest();

    // Check that the field contains the right value.
    $assert->fieldValueEquals('field_test[0][value]', strval($n1));
    $n3 = mt_rand(0, 1000);
    $page->fillField('field_test[0][value]', strval($n3));

    $page->pressButton('Next step');

    // Wait for ajax.
    $assert->assertWaitOnAjaxRequest();

    // Check that the field contains the right value.
    $n2 = mt_rand(0, 1000);
    $page->fillField('field_test_2[0][value]', strval($n2));

    // Save form.
    $page->pressButton('Save');

    $assert->pageTextContains(strval($n3));
    $assert->pageTextContains(strval($n2));

  }

  /**
   * Test settings Page counter at bottom.
   */
  public function testPageCounter() {
    $this->updateSettingField($this->nodeType, $this->fields['group'], 'page_counter', 0);
    $this->drupalGet('/node/add/' . $this->nodeType);
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $assert->elementNotExists('css', 'span.multipage-counter_ajax');

    $this->updateSettingField($this->nodeType, $this->fields['group'], 'page_counter', 1);
    $this->drupalGet('/node/add/' . $this->nodeType);

    $assert->elementExists('css', 'span.multipage-counter_ajax');
    $assert->pageTextContains('1 / 2');

    $page->fillField('title[0][value]', 'FIELD GROUP AJAXIFIED MULTIPAGE');

    $n1 = mt_rand(0, 1000);
    $page->fillField('field_test[0][value]', strval($n1));

    $page->pressButton('Next step');
    $assert->assertWaitOnAjaxRequest();

    $assert->elementExists('css', 'span.multipage-counter_ajax');
    $assert->pageTextContains('2 / 2');

    $page->pressButton('Previous step');
    $assert->assertWaitOnAjaxRequest();

    $assert->elementExists('css', 'span.multipage-counter_ajax');
    $assert->pageTextContains('1 / 2');
  }

  /**
   * Test settings Button label.
   */
  public function testButtonLabel() {
    $this->updateSettingField($this->nodeType, $this->fields['group'], 'button_label', 0);
    $this->drupalGet('/node/add/' . $this->nodeType);
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $page->fillField('title[0][value]', 'FIELD GROUP AJAXIFIED MULTIPAGE');

    $n1 = mt_rand(0, 1000);
    $page->fillField('field_test[0][value]', strval($n1));

    $page->pressButton('Next step');

    $assert->assertWaitOnAjaxRequest();

    $page->pressButton('Previous step');
    $assert->assertWaitOnAjaxRequest();

    $this->updateSettingField($this->nodeType, $this->fields['group'], 'button_label', 1);
    $this->updateSettingField($this->nodeType, $this->fields['group'], 'button_label_next', 'Next button label');
    $this->updateSettingField($this->nodeType, $this->fields['group'], 'button_label_prev', 'Previous button label');
    $this->drupalGet('/node/add/' . $this->nodeType);

    $page->fillField('title[0][value]', 'FIELD GROUP AJAXIFIED MULTIPAGE');

    $page->fillField('field_test[0][value]', strval($n1));

    $page->pressButton('Next button label');

    $assert->assertWaitOnAjaxRequest();

    $page->pressButton('Previous button label');
    $assert->assertWaitOnAjaxRequest();
  }

}
