<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript;

/**
 * Tests for the Tooltip plugin.
 *
 * @group paragraphs_collection_bootstrap.
 */
class ParagraphsBootstrapTooltipPluginTest extends ParagraphsBootstrapJavascriptTestBase {

  /**
   * Tests Tooltip plugin.
   */
  public function testTooltip() {
    $this->drupalLogin($this->createUser([
      'administer content types',
      'administer paragraphs types',
      'edit behavior plugin settings',
      'edit any paragraphed_test content',
    ]));

    $this->drupalPostForm('admin/structure/paragraphs_type/text', ['behavior_plugins[pcb_tooltip][enabled]' => TRUE], t('Save'));

    // Create node.
    $this->drupalGet('node/add/paragraphed_test');

    // Add title.
    $this->getSession()
      ->getPage()
      ->fillField('edit-title-0-value', 'Paragraphed test');

    // Add text paragraph.
    $toggle_button_xpath = '//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()
      ->getPage()
      ->find('xpath', $toggle_button_xpath)
      ->click();

    $add_button_xpath = '//ul/li[contains(@class, "dropbutton-action")]/input[@id="field-paragraphs-text-add-more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()
      ->fieldExists('field_paragraphs[0][subform][paragraphs_text][0][value]')
      ->setValue(t('Text content.'));

    // Change to behavior plugin.
    $this->getSession()->getPage()->clickLink('Behavior');

    // Set tooltip text.
    $this->assertSession()
      ->fieldExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][content][value]')
      ->setValue(t('Tooltip text that appears.'));

    // Test for animation option.
    $this->assertSession()
      ->pageTextContains('Apples a CSS fade transition to the tooltip.');
    $this->assertSession()
      ->checkboxChecked('field_paragraphs[0][behavior_plugins][pcb_tooltip][animation]');

    $this->getSession()
      ->getPage()
      ->checkField('field_paragraphs[0][behavior_plugins][pcb_tooltip][animation]');

    $this->assertSession()
      ->pageTextContains('Appends the tooltip to a specific element. Example: container: \'body\'. ' .
        'This option is particularly useful in that it allows you to position the tooltip in the flow of the document near the triggering element - ' .
        'which will prevent the tooltip from floating away from the triggering element during a window resize.');

    // Assert default value for delay parameter.
    $this->assertSession()
      ->pageTextContains('Delay showing and hiding the popover (ms) - does not apply to manual trigger type.');
    $this->assertSession()
      ->fieldValueEquals('field_paragraphs[0][behavior_plugins][pcb_tooltip][delay]', '0');

    $this->getSession()
      ->getPage()
      ->fillField('field_paragraphs[0][behavior_plugins][pcb_tooltip][delay]', '1000');

    // Test available options for placement.
    $this->assertSession()->pageTextContains('The placement of the tooltip');
    $this->assertSession()
      ->selectExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][placement]');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][placement]', 'top');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][placement]', 'left');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][placement]', 'bottom');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][placement]', 'right');

    $this->getSession()
      ->getPage()
      ->selectFieldOption('field_paragraphs[0][behavior_plugins][pcb_tooltip][placement]', 'left');

    // Test available options for trigger.
    $this->assertSession()
      ->pageTextContains('Choose what trigger to use for the tooltip to appear. You may choose multiple triggers, "manual" cannot be combined with any other trigger.');
    $this->assertSession()
      ->selectExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]', 'click');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]', 'hover');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]', 'focus');
    $this->assertSession()
      ->optionExists('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]', 'manual');

    $this->getSession()
      ->getPage()
      ->selectFieldOption('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]', 'hover');
    $this->getSession()
      ->getPage()
      ->selectFieldOption('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]', 'manual', TRUE);

    // Test tooltip offset.
    $this->assertSession()
      ->pageTextContains('Offset of the tooltip relative to its target. For more information refer to Tether\'s offset docs.');
    $this->assertSession()
      ->fieldValueEquals('field_paragraphs[0][behavior_plugins][pcb_tooltip][offset]', '0 0');

    $this->getSession()
      ->getPage()
      ->fillField('field_paragraphs[0][behavior_plugins][pcb_tooltip][offset]', '0 200');

    $this->getSession()->getPage()->pressButton('Save');

    // Technically, you can't select manual trigger with any other.
    $this->assertSession()
      ->pageTextContains('"Manual" cannot be combined with any other trigger in behavior settings.');

    $this->getSession()->getPage()->clickLink('Behavior');

    $this->getSession()
      ->getPage()
      ->selectFieldOption('field_paragraphs[0][behavior_plugins][pcb_tooltip][trigger][]', 'hover');
    $this->getSession()->getPage()->pressButton('Save');

    // Test tooltip on the page.
    $this->assertSession()->pageTextContains('Text content.');
    $tooltip_html_xpath = '//div[@data-toggle="tooltip" and @data-animation="true" and @data-delay="1000" and @data-placement="left" and @data-trigger="hover" and @data-offset="0 200" and @title="Tooltip text that appears."]';
    $this->assertSession()->elementExists('xpath', $tooltip_html_xpath);

    $node = $this->getNodeByTitle('Paragraphed test');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Test for summary.
    $collapse_button_xpath = '//li[contains(@class, "collapse-button")]/input';
    $this->getSession()
      ->getPage()
      ->find('xpath', $collapse_button_xpath)
      ->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()
      ->pageTextContains('Text content., Content: , Animation: enabled, Container: , Delay: 1000, Placement: left, Trigger: hover, Offset: 0 200');
  }
}
