<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript;

/**
 * Tests for the Accordion plugin.
 *
 * @group paragraphs_collection_bootstrap
 */
class ParagraphsBootstrapAccordionPluginTest extends ParagraphsBootstrapJavascriptTestBase {

  /**
   * Tests Accordion plugin.
   */
  public function testAccordion() {
    $user = $this->drupalCreateUser([
      'administer content types',
      'administer paragraphs types',
      'administer paragraph fields',
    ]);
    $this->drupalLogin($user);

    // Accordion paragraph type edit form.
    $this->drupalGet('admin/structure/paragraphs_type/pcb_accordion');
    $this->assertSession()->checkboxChecked('behavior_plugins[pcb_accordion][enabled]');
    $this->assertSession()->pageTextContains('Displays paragraphs in bootstrap accordion.');
    $this->assertSession()->pageTextContains('Choose the field to be used as accordion items.');

    $this->assertSession()->selectExists('edit-behavior-plugins-pcb-accordion-settings-container-field');
    $this->assertSession()->optionExists('edit-behavior-plugins-pcb-accordion-settings-container-field', 'field_pcb_accordion_container');

    // Create node.
    $this->drupalGet('node/add/paragraphed_test');

    // Add title.
    $this->getSession()->getPage()->fillField('edit-title-0-value', 'Paragraphed test');

    // Click to add Accordion.
    $toggle_button_xpath = '//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//li[contains(@class, "dropbutton-action")]/input[@id="field-paragraphs-pcb-accordion-add-more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Choose paragraphs type for title in Accordion item.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-0-subform-field-pcb-accordion-title"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-0-subform-field-pcb-accordion-title"]//ul/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_field_pcb_accordion_container_0_subform_field_pcb_accordion_title_title_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('field_paragraphs[0][subform][field_pcb_accordion_container][0][subform][field_pcb_accordion_title][0][subform][paragraphs_title][0][value]', 'Title #1');

    // Choose paragraphs type for content in Accordion item.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-0-subform-field-pcb-accordion-content"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-0-subform-field-pcb-accordion-content"]//ul/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_field_pcb_accordion_container_0_subform_field_pcb_accordion_content_text_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_pcb_accordion_container][0][subform][field_pcb_accordion_content][0][subform][paragraphs_text][0][value]')->setValue(t('Accordion content text #1.'));

    // Add another content to Accordion.
    $this->getSession()->getPage()->pressButton('Add Accordion Item');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Choose paragraphs type for title in second Accordion item.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-1-subform-field-pcb-accordion-title"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-1-subform-field-pcb-accordion-title"]//ul/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_field_pcb_accordion_container_1_subform_field_pcb_accordion_title_title_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('field_paragraphs[0][subform][field_pcb_accordion_container][1][subform][field_pcb_accordion_title][0][subform][paragraphs_title][0][value]', 'Title #2');

    // Choose paragraphs type for content in second Accordion item.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-1-subform-field-pcb-accordion-content"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $add_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-pcb-accordion-container-1-subform-field-pcb-accordion-content"]//ul/li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_subform_field_pcb_accordion_container_1_subform_field_pcb_accordion_content_text_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_pcb_accordion_container][1][subform][field_pcb_accordion_content][0][subform][paragraphs_text][0][value]')->setValue(t('Accordion content text #2.'));

    // Save and publish.
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Title #1');
    $this->assertSession()->pageTextContains('Accordion content text #1.');
    $this->assertSession()->pageTextContains('Title #2');
    $this->assertSession()->pageTextContains('Accordion content text #2.');

    // Inspect HTML.
    $accordion_css_xpath = '//div[@id="collapse--0" and contains(@class, "show")]';
    $this->assertSession()->elementExists('xpath', $accordion_css_xpath);

    $accordion_css_xpath = '//div[@id="heading--1"]/h5/a[contains(@class, "collapsed")]';
    $this->assertSession()->elementExists('xpath', $accordion_css_xpath);

    // Delete field from Accordion paragraph type.
    $this->drupalGet('/admin/structure/paragraphs_type/pcb_accordion/fields/paragraph.pcb_accordion.field_pcb_accordion_container/delete');

    $this->getSession()->getPage()->pressButton('Delete');

    $this->drupalGet('admin/structure/paragraphs_type/pcb_accordion');

    $this->assertSession()->pageTextContains('There are no entity reference revisions fields available. Please add at least one in the Manage fields page.');

    $node = $this->getNodeByTitle('Paragraphed test');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
  }

}
