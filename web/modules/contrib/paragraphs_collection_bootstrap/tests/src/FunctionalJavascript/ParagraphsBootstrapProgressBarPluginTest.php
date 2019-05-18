<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript;

/**
 * Progress Bar plugin test class.
 *
 * @see \Drupal\paragraphs_collection_bootstrap\Plugin\paragraphs\Behavior\ParagraphsBootstrapProgressBarPlugin
 * @group paragraphs_collection_bootstrap
 */
class ParagraphsBootstrapProgressBarPluginTest extends ParagraphsBootstrapJavascriptTestBase {

  /**
   * Test Progress Bar.
   */
  public function testProgressBar() {
    $this->loginAsAdmin([
      'edit behavior plugin settings',
    ]);
    $this->drupalGet('node/add/paragraphed_test');

    $this->getSession()->getPage()->fillField('edit-title-0-value', 'Progress Bar Test');
    $toggle_button_xpath = '//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()->getPage()->find('xpath', $toggle_button_xpath)->click();

    $progress_button_xpath = '//li[contains(@class, "dropbutton-action")]/input[@id="field-paragraphs-pcb-progress-add-more"]';
    $this->getSession()->getPage()->find('xpath', $progress_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Paragraphs Collection Bootstrap Progress Bar Width. Default value is 25%.');
    $this->assertSession()->pageTextContains('Paragraphs Collection Progress Bar Height. Default value is 16px.');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_pcb_progress_bar][0][subform][field_pcb_progress_bar_width][0][value]', 25);
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_pcb_progress_bar][0][subform][field_pcb_progress_bar_height][0][value]', 16);

    $this->getSession()->getPage()->clickLink('Behavior');

    $this->assertSession()->checkboxChecked('field_paragraphs[0][subform][field_pcb_progress_bar][0][behavior_plugins][pcb_progress_bar][label]');
    $this->assertSession()->checkboxNotChecked('field_paragraphs[0][subform][field_pcb_progress_bar][0][behavior_plugins][pcb_progress_bar][striped]');
    $this->assertSession()->checkboxNotChecked('field_paragraphs[0][subform][field_pcb_progress_bar][0][behavior_plugins][pcb_progress_bar][animated]');

    $this->assertSession()->pageTextContains('Apply a stripe via CSS gradient over the progress bar’s background color.');
    $this->assertSession()->pageTextContains('The striped gradient can also be animated. Animate the stripes right to left via CSS3 animations.');
    $this->assertSession()->pageTextContains('Add/remove label to your progress bar.');

    $this->getSession()->getPage()->checkField('field_paragraphs[0][subform][field_pcb_progress_bar][0][behavior_plugins][pcb_progress_bar][animated]');
    $this->getSession()->getPage()->checkField('field_paragraphs[0][subform][field_pcb_progress_bar][0][behavior_plugins][pcb_progress_bar][striped]');

    $collapse_button_xpath = '//li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_0_collapse"]';
    $this->getSession()->getPage()->find('xpath', $collapse_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('1 child, Striped: YES, Animated: YES, Label: YES');
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('Progress Bar Test');
    $this->assertSession()->pageTextContains('25%');
    $progress_xpath = '//div[contains(@class, "progress")]/div[contains(@class, "progress-bar") and contains(@class, "striped") and contains(@class, "animated")]';
    $this->assertSession()->elementExists('xpath', $progress_xpath);
  }

}
