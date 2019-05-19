<?php

namespace Drupal\Tests\twig_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Base class for Twig field tests.
 */
abstract class TestBase extends WebDriverTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['twig_field', 'field_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'test']);
  }

  /**
   * Sets CodeMirror document selection.
   */
  protected function editorSetSelection($anchor, $head) {
    $script = 'document.querySelector("#edit-field-template-wrapper .CodeMirror").CodeMirror.setSelection({line: %d, ch: %d}, {line: %d, ch: %d});';
    $script = sprintf($script, $anchor[0], $anchor[1], $head[0], $head[1]);
    $this->getSession()
      ->getDriver()
      ->executeScript($script);
  }

  /**
   * Clicks specified editor toolbar button.
   */
  protected function editorClickButton($button) {
    $this->getSession()
      ->getPage()
      ->find('css', sprintf('#edit-field-template-wrapper .cme-toolbar [data-cme-button="%s"]', $button))
      ->click();
  }

  /**
   * Sets CodeMirror document value.
   */
  protected function editorSetValue($value) {
    $script = 'document.querySelector("#edit-field-template-wrapper .CodeMirror").CodeMirror.setValue("%s");';
    $script = sprintf($script, $value);
    $this->getSession()
      ->getDriver()
      ->executeScript($script);
  }

  /**
   * Clicks button or link located by it's XPath query.
   */
  protected function click($xpath) {
    $this->getSession()->getDriver()->click($xpath);
  }

  /**
   * Assets editor option value.
   */
  protected function assertEditorOption($option, $expected_value) {
    $script = 'document.querySelector("#edit-field-template-wrapper .CodeMirror").CodeMirror.getOption("%s");';
    $script = sprintf($script, $option);
    $value = $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
    self::assertSame($expected_value, $value);
  }

  /**
   * Assets editor value.
   */
  protected function assertEditorValue($expected_value) {
    $script = 'document.querySelector("#edit-field-template-wrapper .CodeMirror").CodeMirror.getValue();';
    $value = $this->getSession()
      ->getDriver()
      ->evaluateScript($script);
    self::assertSame($expected_value, $value);
  }

  /**
   * Assets that toolbar exists.
   */
  protected function assertToolbarExists() {
    $this->assertSession()
      ->elementExists('css', '#edit-field-template-wrapper .cme-toolbar');
  }

  /**
   * Assets that toolbar does not exist.
   */
  protected function assertToolbarNotExists() {
    $this->assertSession()
      ->elementNotExists('css', '#edit-field-template-wrapper .cme-toolbar');
  }

  /**
   * Assets Twig variable options.
   */
  protected function assertVariableOptions(array $options) {
    $assert_session = $this->assertSession();
    $prefix = '//select[@name = "field_template[0][footer][variables]"]';
    foreach ($options as $label => $group_options) {
      foreach ($group_options as $option) {
        $xpath = sprintf('%s/optgroup[@label = "%s"]/option[@value = "%s"]', $prefix, $label, $option);
        $assert_session->elementExists('xpath', $xpath);

      }
    }
  }

  /**
   * Asserts widget form.
   */
  protected function assertWidgetForm(array $widget_settings) {
    $xpath = sprintf(
      '//textarea[@name = "field_template[0][value]" and @rows = %d and @placeholder = "%s"]',
      $widget_settings['rows'],
      $widget_settings['placeholder']
    );
    $this->assertSession()->elementExists('xpath', $xpath);
    $widget_settings['toolbar'] ? $this->assertToolbarExists() : $this->assertToolbarNotExists();
    $this->assertEditorOption('mode', $widget_settings['mode']);
    $this->assertEditorOption('lineNumbers', $widget_settings['lineNumbers']);
    $this->assertEditorOption('foldGutter', $widget_settings['foldGutter']);
    $this->assertEditorOption('autoCloseTags', $widget_settings['autoCloseTags']);
    $this->assertEditorOption('styleActiveLine', $widget_settings['styleActiveLine']);
  }

  /**
   * Asserts widget settings form.
   */
  protected function assertWidgetSettingsForm(array $widget_settings) {
    $assert_session = $this->assertSession();

    $settings_wrapper = $assert_session
      ->waitForElementVisible('xpath', '//div[@data-drupal-selector = "edit-fields-field-template-settings-edit-form"]');
    self::assertNotNull($settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][rows]" and @value = %d]';
    $xpath = sprintf($xpath, $widget_settings['rows']);
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][placeholder]" and @value = "%s"]';
    $xpath = sprintf($xpath, $widget_settings['placeholder']);
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][mode]" and @value = "%s"]';
    $xpath = sprintf($xpath, $widget_settings['mode']);
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][toolbar]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['toolbar'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][lineNumbers]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['lineNumbers'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][foldGutter]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['foldGutter'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][autoCloseTags]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['autoCloseTags'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);

    $xpath = '//input[@name = "fields[field_template][settings_edit_form][settings][styleActiveLine]" and %s]';
    $xpath = sprintf($xpath, $widget_settings['styleActiveLine'] ? '@checked = "checked"' : 'not(@checked)');
    $assert_session->elementExists('xpath', $xpath, $settings_wrapper);
  }

  /**
   * Asserts widget settings summary.
   */
  protected function assertWidgetSettingsSummary(array $widget_settings) {
    $summary = $this->assertSession()->waitForElement('css', '#field-template .field-plugin-summary');
    $this->assertNotNull($summary);

    $expected_summary[] = 'Number of rows: ' . $widget_settings['rows'];
    if ($widget_settings['placeholder']) {
      $expected_summary[] = 'Placeholder: ' . $widget_settings['placeholder'];
    }
    $expected_summary[] = 'Language mode: ' . $widget_settings['mode'];
    $expected_summary[] = 'Load toolbar: ' . ($widget_settings['toolbar'] ? 'Yes' : 'No');
    $expected_summary[] = 'Line numbers: ' . ($widget_settings['lineNumbers'] ? 'Yes' : 'No');
    $expected_summary[] = 'Fold gutter: ' . ($widget_settings['foldGutter'] ? 'Yes' : 'No');
    $expected_summary[] = 'Auto close tags: ' . ($widget_settings['autoCloseTags'] ? 'Yes' : 'No');
    $expected_summary[] = 'Style active line: ' . ($widget_settings['styleActiveLine'] ? 'Yes' : 'No');

    $summary_xpath = '//tr[@id = "field-template"]//div[@class = "field-plugin-summary"]';
    $summary = $this->xpath($summary_xpath)[0]->getHtml();

    self::assertEquals(implode('<br>', $expected_summary), $summary);
  }

}
