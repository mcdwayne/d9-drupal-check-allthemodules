<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap\FunctionalJavascript;

/**
 * Tests for the Carousel plugin.
 *
 * @group paragraphs_collection_bootstrap
 */
class ParagraphsBootstrapCarouselPluginTest extends ParagraphsBootstrapJavascriptTestBase {

  /**
   * Tests Carousel plugin.
   */
  public function testCarousel() {
    $user = $this->drupalCreateUser([
      'administer content types',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer node form display',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);
    $this->drupalLogin($user);

    // Carousel paragraph type edit form.
    $this->drupalGet('admin/structure/paragraphs_type/pcb_carousel');
    $this->assertSession()
      ->checkboxChecked('behavior_plugins[pcb_carousel][enabled]');
    $this->assertSession()
      ->pageTextContains('Displays paragraphs in bootstrap carousel.');
    $this->assertSession()
      ->pageTextContains('Choose the field to be used as carousel items.');

    $this->assertSession()
      ->selectExists('edit-behavior-plugins-pcb-carousel-settings-container-field');
    $this->assertSession()
      ->optionExists('edit-behavior-plugins-pcb-carousel-settings-container-field', 'field_pcb_carousel_container');

    // Create node.
    $this->drupalGet('node/add/paragraphed_test');

    // Add title.
    $this->getSession()
      ->getPage()
      ->fillField('edit-title-0-value', 'Paragraphed test');

    // Add Carousel.
    $toggle_button_xpath = '//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()
      ->getPage()
      ->find('xpath', $toggle_button_xpath)
      ->click();

    $add_button_xpath = '//li[contains(@class, "dropbutton-action")]/input[@id="field-paragraphs-pcb-carousel-add-more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add content to Carousel item.
    $this->getSession()
      ->getPage()
      ->fillField('field_paragraphs[0][subform][field_pcb_carousel_container][0][subform][field_pcb_carousel_caption][0][value]', 'This is some text.');

    // Add another Carousel item and add content to it.
    $this->getSession()
      ->getPage()
      ->pressButton('field_paragraphs_0_subform_field_pcb_carousel_container_pcb_carousel_item_add_more');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()
      ->getPage()
      ->fillField('field_paragraphs[0][subform][field_pcb_carousel_container][1][subform][field_pcb_carousel_caption][0][value]', 'Here is some other text.');

    // Change to behavior plugin.
    $this->getSession()->getPage()->clickLink('Behavior');

    // Assert the controls checkbox.
    $this->assertSession()
      ->checkboxNotChecked('field_paragraphs[0][behavior_plugins][pcb_carousel][controls]');
    $this->assertSession()
      ->pageTextContains('Check to use controls on the left and right side.');

    // Select controls checkbox.
    $controls_checkbox_xpath = '//input[@data-drupal-selector="edit-field-paragraphs-0-behavior-plugins-pcb-carousel-controls"]';
    $this->assertSession()->elementExists('xpath', $controls_checkbox_xpath);
    $this->getSession()
      ->getPage()
      ->find('xpath', $controls_checkbox_xpath)
      ->click();
    $this->assertSession()
      ->checkboxChecked('field_paragraphs[0][behavior_plugins][pcb_carousel][controls]');

    // Assert the indicator checkbox.
    $this->assertSession()
      ->checkboxNotChecked('field_paragraphs[0][behavior_plugins][pcb_carousel][indicator]');
    $this->assertSession()
      ->pageTextContains('Check to use indicator at the bottom of the slide.');

    // Select indicator checkbox.
    $indicator_checkbox_xpath = '//input[@data-drupal-selector="edit-field-paragraphs-0-behavior-plugins-pcb-carousel-indicator"]';
    $this->getSession()
      ->getPage()
      ->find('xpath', $indicator_checkbox_xpath)
      ->click();
    $this->assertSession()
      ->checkboxChecked('field_paragraphs[0][behavior_plugins][pcb_carousel][indicator]');

    // Click to add another Carousel.
    $toggle_button_xpath = '//div[@data-drupal-selector="edit-field-paragraphs-add-more"]//ul/li[contains(@class, "dropbutton-toggle")]/button';
    $this->getSession()
      ->getPage()
      ->find('xpath', $toggle_button_xpath)
      ->click();

    $add_button_xpath = '//li[contains(@class, "dropbutton-action")]/input[@name="field_paragraphs_pcb_carousel_add_more"]';
    $this->getSession()->getPage()->find('xpath', $add_button_xpath)->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()
      ->getPage()
      ->fillField('field_paragraphs[1][subform][field_pcb_carousel_container][0][subform][field_pcb_carousel_caption][0][value]', 'Here is text for third carousel item.');

    // Save and publish.
    $this->getSession()->getPage()->pressButton('Save');

    // Assert page content.
    $this->assertSession()->pageTextContains('This is some text.');
    $this->assertSession()->pageTextContains('Here is some other text.');
    $this->assertSession()
      ->pageTextContains('Here is text for third carousel item.');

    $carousel_css_xpath = '//div[@id="bootstrap-carousel" and contains(@class, "carousel")]/ol[contains(@class, "carousel-indicators")]/li[@data-target="#bootstrap-carousel"]';
    $this->assertSession()->elementExists('xpath', $carousel_css_xpath);

    $carousel_css_xpath = '//div[@id="bootstrap-carousel" and contains(@class, "carousel")]/div[contains(@class, "carousel-inner")]/div[contains(@class, "carousel-item")]/div[contains(@class, "carousel-caption")]';
    $this->assertSession()->elementExists('xpath', $carousel_css_xpath);

    $carousel_css_xpath = '//div[@id="bootstrap-carousel" and contains(@class, "carousel")]/a[contains(@class, "carousel-control-prev")]';
    $this->assertSession()->elementExists('xpath', $carousel_css_xpath);

    $carousel_css_xpath = '//div[@id="bootstrap-carousel" and contains(@class, "carousel")]/a[contains(@class, "carousel-control-next")]';
    $this->assertSession()->elementExists('xpath', $carousel_css_xpath);

    $carousel_css_xpath = '//div[@id="bootstrap-carousel--2" and contains(@class, "carousel")]';
    $this->assertSession()->elementExists('xpath', $carousel_css_xpath);

    // Test for summary.
    $node = $this->getNodeByTitle('Paragraphed test');
    $this->drupalGet('node/' . $node->id() . '/edit');

    $collapse_button_xpath = '//ul[@data-drupal-selector="edit-field-paragraphs-0-top-links-operations"]/li[contains(@class, "collapse-button")]/input';
    $this->getSession()
      ->getPage()
      ->find('xpath', $collapse_button_xpath)
      ->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()
      ->pageTextContains('2 children, This is some text., Here is some other text., Carousel controls: enabled, Carousel indicator: enabled');

    // Delete field from Carousel paragraph type.
    $this->drupalGet('/admin/structure/paragraphs_type/pcb_carousel/fields/paragraph.pcb_carousel.field_pcb_carousel_container/delete');

    $this->getSession()->getPage()->pressButton('Delete');

    $this->drupalGet('admin/structure/paragraphs_type/pcb_carousel');

    $this->assertSession()
      ->pageTextContains('There are no entity reference revisions fields available. Please add at least one in the Manage fields page.');

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
  }

}
