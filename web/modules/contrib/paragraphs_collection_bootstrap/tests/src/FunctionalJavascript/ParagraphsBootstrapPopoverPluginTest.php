<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Tests for the Popover plugin.
 *
 * @group paragraphs_collection_bootstrap.
 */
class ParagraphsBootstrapPopoverPluginTest extends ParagraphsBootstrapJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a new paragraphs type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'text_test',
      'id' => 'text_test',
    ]);
    $paragraph_type->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_text_test',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '1',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'text_test',
      'settings' => [
        'size' => 60,
        'placeholder' => '',
      ],
    ]);
    $field->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'text_test',
      'mode' => 'default',
      'status' => TRUE,
      'settings' => [
        'size' => 60,
        'placeholder' => '',
      ],
    ])->setComponent('field_text_test', ['type' => 'string_textfield']);
    $form_display->save();

    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'text_test',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_text_test', ['label' => 'hidden']);
    $view_display->save();
  }

  /**
   * Tests Popover plugin.
   */
  public function testPopover() {
    $this->drupalLogin($this->createUser([
      'administer content types',
      'administer paragraphs types',
      'administer paragraph fields',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]));

    $this->drupalPostForm('admin/structure/paragraphs_type/text_test', ['behavior_plugins[pcb_popover][enabled]' => TRUE], t('Save'));

    // Create node.
    $this->drupalGet('node/add/paragraphed_test');

    // Add title.
    $this->getSession()->getPage()->fillField('edit-title-0-value', 'Paragraphed test');

    // Add paragraph container.
    $toggle_button_xpath = '//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//ul/li[contains(@class, "dropbutton-action")]/input[@id="field-paragraphs-container-add-more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add text paragraph inside container.
    $toggle_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-subform-paragraphs-container-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-subform-paragraphs-container-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_paragraphs_container_paragraphs_text_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->fieldExists('field_paragraphs[0][subform][paragraphs_container_paragraphs][0][subform][paragraphs_text][0][value]')->setValue(t('Text inside container.'));

    // Add another container inside this one.
    $toggle_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-subform-paragraphs-container-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-subform-paragraphs-container-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_paragraphs_container_paragraphs_container_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add title inside this other container.
    $toggle_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-subform-paragraphs-container-paragraphs-1-subform-paragraphs-container-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-subform-paragraphs-container-paragraphs-1-subform-paragraphs-container-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_paragraphs_container_paragraphs_1_subform_paragraphs_container_paragraphs_title_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('field_paragraphs[0][subform][paragraphs_container_paragraphs][1][subform][paragraphs_container_paragraphs][0][subform][paragraphs_title][0][value]', 'Title inside container which is inside container.');

    // Add link paragraph.
    $toggle_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_link_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('field_paragraphs[1][subform][paragraphs_link][0][uri]', '#some');
    $this->getSession()->getPage()->fillField('field_paragraphs[1][subform][paragraphs_link][0][title]', 'Some link');

    // Now, add text_test paragraph.
    $toggle_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-add-more-operations"]/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_text_test_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('field_paragraphs[2][subform][field_text_test][0][value]', 'Popover text.');

    // Save and publish.
    $this->getSession()->getPage()->pressButton('Save');

    $node = $this->getNodeByTitle('Paragraphed test');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Change to behavior plugin.
    $this->getSession()->getPage()->clickLink('Behavior');

    // Test for animation option.
    $this->assertSession()->pageTextContains('Apples a CSS fade transition to the popover.');
    $this->assertSession()->checkboxChecked('field_paragraphs[2][behavior_plugins][pcb_popover][animation]');

    $this->assertSession()->pageTextContains('Appends the popover to a specific element. Example: container: \'body\'. ' .
      'This option is particularly useful in that it allows you to position the popover in the flow of the document near the triggering element - ' .
      'which will prevent the popover from floating away from the triggering element during a window resize.');

    // Test popover content.
    $this->assertSession()->pageTextContains('A paragraph to be used as popover content.');
    // Test if there are options for popover content dropdown button. Options
    // are appearing in the following order: Container (first), Text , Container
    // (inside first one), Title and Link.
    $this->assertSession()->selectExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-popover-content');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-popover-content', 'Container (4)');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-popover-content', 'Text (1)');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-popover-content', 'Container (3)');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-popover-content', 'Title (2)');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-popover-content', 'Link (5)');

    $this->getSession()->getPage()->selectFieldOption('edit-field-paragraphs-2-behavior-plugins-pcb-popover-popover-content', 'Title (2)');

    // Assert default value for delay parameter.
    $this->assertSession()->pageTextContains('Delay showing and hiding the popover (ms) - does not apply to manual trigger type.');
    $this->assertSession()->fieldValueEquals('field_paragraphs[2][behavior_plugins][pcb_popover][delay]', '0');

    $this->getSession()->getPage()->fillField('field_paragraphs[2][behavior_plugins][pcb_popover][delay]', '1000');

    // Test available options for placement.
    $this->assertSession()->pageTextContains('The placement of the popup');
    $this->assertSession()->selectExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-placement');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-placement', 'top');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-placement', 'left');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-placement', 'bottom');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-placement', 'right');

    $this->getSession()->getPage()->selectFieldOption('edit-field-paragraphs-2-behavior-plugins-pcb-popover-placement', 'left');

    // Test available options for trigger.
    $this->assertSession()->pageTextContains('Chose what trigger to use for the popup to appear. You may choose multiple triggers, "manual" cannot be combined with any other trigger.');
    $this->assertSession()->selectExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-trigger');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-trigger', 'click');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-trigger', 'hover');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-trigger', 'focus');
    $this->assertSession()->optionExists('edit-field-paragraphs-2-behavior-plugins-pcb-popover-trigger', 'manual');

    $this->getSession()->getPage()->selectFieldOption('edit-field-paragraphs-2-behavior-plugins-pcb-popover-trigger', 'hover');

    // Test popover offset.
    $this->assertSession()->pageTextContains('Offset of the popover relative to its target. For more information refer to Tether\'s constraint docs.');
    $this->assertSession()->fieldValueEquals('field_paragraphs[2][behavior_plugins][pcb_popover][offset]', '0 0');

    $this->getSession()->getPage()->fillField('field_paragraphs[2][behavior_plugins][pcb_popover][offset]', '0 200');

    $this->getSession()->getPage()->pressButton('Save');

    // Test popover on the page.
    $this->assertSession()->pageTextContains('Popover text.');
    $popover_html_xpath = '//div[@data-toggle="popover" and @data-animation="true" and @data-delay="1000" and @data-placement="left" and @data-trigger="hover" and @data-offset="0 200"]';
    $this->assertSession()->elementExists('xpath', $popover_html_xpath);
  }

}
