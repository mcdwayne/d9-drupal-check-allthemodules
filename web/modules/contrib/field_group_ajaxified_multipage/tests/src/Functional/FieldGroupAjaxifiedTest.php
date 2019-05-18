<?php

namespace Drupal\Tests\field_group_ajaxified_multipage\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;
use Drupal\Core\Url;

/**
 * Functional tests.
 *
 * @ingroup field_group_ajaxified_multipage
 *
 * @group field_group_ajaxified_multipage
 */
class FieldGroupAjaxifiedTest extends BrowserTestBase {

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
    $this->createField('field_test_2', $type_name, $display);

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
   * Tests that the home page loads with a 200 response.
   */
  public function testFrontpage() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertResponse(200);
  }

  /**
   * Test settings Format page title.
   */
  public function testPageTitle() {

    $this->updateSettingField($this->nodeType, $this->fields['group'], 'page_header', 0);
    $this->drupalGet('/node/add/' . $this->nodeType);
    $assert = $this->assertSession();

    $assert->elementTextNotContains('css', 'h2 span', 'Multipage step 1');
    $assert->elementTextNotContains('css', 'h2 span', 'Step 1 of 2');
    $assert->elementTextNotContains('css', 'h2 span', 'Step 1 of 2 Multipage step 1');

    $this->updateSettingField($this->nodeType, $this->fields['group'], 'page_header', 1);
    $this->drupalGet('/node/add/' . $this->nodeType);
    $assert->elementTextContains('css', 'h2 span', 'Multipage step 1');
    $assert->elementTextNotContains('css', 'h2 span', 'Step 1 of 2');
    $assert->elementTextNotContains('css', 'h2 span', 'Step 1 of 2 Multipage step 1');

    $this->updateSettingField($this->nodeType, $this->fields['group'], 'page_header', 2);
    $this->drupalGet('/node/add/' . $this->nodeType);
    $assert->elementTextNotContains('css', 'h2 span', 'Multipage group');
    $assert->elementTextContains('css', 'h2 span', 'Step 1 of 2');
    $assert->elementTextNotContains('css', 'h2 span', 'Step 1 of 2 Multipage step 1');

    $this->updateSettingField($this->nodeType, $this->fields['group'], 'page_header', 3);
    $this->drupalGet('/node/add/' . $this->nodeType);
    $assert->elementTextContains('css', 'h2 span', 'Step 1 of 2 Multipage step 1');
  }

}
