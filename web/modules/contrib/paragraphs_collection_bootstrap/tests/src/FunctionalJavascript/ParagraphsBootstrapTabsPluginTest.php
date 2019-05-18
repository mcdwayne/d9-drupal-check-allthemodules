<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript;

/**
 * Tests for the Tabs plugin.
 *
 * @group paragraphs_collection_bootstrap
 */
class ParagraphsBootstrapTabsPluginTest extends ParagraphsBootstrapJavascriptTestBase {

  /**
   * Tests Tabs plugin.
   */
  public function testTabs() {
    $user = $this->createUser([
      'administer content types',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer node form display',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);
    $this->drupalLogin($user);

    // Tabs paragraph type edit form.
    $this->drupalGet('admin/structure/paragraphs_type/pcb_tabs');
    $this->assertSession()->checkboxChecked('behavior_plugins[style][enabled]');
    $this->assertSession()->pageTextContains('Allows the selection of a pre-defined visual style for a whole paragraph.');
    $this->assertSession()->pageTextContains('Restrict available styles to a certain style group. Select "- None -" to allow all styles.');

    $this->assertSession()->selectExists('edit-behavior-plugins-style-settings-group');
    $this->assertSession()->optionExists('edit-behavior-plugins-style-settings-group', 'Tabs');

    $this->assertSession()->checkboxChecked('behavior_plugins[pcb_tabs][enabled]');
    $this->assertSession()->pageTextContains('Displays paragraphs in bootstrap tabs.');
    $this->assertSession()->pageTextContains('Choose the field to be used as container for tab items.');

    $this->assertSession()->selectExists('edit-behavior-plugins-pcb-tabs-settings-container-field');
    $this->assertSession()->optionExists('edit-behavior-plugins-pcb-tabs-settings-container-field', 'field_pcb_tabs_container');

    // Create node.
    $this->drupalGet('node/add/paragraphed_test');

    // Add title.
    $this->getSession()->getPage()->fillField('edit-title-0-value', 'Paragraphed test');

    // Add Tab.
    $toggle_button_xpath = '//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//ul/li[contains(@class, "dropbutton-action")]/input[@id="field-paragraphs-pcb-tabs-add-more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add content to Tabs.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-tabs-container-0-subform-field-pcb-tabs-content"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-tabs-container-0-subform-field-pcb-tabs-content"]//ul/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_field_pcb_tabs_container_0_subform_field_pcb_tabs_content_text_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('field_paragraphs[0][subform][field_pcb_tabs_container][0][subform][field_pcb_tabs_title][0][value]', 'Title #1');
    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_pcb_tabs_container][0][subform][field_pcb_tabs_content][0][subform][paragraphs_text][0][value]')->setValue(t('Text inside tab.'));

    // Add another tab.
    $add_button_xpath = '//input[@name="field_paragraphs_0_subform_field_pcb_tabs_container_pcb_tabs_item_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add content to it.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-tabs-container-1-subform-field-pcb-tabs-content"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-tabs-container-1-subform-field-pcb-tabs-content"]//ul/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_field_pcb_tabs_container_1_subform_field_pcb_tabs_content_text_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('field_paragraphs[0][subform][field_pcb_tabs_container][1][subform][field_pcb_tabs_title][0][value]', 'Title #2');
    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_pcb_tabs_container][1][subform][field_pcb_tabs_content][0][subform][paragraphs_text][0][value]')->setValue(t('Another text inside tab.'));

    // Change to behavior plugin.
    $this->getSession()->getPage()->clickLink('Behavior');

    // Assert the fade checkbox.
    $this->assertSession()->checkboxNotChecked('field_paragraphs[0][behavior_plugins][pcb_tabs][fade]');
    $this->assertSession()->pageTextContains('Check to enable fade effect for tabs.');

    $this->getSession()->getPage()->clickLink('Behavior');

    // Select fade checkbox.
    $this->getSession()->getPage()->checkField('field_paragraphs[0][behavior_plugins][pcb_tabs][fade]');
    $this->assertSession()->checkboxChecked('field_paragraphs[0][behavior_plugins][pcb_tabs][fade]');

    // Click to add another Tab container.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-add-more"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-add-more"]//ul/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_pcb_tabs_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // It is necessary to select plugin style.
    $this->getSession()->getPage()->clickLink('Behavior');
    $this->getSession()->getPage()->selectFieldOption('field_paragraphs[1][behavior_plugins][style][style_wrapper][style]', 'tab-default');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Tabs styles are required.');

    // Select justified style tab.
    $this->getSession()->getPage()->selectFieldOption('field_paragraphs[0][behavior_plugins][style][style_wrapper][style]', 'tab-justified');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Title #1');
    $this->assertSession()->pageTextContains('Title #2');
    $this->assertSession()->pageTextContains('Text inside tab.');

    $tabs_css_xpath = '//div[@id="bootstrap-tabs"]/ul[contains(@class, "nav-tabs") and contains(@class, "nav-justified")]/li/a[@data-toggle="tab"]';
    $this->assertSession()->elementExists('xpath', $tabs_css_xpath);

    $tabs_css_xpath = '//div[@id="bootstrap-tabs"]/div[contains(@class, "tab-content")]/div[contains(@class, "fade")]';
    $this->assertSession()->elementExists('xpath', $tabs_css_xpath);

    $tabs_css_xpath = '//div[@id="bootstrap-tabs--2"]';
    $this->assertSession()->elementExists('xpath', $tabs_css_xpath);

    // Test for summary.
    $node = $this->getNodeByTitle('Paragraphed test');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Collapse first tab so we can check summary.
    $collapse_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-top-links-operations"]/li[contains(@class, "collapse-button")]/input';
    $this->getSession()->getPage()->find('xpath', $collapse_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('2 children, Title #1, Title #2, Style: Tabs justified, Tabs fade: enabled');

    // Delete field from Carousel paragraph type.
    $this->drupalGet('/admin/structure/paragraphs_type/pcb_tabs/fields/paragraph.pcb_tabs.field_pcb_tabs_container/delete');

    $this->getSession()->getPage()->pressButton('Delete');

    $this->drupalGet('admin/structure/paragraphs_type/pcb_tabs');

    $this->assertSession()->pageTextContains('There are no entity reference revisions fields available. Please add at least one in the Manage fields page.');

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
  }

}
