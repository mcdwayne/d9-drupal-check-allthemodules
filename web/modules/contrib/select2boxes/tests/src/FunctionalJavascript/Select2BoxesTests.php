<?php

namespace Drupal\Tests\select2boxes\FunctionalJavascript;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class Select2BoxesTests.
 *
 * @package Drupal\Tests\select2boxes\FunctionalJavascript
 * @group Select2Boxes
 */
class Select2BoxesTests extends Select2BoxesTestsBase {

  /**
   * Tests whether the widgets plugins are exist and accessible.
   */
  public function testWidgetsPlugins() {
    // Check if the plugin manager service exists.
    $this->assertTrue(\Drupal::hasService('plugin.manager.field.widget'));
    /** @var \Drupal\Core\Field\WidgetPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.field.widget');
    // Check each plugin if it has definition under a plugin manager service.
    foreach (static::$pluginIds as $widget) {
      $this->assertTrue($plugin_manager->hasDefinition($widget));
    }
  }

  /**
   * Check whether all widgets are exist in the field widgets settings.
   */
  public function testWidgetsAdminExistence() {
    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Check for entity reference field widgets.
    $select = $this->getFieldById('edit-fields-field-tags-type');
    $options = $this->getFieldOptions($select);
    $this->assertNotEmpty($options);
    $this->assertContains(static::$pluginIds[1], $options);
    $this->assertContains(static::$pluginIds[2], $options);

    // Check if the newly created field is exist in the list.
    $select = $this->getFieldById('edit-fields-field-test-list-type');
    $this->assertNotNull($select);
    // Check for list field widgets.
    $options = $this->getFieldOptions($select);
    $this->assertNotEmpty($options);
    $this->assertContains(static::$pluginIds[0], $options);

    // Set the Select2Boxes widgets for both fields and submit the form.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_test_list][type]"]')
      ->setValue(static::$pluginIds[0]);
    $this->minkSession->wait(2000);
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_tags][type]"]')
      ->setValue(static::$pluginIds[1]);
    $this->minkSession->wait(2000);
    $this->saveForm();
    // Check if the submission is finishing correctly.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test single widget for entity reference fields.
   */
  public function testSingleWidget() {
    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);
    // Set single widget for the Tags field.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_tags][type]"]')
      ->setValue(static::$pluginIds[1]);
    $this->minkSession->wait(2000);
    $this->saveForm();
    $this->assertSession()->statusCodeEquals(200);
    // Now let's check the widgets style on a node creation page.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);
    $select = $this->getFieldById('edit-field-tags');
    $this->assertNotNull($select);
    // Check if all required html attributes are exist
    // for the entity reference field.
    $this->assertNotNull($select->getAttribute('data-jquery-once-autocomplete'));
    $this->assertNotNull($select->getAttribute('data-select2-autocomplete-list-widget'));
    $this->assertTrue($select->hasClass('select2-widget'));
  }

  /**
   * Test multiple widget for entity reference fields.
   */
  public function testMultipleWidget() {
    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);
    // Set multiple widget for the Tags field.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_tags][type]"]')
      ->setValue(static::$pluginIds[2]);
    $this->minkSession->wait(2000);
    $this->saveForm();
    $this->assertSession()->statusCodeEquals(200);
    // Now let's check the node creation page.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);
    $select = $this->getFieldById('edit-field-tags');
    $this->assertNotNull($select);
    // Check if all required html attributes are exist
    // for the entity reference field.
    $this->assertNotNull($select->getAttribute('data-jquery-once-autocomplete'));
    $this->assertNotNull($select->getAttribute('data-select2-multiple'));
    $this->assertNotNull($select->getAttribute('data-autocomplete-path'));
    $this->assertNotNull($select->getAttribute('data-field-name'));
    $this->assertTrue($select->hasClass('select2-widget'));
    $this->assertTrue($select->hasClass('select2-boxes-widget'));

