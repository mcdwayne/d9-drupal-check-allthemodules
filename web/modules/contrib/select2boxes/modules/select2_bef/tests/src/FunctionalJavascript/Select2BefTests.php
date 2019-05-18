<?php

namespace Drupal\Tests\select2_bef\FunctionalJavascript;

use Drupal\Tests\select2boxes\FunctionalJavascript\Select2BoxesTestsBase;

/**
 * Class Select2BefTests.
 *
 * @package Drupal\Tests\select2_bef\FunctionalJavascript
 *
 * @group Select2Bef
 */
class Select2BefTests extends Select2BoxesTestsBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'select2boxes',
    'select2_bef',
    'better_exposed_filters',
    'node',
    'field',
    'views',
    'views_ui',
  ];

  /**
   * Test if the BEF plugin is overridden successfully.
   */
  public function testBetterExposedFormPluginOverride() {
    // Check if the plugin manager service exists.
    $this->assertTrue(\Drupal::hasService('plugin.manager.views.exposed_form'));
    /** @var \Drupal\Core\Field\WidgetPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.views.exposed_form');
    // Check each plugin if it has definition under a plugin manager service.
    $this->assertTrue($plugin_manager->hasDefinition('bef'));
    $this->assertEquals(
      'Drupal\select2_bef\Plugin\views\exposed_form\BetterExposedFilters',
      $plugin_manager->getDefinition('bef')['class']
    );
  }

  /**
   * Test entity reference exposed filters.
   */
  public function testEntityReferenceFieldFilters() {
    // Disable JS errors to prevent test failures
    // due to the View's module JS console errors.
    $this->minkSession->getDriver()->getBrowser()->jsErrors(FALSE);
    // Generate fake contents for testing.
    $this->generateDummyTerms('tags', 10);
    $this->generateDummyArticles(10);

    // Go to the view's creation page.
    $this->drupalGet('admin/structure/views/add');
    $this->assertEquals(200, $this->minkSession->getStatusCode());

    // Fill all required fields and save the new view.
    $this->minkSession->getPage()->fillField('label', 'Test');
    $this->minkSession->wait(1000);
    $this->minkSession->getPage()->fillField('id', 'test');
    $this->minkSession->wait(1000);
    $this->minkSession->getPage()->selectFieldOption('show[type]', 'article');
    $this->minkSession->wait(5000);
    $this->minkSession->getPage()->checkField('page[create]');
    $this->minkSession->wait(1000);
    $this->minkSession->getPage()->fillField('page[title]', 'Test');
    $this->minkSession->getPage()->fillField('page[path]', 'test');
    $this->minkSession->wait(1000);
    $this->assertSession()->buttonExists('Save and edit')->click();
    $this->minkSession->wait(20000);
    $this->assertEquals(200, $this->minkSession->getStatusCode());
    $this->assertSession()->addressEquals('admin/structure/views/view/test');
    $this->assertTextHelper('The view Test has been saved', FALSE);

    // Add filter by field "Tags" using the View's UI.
    $this->click('a[id="views-add-filter"]');
    $this->minkSession->wait(2000);
    $this->minkSession->getPage()->fillField('override[controls][options_search]', 'tags');
    $this->minkSession->getPage()->checkField('name[node__field_tags.field_tags_target_id]');
    $this->assertTextHelper('Selected: Tags (field_tags)', FALSE);
    $this->click('button.button--primary');
    $this->minkSession->wait(2000);

    // Set dropdown as a filter widget.
    $this->minkSession->getPage()->fillField('options[type]', 'select');
    $this->click('button.button--primary');
    $this->minkSession->wait(2000);
    $this->assertTextHelper('Configure filter criterion: Content: Tags (field_tags)', FALSE);

    // Make filter exposed.
    $this->minkSession->getPage()->checkField('options[expose_button][checkbox][checkbox]');
    $this->minkSession->wait(2000);
    $this->click('button.button--primary');
    $this->minkSession->wait(5000);
    // Save the view.
    $this->saveForm();
    $this->minkSession->wait(5000);
    $this->assertTextHelper('Content: Tags (exposed)', FALSE);

    $this->click('summary[role="button"]');
    $this->minkSession->wait(2000);
    $this->click('a[id="views-page-1-exposed-form"]');
    $this->minkSession->wait(2000);
    $this->minkSession->getPage()->fillField('exposed_form[type]', 'bef');
    $this->click('button.button--primary');
    $this->minkSession->wait(1000);
    // Check for existing options.
    $dropdown = $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="exposed_form_options[bef][field_tags_target_id][bef_format]"]');
    $this->assertNotNull($dropdown);
    $options = $this->getFieldOptions($dropdown);
    $this->assertContains('select2boxes_autocomplete_multi', $options);
    $this->assertContains('select2boxes_autocomplete_single', $options);
    $this->minkSession
      ->getPage()
      ->selectFieldOption('exposed_form_options[bef][field_tags_target_id][bef_format]', 'select2boxes_autocomplete_single');
    $this->minkSession->wait(1000);
    $bundles_element = $this->minkSession
      ->getPage()
      ->find('xpath', '//input[@name="exposed_form_options[bef][field_tags_target_id][more_options][reference_bundles][tags]"]');
    $this->assertNotNull($bundles_element);
    $this->minkSession
      ->getPage()
      ->checkField('exposed_form_options[bef][field_tags_target_id][more_options][reference_bundles][tags]');
    $this->minkSession->wait(1000);
    $this->click('button.button--primary');
    $this->minkSession->wait(2000);
    $this->saveForm();
    $this->minkSession->wait(5000);
    $this->drupalGet('test');
    $this->assertEquals(200, $this->minkSession->getStatusCode());
    $select = $this->getFieldById('edit-field-tags-target-id');
    $this->assertNotNull($select);
    // Check if all required html attributes are exist
    // for the entity reference field.
    $this->assertNotNull($select->getAttribute('data-jquery-once-autocomplete'));
    $this->assertNotNull($select->getAttribute('data-select2-autocomplete-list-widget'));
    $this->assertTrue($select->hasClass('select2-widget'));

    // Update widget to use multiple Select2 widget.
    $this->drupalGet('admin/structure/views/view/test');
    $this->assertEquals(200, $this->minkSession->getStatusCode());

    $this->clickLink('Content: Tags (exposed)');
    $this->minkSession->wait(2000);
    $this->minkSession->getPage()->checkField('options[expose][multiple]');
    $this->click('button.button--primary');
    $this->minkSession->wait(2000);

    $this->drupalGet('admin/structure/views/nojs/display/test/page_1/exposed_form_options');
    $this->assertEquals(200, $this->minkSession->getStatusCode());

    $edit = [
      'exposed_form_options[bef][field_tags_target_id][bef_format]' => 'select2boxes_autocomplete_multi',
      'exposed_form_options[bef][field_tags_target_id][more_options][reference_bundles][tags]' => '1',
      'exposed_form_options[bef][field_tags_target_id][more_options][enable_preload]' => '1',
      'exposed_form_options[bef][field_tags_target_id][more_options][preload_count]' => '5',
    ];
    $this->submitForm($edit, t('Apply'));
    $this->minkSession->wait(5000);
    $this->saveForm();
    $this->minkSession->wait(5000);
    $this->drupalGet('test');
    $this->assertEquals(200, $this->minkSession->getStatusCode());
    // Trigger the opening dropdown via click on the search input field.
    $this->click('input[class="select2-search__field"]');
    $this->minkSession->wait(1000);
    $select = $this->getFieldById('edit-field-tags-target-id');
    $this->assertNotNull($select);
    // Check if all required html attributes are exist
    // for the entity reference field.
    $this->assertNotNull($select->getAttribute('data-jquery-once-autocomplete'));
    $this->assertNotNull($select->getAttribute('data-select2-multiple'));
    $this->assertNotNull($select->getAttribute('data-autocomplete-path'));
    $this->assertNotNull($select->getAttribute('data-field-name'));
    $this->assertTrue($select->hasClass('select2-widget'));
    $this->assertTrue($select->hasClass('select2-boxes-widget'));
    // Check if the number of results is equals to the 5 rows
    // (as was specified in the widget settings).
    $this->assertEquals(5, count($this->getFieldById('select2-edit-field-tags-target-id-results')->findAll('xpath', '//li')));
  }

  /**
   * Test list exposed filters.
   */
  public function testListExposedFilters() {
    // Disable JS errors to prevent test failures
    // due to the View's module JS console errors.
    $this->minkSession->getDriver()->getBrowser()->jsErrors(FALSE);
    // Generate fake contents for testing.
    $this->generateDummyTerms('tags', 10);
    $this->generateDummyArticles(10);
    // Go to the view's creation page.
    $this->drupalGet('admin/structure/views/add');
    $this->assertEquals(200, $this->minkSession->getStatusCode());

    // Fill all required fields and save the new view.
    $this->minkSession->getPage()->fillField('label', 'Test');
    $this->minkSession->wait(1000);
    $this->minkSession->getPage()->fillField('id', 'test');
    $this->minkSession->wait(1000);
    $this->minkSession->getPage()->selectFieldOption('show[type]', 'article');
    $this->minkSession->wait(5000);
    $this->minkSession->getPage()->checkField('page[create]');
    $this->minkSession->wait(1000);
    $this->minkSession->getPage()->fillField('page[title]', 'Test');
    $this->minkSession->getPage()->fillField('page[path]', 'test');
    $this->minkSession->wait(1000);
    $this->assertSession()->buttonExists('Save and edit')->click();
    $this->minkSession->wait(20000);
    $this->assertEquals(200, $this->minkSession->getStatusCode());
    $this->assertSession()->addressEquals('admin/structure/views/view/test');
    $this->assertTextHelper('The view Test has been saved', FALSE);

    // Add filter by field "Tags" using the View's UI.
    $this->click('a[id="views-add-filter"]');
    $this->minkSession->wait(2000);
    $this->minkSession->getPage()->fillField('override[controls][options_search]', 'Test');
    $this->minkSession->getPage()->checkField('name[node__field_test_list.field_test_list_value]');
    $this->click('button.button--primary');
    $this->minkSession->wait(2000);

    // Make filter exposed.
    $this->minkSession->getPage()->checkField('options[expose_button][checkbox][checkbox]');
    $this->minkSession->wait(2000);
    $this->click('button.button--primary');
    $this->minkSession->wait(5000);

    $this->drupalGet('admin/structure/views/nojs/display/test/page_1/exposed_form');
    $this->assertEquals(200, $this->minkSession->getStatusCode());
    $edit = [
      'exposed_form[type]' => 'bef',
    ];
    $this->submitForm($edit, t('Apply'));
    $this->minkSession->wait(5000);
    $this->drupalGet('admin/structure/views/nojs/display/test/page_1/exposed_form_options');
    $this->assertEquals(200, $this->minkSession->getStatusCode());
    $edit = [
      'exposed_form_options[bef][field_test_list_value][bef_format]' => 'select2boxes_autocomplete_list',
    ];
    $this->submitForm($edit, t('Apply'));
    $this->minkSession->wait(5000);
    $this->saveForm();
    $this->minkSession->wait(5000);

    $this->drupalGet('test');
    $this->assertEquals(200, $this->minkSession->getStatusCode());

    $select = $this->getFieldById('edit-field-test-list-value');
    $this->assertNotNull($select);
    // Check if all required html attributes are exist
    // for the entity reference field.
    $this->assertNotNull($select->getAttribute('data-jquery-once-autocomplete'));
    $this->assertNotNull($select->getAttribute('data-select2-autocomplete-list-widget'));
    $this->assertTrue($select->hasClass('select2-widget'));
  }

}
