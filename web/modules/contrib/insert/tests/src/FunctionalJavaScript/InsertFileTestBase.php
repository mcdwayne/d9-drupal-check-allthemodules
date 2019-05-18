<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

abstract class InsertFileTestBase extends WebDriverTestBase {

  use FileFieldCreationTrait {
    createFileField as drupalCreateFileField;
  }
  use StringTranslationTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }
  use TextFieldCreationTrait;

  /**
   * @var array
   */
  public static $modules = ['node', 'file', 'insert', 'field_ui'];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * @var string
   */
  protected $contentTypeName;

  protected function setUp() {
    parent::setUp();

    $this->contentTypeName = 'article';
    $this->createContentType([
      'type' => $this->contentTypeName,
      'name' => 'Article',
    ]);
    $this->adminUser = $this->createUser([], null, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * @return \Behat\Mink\Element\DocumentElement
   */
  protected function gotoInsertConfig() {
    $this->drupalGet('admin/config/content/insert');
    return $this->getSession()->getPage();
  }

  /**
   * @param \Behat\Mink\Element\DocumentElement $page
   */
  protected function saveInsertConfig($page) {
    $page->findButton('edit-submit')->click();
    $this->assertSession()->waitForElement('css', 'role[contentinfo]');
  }

  /**
   * @param string $name
   * @param array (optional) $field_settings
   */
  protected function createFileField($name, array $field_settings = []) {
    $this->drupalCreateFileField(
      $name,
      'node',
      $this->contentTypeName,
      [],
      $field_settings
    );
  }

  /**
   * @param string $field_name
   * @param array $settings
   */
  protected function updateInsertSettings($field_name, array $settings) {
    $manage_display = 'admin/structure/types/manage/' . $this->contentTypeName . '/form-display';
    $this->drupalGet($manage_display);

    $this->drupalPostForm(null, [], $field_name . "_settings_edit");

    $this->getSession()->getPage()->find('css', 'summary')->click();
    $this->assertSession()->waitForField(
      'fields[' . $field_name . '][settings_edit_form][third_party_settings][insert][default]'
    );

    $this->drupalPostForm(
      null,
      $this->settingsToParams($field_name, $settings),
      $this->t('Update')
    );
    $this->drupalPostForm(null, [], $this->t('Save'));
  }

  /**
   * @param string $field_name
   * @param array $settings
   * @return array
   */
  protected function settingsToParams($field_name, array $settings) {
    $params = [];
    $values = $this->flatten($settings);

    foreach ($values as $key => $value) {
      $params['fields[' . $field_name . "][settings_edit_form]"
      . "[third_party_settings][insert]$key"] = $value;
    }

    return $params;
  }

  /**
   * @param array $array
   * @param string (optional) $prefix
   * @return array
   */
  protected function flatten(array $array, $prefix = '') {
    $result = [];
    foreach($array as $key => $value) {
      if (is_array($value)) {
        $result = $result + $this->flatten($value, $prefix . "[$key]");
      }
      else {
        $result[$prefix . "[$key]"] = $value;
      }
    }
    return $result;
  }

}
