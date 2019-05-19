<?php

namespace Drupal\Tests\twig_field\FunctionalJavascript;

/**
 * Tests the Twig field module.
 *
 * @group twig_field
 */
class TwigFieldTest extends TestBase {

  /**
   * Test callback.
   */
  public function testTwigField() {

    // -- Login as admin.
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $permissions = [
      'access twig fields',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'create test content',
      'edit own test content',
    ];
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    // -- Create a Twig field.
    $this->drupalGet('admin/structure/types/manage/test/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'twig');
    $page->fillField('label', 'Template');
    $assert_session->waitForElementVisible('css', '#edit-label-machine-name-suffix');
    $page->pressButton('Save and continue');
    $page->pressButton('Save field settings');
    $assert_session->elementExists('xpath', '//select[@name = "settings[display_mode]"]/option[@selected and text() = "- None -"]');
    $page->pressButton('Save settings');

    // -- Create a node.
    $this->drupalGet('node/add');

    $page->fillField('title[0][value]', 'Example');

    // Default widget settings.
    $widget_settings = [
      'rows' => 5,
      'placeholder' => '',
      'mode' => 'html_twig',
      'toolbar' => TRUE,
      'lineNumbers' => FALSE,
      'foldGutter' => FALSE,
      'autoCloseTags' => TRUE,
      'styleActiveLine' => FALSE,
    ];
    $this->assertWidgetForm($widget_settings);

    $variable_options = [
      'Global' => [
        'theme',
        'theme_directory',
        'base_path',
        'front_page',
        'is_front',
        'language',
        'is_admin',
        'logged_in',
      ],
      'Other' => [
        'node',
      ],
    ];
    $this->assertVariableOptions($variable_options);
    $assert_session->elementNotExists('xpath', '//optgroup[@label = "Fields"]');

    $page->selectFieldOption('field_template[0][footer][variables]', 'theme');
    $page->pressButton('Insert');
    $this->editorSetSelection([0, 0], [0, 11]);
    $this->editorClickButton('italic');

    $page->pressButton('Save');
    $assert_session->pageTextContains('test Example has been created.');
    $assert_session->elementExists('xpath', '//div[contains(@class, "field--name-field-template")]/div/em[text() = "classy"]');

    // -- Update widget settings.
    $this->drupalGet('admin/structure/types/manage/test/form-display');
    $this->assertWidgetSettingsSummary($widget_settings);
    $this->click('//input[@name = "field_template_settings_edit"]');
    $this->assertWidgetSettingsForm($widget_settings);

    $widget_settings = [
      'rows' => 10,
      'placeholder' => 'Test',
      'mode' => 'text/html',
      'toolbar' => FALSE,
      'lineNumbers' => TRUE,
      'foldGutter' => TRUE,
      'autoCloseTags' => FALSE,
      'styleActiveLine' => TRUE,
    ];
    foreach ($widget_settings as $name => $value) {
      $field_name = "fields[field_template][settings_edit_form][settings][$name]";
      if (is_bool($value)) {
        $value ? $page->checkField($field_name) : $page->uncheckField($field_name);
      }
      else {
        $page->fillField($field_name, $value);
      }
    }

    $page->pressButton('Update');
    $this->assertWidgetSettingsSummary($widget_settings);

    $page->pressButton('Save');
    $this->click('//input[@name = "field_template_settings_edit"]');
    $this->assertWidgetSettingsForm($widget_settings);

    // -- Update field settings.
    $this->drupalGet('admin/structure/types/manage/test/fields/node.test.field_template');
    $page->selectFieldOption('settings[display_mode]', 'node.test.default');
    $page->pressButton('Save settings');

    // -- Update the node.
    $this->drupalGet('node/1/edit');
    $this->assertWidgetForm($widget_settings);
    $this->assertEditorValue('<em>{{ theme }}</em>');

    $variable_options['Fields'] = [
      'body',
      'created',
      'title',
      'uid',
    ];
    $this->assertVariableOptions($variable_options);
    $this->editorSetValue('<b>{{ title }}</b>');

    $page->pressButton('Save');
    $assert_session->elementExists('xpath', '//div[contains(@class, "field--name-field-template")]/div/b/span[text() = "Example"]');

    // -- Check access.
    $permissions = [
      'edit any test content',
    ];
    $unprivileged_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($unprivileged_user);
    $this->drupalGet('node/1/edit');
    $assert_session->pageTextContains('Edit test Example');
    // The field is not available because the user has no 'access twig fields'
    // permission.
    $assert_session->elementNotExists('css', '.field--name-field-template');
  }

}