    // Generate dummy terms.
    $terms = $this->generateDummyTerms('tags', 10);
    // Generate one node for test.
    $nodes = $this->generateDummyArticles(1);
    /** @var \Drupal\node\Entity\Node $node */
    $node = reset($nodes);
    $node->set('field_tags', $terms[mt_rand(0, 9)]->id())
      ->save();
    // Go to the node's edit page.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    // Get the select element.
    $select = $this->getFieldById('edit-field-tags');
    // Check that the select element exists.
    $this->assertNotNull($select);
    // Check that only the selected tag item
    // has been rendered as a dropdown option.
    $this->assertEquals(1, count($this->getFieldOptions($select)));
  }

  /**
   * Test widget for a list type of fields.
   */
  public function testListWidget() {
    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);
    // Set list widget for the Test list field.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_test_list][type]"]')
      ->setValue(static::$pluginIds[0]);
    $this->minkSession->wait(2000);
    $this->saveForm();
    $this->assertSession()->statusCodeEquals(200);
    // Now let's check the node creation page.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);
    $select = $this->getFieldById('edit-field-test-list');
    $this->assertNotNull($select);
    // Check if all required html attributes are exist
    // for the list field.
    $this->assertNotNull($select->getAttribute('data-jquery-once-autocomplete'));
    $this->assertNotNull($select->getAttribute('data-select2-autocomplete-list-widget'));
    $this->assertTrue($select->hasClass('select2-widget'));
  }

  /**
   * Test preloading functionality for the multiple entity reference widgets.
   */
  public function testPreloading() {
    // Firstly generate a fake content.
    $this->generateDummyTerms('tags', 10);
    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);
    // Set multiple widget for the Tags field.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_tags][type]"]')
      ->setValue(static::$pluginIds[2]);
    $this->minkSession->wait(2000);
    // Check for the summary text about preloading option.
    $this->assertTextHelper('Preloading disabled', FALSE);
    // Open the settings form for the Tags field.
    $this->click('input[name="field_tags_settings_edit"]');
    $this->minkSession->wait(2000);
    // Enable preloading via checking the checkbox field.
    $this->minkSession
      ->getPage()
      ->checkField('fields[field_tags][settings_edit_form][third_party_settings][select2boxes][enable_preload]');
    $this->minkSession->wait(500);
    // Set 5 rows to be preloaded.
    $this->minkSession
      ->getPage()
      ->fillField('fields[field_tags][settings_edit_form][third_party_settings][select2boxes][preload_count]', '5');
    // Submit the settings form.
    $this->click('input[name="field_tags_plugin_settings_update"]');
    $this->minkSession->wait(2000);
    // Check for summary text updates,
    // according to the specified number of preload entries.
    $this->assertTextHelper('Number of preloaded entries: 5', FALSE);
    // Submit the entity form display settings.
    $this->saveForm();
    $this->minkSession->wait(2000);
    $this->assertSession()->statusCodeEquals(200);
    // Go to the node's creation form.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);

    // Trigger the opening dropdown via click on the search input field.
    $this->click('input[class="select2-search__field"]');
    $this->minkSession->wait(1000);
    // Find the list of results element on the page.
    $select = $this->getFieldById('select2-edit-field-tags-results');
    $this->assertNotNull($select);
    // Check if the number of results is equals to the 5 rows
    // (as was specified in the widget settings).
    $this->assertEquals(5, count($select->findAll('xpath', '//li')));
  }

  /**
   * Test limited search option functionality.
   */
  public function testLimitedSearch() {
    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Set list widget for the Test list field.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_test_list][type]"]')
      ->setValue(static::$pluginIds[0]);
    $this->minkSession->wait(2000);
    $this->saveForm();

    // Enable limited search option.
    $this->drupalGet('admin/config/user-interface/select2boxes');
    $this->assertSession()->statusCodeEquals(200);
    // Specify minimum length search to a BIGGER value
    // than we have in our "Allowed values" settings for the field.
    $edit = [
      'limited_search'        => '1',
      'minimum_search_length' => '4',
    ];
    $this->submitForm($edit, t('Save configuration'));
    $this->minkSession->wait(2000);
    $this->assertSession()->statusCodeEquals(200);
    // Go to the node's creation form.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);

    // Trigger opening dropdown.
    $this->minkSession
      ->getDriver()
      ->executeScript("jQuery('select[name=\"field_test_list\"]').select2('open');");
    // Check for NON-existing search input field.
    $search_input = $this->minkSession
      ->getPage()
      ->find('xpath', '//span[contains(@class, \'select2-search--dropdown\')]');
    $this->assertNotNull($search_input);
    $this->assertTrue($search_input->hasClass('select2-search--hide'));

    $this->drupalGet('admin/config/user-interface/select2boxes');
    $this->assertSession()->statusCodeEquals(200);
    // Specify minimum length search to the SAME value
    // than we have in our "Allowed values" settings for the field.
    $edit = [
      'limited_search'        => '1',
      'minimum_search_length' => '3',
    ];
    $this->submitForm($edit, t('Save configuration'));
    $this->minkSession->wait(2000);
    $this->assertSession()->statusCodeEquals(200);

    // Go to the node's creation form.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);

    // Trigger opening dropdown.
    $this->minkSession
      ->getDriver()
      ->executeScript("jQuery('select[name=\"field_test_list\"]').select2('open');");
    // Check for EXISTING search input field.
    $search_input = $this->minkSession
      ->getPage()
      ->find('xpath', '//span[contains(@class, \'select2-search--dropdown\')]');
    $this->assertNotNull($search_input);
    $this->assertFalse($search_input->hasClass('select2-search--hide'));
  }

  /**
   * Test entity auto-creation with limited search enabled.
   */
  public function testEntityAutoCreationWithLimitedSearch() {
    $this->drupalPostForm(
      'admin/config/user-interface/select2boxes',
      [
        'limited_search'        => '1',
        'minimum_search_length' => '4',
      ],
      t('Save configuration')
    );

    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Set single widget for the Tags entity reference field.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_tags][type]"]')
      ->setValue(static::$pluginIds[1]);
    $this->minkSession->wait(2000);
    $this->saveForm();

    // Go to the node's creation form.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);

    // Trigger opening dropdown.
    $this->minkSession
      ->getDriver()
      ->executeScript("jQuery('select[name=\"field_tags\"]').select2('open');");
    // Check for EXISTING search input field.
    $search_input = $this->minkSession
      ->getPage()
      ->find('xpath', '//span[contains(@class, \'select2-search--dropdown\')]');
    $this->assertNotNull($search_input);
    $this->assertFalse($search_input->hasClass('select2-search--hide'));
  }

  /**
   * Test unlimited preloading behavior.
   */
  public function testUnlimitedPreloading() {
    // Firstly generate a fake content.
    $this->generateDummyTerms('tags', 10);
    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);
    // Set multiple widget for the Tags field.
    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_tags][type]"]')
      ->setValue(static::$pluginIds[2]);
    $this->minkSession->wait(2000);
    // Check for the summary text about preloading option.
    $this->assertTextHelper('Preloading disabled', FALSE);
    // Open the settings form for the Tags field.
    $this->click('input[name="field_tags_settings_edit"]');
    $this->minkSession->wait(2000);
    // Enable preloading via checking the checkbox field.
    $this->minkSession
      ->getPage()
      ->checkField('fields[field_tags][settings_edit_form][third_party_settings][select2boxes][enable_preload]');
    $this->minkSession->wait(500);
    // Do not specify the preload count value!
    // Submit the settings form.
    $this->click('input[name="field_tags_plugin_settings_update"]');
    $this->minkSession->wait(2000);
    // Check for summary text updates,
    // according to the NON-specified number of preload entries.
    $this->assertTextHelper('Number of preloaded entries: all', FALSE);
    // Submit the entity form display settings.
    $this->saveForm();
    $this->minkSession->wait(2000);
    $this->assertSession()->statusCodeEquals(200);
    // Go to the node's creation form.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);

    // Trigger the opening dropdown via click on the search input field.
    $this->click('input[class="select2-search__field"]');
    $this->minkSession->wait(1000);
    // Find the list of results element on the page.
    $select = $this->getFieldById('select2-edit-field-tags-results');
    $this->assertNotNull($select);
    // Check if the number of results is equals
    // to the 10 rows (as was generated).
    $this->assertEquals(10, count($select->findAll('xpath', '//li')));
  }

  /**
   * Test globally enabled select2 widgets.
   */
  public function testGlobalSelect2Widgets() {
    // Enable select2 widgets globally.
    $this->drupalPostForm(
      'admin/config/user-interface/select2boxes',
      ['select2_global' => TRUE],
      t('Save configuration')
    );

    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Get all <select> elements on a page.
    $selects = $this->minkSession
      ->getPage()
      ->findAll('xpath', '//select');

    // Check if all of them are having appropriate class and attribute values.
    foreach ($selects as $select) {
      /* @var \Behat\Mink\Element\NodeElement $select */
      $this->assertTrue($select->hasClass('select2-widget'));
      $this->assertTrue($select->hasAttribute('data-jquery-once-autocomplete'));
      $this->assertTrue($select->hasAttribute('data-select2-autocomplete-list-widget'));
    }

    // Additionally check for JS errors
    // on non-views and non-admin page with select element.
    $this->minkSession->getDriver()->getBrowser()->jsErrors(TRUE);
    $this->drupalGet('select2boxes_test_form');
    $this->assertSession()->statusCodeEquals(200);
    $this->minkSession->getDriver()->getBrowser()->jsErrors(FALSE);

    // Disable select2 widgets globally.
    $this->drupalPostForm(
      'admin/config/user-interface/select2boxes',
      ['select2_global' => FALSE],
      t('Save configuration')
    );

    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Get all <select> elements on a page.
    $selects = $this->minkSession
      ->getPage()
      ->findAll('xpath', '//select');

    // Check if all of them are NOT having
    // appropriate class and attribute values.
    foreach ($selects as $select) {
      /* @var \Behat\Mink\Element\NodeElement $select */
      $this->assertFalse($select->hasClass('select2-widget'));
      $this->assertFalse($select->hasAttribute('data-jquery-once-autocomplete'));
      $this->assertFalse($select->hasAttribute('data-select2-autocomplete-list-widget'));
    }
  }

  /**
   * Test globally enabled select2 widgets with limited search.
   */
  public function testGlobalSelect2WidgetsWithLimitedSearch() {
    $this->drupalPostForm(
      'admin/config/user-interface/select2boxes',
      [
        'select2_global'        => TRUE,
        'limited_search'        => TRUE,
        'minimum_search_length' => '3',
      ],
      t('Save configuration')
    );

    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Trigger opening dropdown.
    $this->minkSession
      ->getDriver()
      ->executeScript("jQuery('select[name=\"fields[status][type]\"]').select2('open');");
    // Check for NON-existing search input field.
    $search_input = $this->minkSession
      ->getPage()
      ->find('xpath', '//span[contains(@class, \'select2-search--dropdown\')]');
    $this->assertNotNull($search_input);
    $this->assertTrue($search_input->hasClass('select2-search--hide'));
  }

  /**
   * Test globally enabled select2 widgets with disabling it for admin pages.
   */
  public function testGlobalSelect2WidgetsWithAdminPagesDisabled() {
    // Enable select2 widgets globally.
    $this->drupalPostForm(
      'admin/config/user-interface/select2boxes',
      ['select2_global' => TRUE],
      t('Save')
    );

    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Get all <select> elements on a page.
    $selects = $this->minkSession
      ->getPage()
      ->findAll('xpath', '//select');

    // Check if all of them are having appropriate class and attribute values.
    foreach ($selects as $select) {
      /* @var \Behat\Mink\Element\NodeElement $select */
      $this->assertTrue($select->hasClass('select2-widget'));
      $this->assertTrue($select->hasAttribute('data-jquery-once-autocomplete'));
      $this->assertTrue($select->hasAttribute('data-select2-autocomplete-list-widget'));
    }

    // Disable select2 widgets for admin pages.
    $this->drupalPostForm(
      'admin/config/user-interface/select2boxes',
      ['disable_for_admin_pages' => TRUE],
      t('Save')
    );

    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    // Get all <select> elements on a page.
    $selects = $this->minkSession
      ->getPage()
      ->findAll('xpath', '//select');

    // Check if all of them are NOT having
    // appropriate class and attribute values.
    foreach ($selects as $select) {
      /* @var \Behat\Mink\Element\NodeElement $select */
      $this->assertFalse($select->hasClass('select2-widget'));
      $this->assertFalse($select->hasAttribute('data-jquery-once-autocomplete'));
      $this->assertFalse($select->hasAttribute('data-select2-autocomplete-list-widget'));
    }
  }

  /**
   * Test entity auto-creation with multiple vocabularies.
   */
  public function testEntityAutoCreationWithMultipleVocabularies() {
    // Create new taxonomy vocabulary for test.
    $voc = 'test_voc';
    Vocabulary::create(['vid' => $voc, 'name' => $voc])->save();
    // Check that the new taxonomy vocabulary has been created.
    $this->assertNotNull(Vocabulary::load($voc));

    // Create new entity reference field field.
    $this->fieldUiAddNewField(
      'admin/structure/types/manage/article',
      'test_term',
      'Test term',
      'entity_reference',
      [
        'cardinality'           => -1,
        'settings[target_type]' => 'taxonomy_term',
      ]
    );
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_test_term');
    $this->assertSession()->statusCodeEquals(200);

    $this->minkSession->getPage()->checkField('settings[handler_settings][auto_create]');
    $this->minkSession->wait(2000);
    $this->minkSession->getPage()->checkField("settings[handler_settings][target_bundles][$voc]");
    $this->minkSession->wait(2000);
    $this->minkSession->getPage()->checkField('settings[handler_settings][target_bundles][tags]');
    $this->minkSession->wait(2000);
    $this->minkSession->getPage()->fillField('settings[handler_settings][auto_create_bundle]', $voc);
    $this->click('input[value="Save settings"]');

    // Go the the "Manage Form Display" form.
    $this->drupalGet(static::$manageFormDisplayPath);
    $this->assertSession()->statusCodeEquals(200);

    $this->minkSession
      ->getPage()
      ->find('xpath', '//select[@name="fields[field_test_term][type]"]')
      ->setValue(static::$pluginIds[2]);
    $this->minkSession->wait(2000);
    $this->saveForm();
    // Go to the node's creation form.
    $this->drupalGet(static::$nodeAddPath);
    $this->assertSession()->statusCodeEquals(200);

    $this->minkSession->getPage()->fillField('title[0][value]', 'TESTTITLE');
    $this->minkSession->wait(200);
    $this->minkSession
      ->getDriver()
      ->executeScript("jQuery('#edit-field-test-term').next().find('input.select2-search__field').val('TESTTERM').trigger('keyup');");
    $this->minkSession->wait(200);
    $this->minkSession->getPage()->selectFieldOption('field_test_term[]', 'TESTTERM');
    $this->minkSession->wait(200);
    $this->saveForm();
    $terms = Term::loadMultiple();
    $this->assertNotEmpty($terms);
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = reset($terms);
    $this->assertEquals($voc, $term->getVocabularyId());
  }

}
