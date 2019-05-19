<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Tests\ViewsXMLBackendBase.
 */

namespace Drupal\views_xml_backend\Tests;

use Drupal\views\Views;
use Drupal\views_ui\Tests\UITestBase;
use Drupal\Component\Serialization\Json;

/**
 * Provides supporting functions for testing the Views XML Backend module.
 */

abstract class ViewsXMLBackendBase extends UITestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'views',
    'views_ui',
    'views_xml_backend',
  ];

  /**
   * The administrator account to use for the tests.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $viewsXMLBackendUser;

  /**
   * Views XML Backend field id.
   *
   * @var string
   */
  protected $viewsXMLBackendViewFieldId;

  /**
   * Views XML Backend field name.
   *
   * @var string
   */
  protected $viewsXMLBackendViewFieldName;

  /**
   * Views XML Backend field value.
   *
   * @var string
   */
  protected $viewsXMLBackendViewValue;

  /**
   * Views XML Backend base view title.
   *
   * @var string
   */
  protected $viewsXMLBackendTitle;

  /**
   * Views XML Backend base view id.
   *
   * @var string
   */
  protected $viewsXMLBackendViewId;

  /**
   * Views XML Backend base view admin add path.
   *
   * @var string
   */
  protected $viewsXMLBackendViewAddPath;

  /**
   * Views XML Backend base view admin edit path.
   *
   * @var string
   */
  protected $viewsXMLBackendViewEditPath;

  /**
   * Views XML Backend base view admin query path.
   *
   * @var string
   */
  protected $viewsXMLBackendViewQueryPath;

  /**
   * Views XML Backend base view xml file.
   *
   * @var string
   */
  protected $viewsXMLBackendFile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $permissions = [
      'administer users',
      'administer permissions',
      'administer views',
      'access user profiles',
      'administer permissions',
      'administer blocks',
      'bypass node access',
      'view all revisions',
    ];
    $this->viewsXMLBackendUser = $this->createTestUser($permissions);
    $this->drupalLogin($this->viewsXMLBackendUser);
  }

  /**
   * Creates a valid test User with supplied permissions.
   *
   * @param array $permissions
   *   Permissions user should have.
   *
   * @return \Drupal\Core\Session\AccountInterface|false
   *   A user account interface or FALSE if fails
   */
  private function createTestUser(array $permissions = []) {
    return $this->drupalCreateUser($permissions);
  }

  /**
   * Provides settings used for creating and managing Views XML Backend.
   *
   * @return array
   *   An array of settings.
   */
  private function setUpViewsXMLBackendVariables() {
    return $settings = [
      'field_id' => 'edit-show-wizard-key',
      'field_name' => 'show[wizard_key]',
      'value' => 'standard:views_xml_backend',
      'file' => 'https://updates.drupal.org/release-history/views/7.x',
    ];
  }

  /**
   * Provides variables used generally for creating and managing Views.
   */
  private function setUpViewsVariables() {
    $settings = $this->setUpViewsXMLBackendVariables();
    $this->viewsXMLBackendViewFieldId = $settings['field_id'];
    $this->viewsXMLBackendViewFieldName = $settings['field_name'];
    $this->viewsXMLBackendViewValue = $settings['value'];
    $this->viewsXMLBackendFile = $settings['file'];
    $this->viewsXMLBackendViewId = strtolower($this->randomMachineName(16));
    $this->viewsXMLBackendTitle = $this->randomMachineName(16);
    $this->viewsXMLBackendViewAddPath = '/admin/structure/views/add';
    $this->viewsXMLBackendViewEditPath = "/admin/structure/views/view/{$this->viewsXMLBackendViewId}/edit/default";
    $this->viewsXMLBackendViewQueryPath = "admin/structure/views/nojs/display/{$this->viewsXMLBackendViewId}/default/query";
    //$path_to_test_file = drupal_get_path('module', "views_xml_backend");
  }

  /**
   * Adds and verifies the Views XML Backend option during new Views creation.
   */
  protected function addXMLBackendView() {
    $this->drupalGet('admin/structure/views/add');
    $settings = $this->setUpViewsXMLBackendVariables();
    $msg = "Select option '{$settings['value']}' was found in '{$settings['field_id']}'";
    $this->assertOption($settings['field_id'], $settings['value'], $msg);
  }

  /**
   * Adds and verifies that a new Views XML Backend View can be created.
   */
  protected function addMinimalXMLBackendView() {
    /*
     * NOTE: To save a test view $strictConfigSchema must be set to FALSE.
     * @see https://www.drupal.org/node/2679725
     */

    // Setup consistent test variables to use throughout new test View.
    $this->setUpViewsVariables();

    $default = [
      $this->viewsXMLBackendViewFieldName => $this->viewsXMLBackendViewValue,
    ];
    $this->drupalPostAjaxForm($this->viewsXMLBackendViewAddPath, $default, $this->viewsXMLBackendViewFieldName);

    // Confirm standard:views_xml_backend was selected in show[wizard_key] select
    $new_id = $this->xpath("//*[starts-with(@id, 'edit-show-wizard-key')]/@id");
    $new_wizard_id = (string) $new_id[0]['id'];
    $this->assertOptionSelected($new_wizard_id, $this->viewsXMLBackendViewValue, "The XML select option 'standard:views_xml_backend' was selected on {$new_wizard_id}");

    // Save the new test View.
    $default = [
      'label' => $this->viewsXMLBackendTitle,
      'id' => $this->viewsXMLBackendViewId,
      'description' => $this->randomMachineName(16),
      $this->viewsXMLBackendViewFieldName => $this->viewsXMLBackendViewValue,
    ];
    $this->drupalPostForm($this->viewsXMLBackendViewAddPath, $default, t('Save and edit'));
    // Confirm new view is saved.
    $this->assertText("The view {$this->viewsXMLBackendTitle} has been saved");
  }

  /**
   * Adds and verifies that a new Views XML Backend View can be created and
   * specific basic Views XML Backend settings can be set.
   */
  protected function addStandardXMLBackendView() {
    $this->addMinimalXMLBackendView();

    // Update the Query Settings
    $this->drupalGet($this->viewsXMLBackendViewQueryPath);
    $this->assertField('query[options][xml_file]', "The XML select option 'query[options][xml_file]' was found");
    $this->assertField('query[options][row_xpath]', "The XML select option 'query[options][row_xpath]' was found");
    // Update the Query settings on the new View to use an XML file as source.
    $xml_setting = [
      'query[options][xml_file]' => $this->viewsXMLBackendFile,
      'query[options][row_xpath]' => "/project/releases/release"
    ];
    $this->drupalPostForm($this->viewsXMLBackendViewQueryPath, $xml_setting, t('Apply'));
    $this->drupalPostForm($this->viewsXMLBackendViewEditPath, array(), t('Save'));

    // Check that the Query Settings are saved into the view itself.
    $view = Views::getView($this->viewsXMLBackendViewId);
    $view->initDisplay();
    $view->initQuery();
    $this->assertEqual($this->viewsXMLBackendFile, $view->query->options['xml_file'], 'Query settings were saved');

    // Update and confirm the default XML field on the new View.
    $this->drupalGet("admin/structure/views/view/{$this->viewsXMLBackendViewId}/edit");
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, $edit = array(), t('Update preview'));

    $edit_handler_url = "admin/structure/views/nojs/handler/{$this->viewsXMLBackendViewId}/default/field/text";
    $this->drupalGet($edit_handler_url);
    $fields = [
      'options[xpath_selector]' => 'version_major'
    ];
    $this->drupalPostForm(NULL, $fields, t('Apply'));
    $this->drupalPostForm(NULL, $edit = array(), t('Update preview'));

    $edit_handler_url = "admin/structure/views/nojs/handler/{$this->viewsXMLBackendViewId}/default/field/text";
    $this->drupalGet($edit_handler_url);
    $field_id = $this->xpath("//*[starts-with(@id, 'edit-options-xpath-selector')]/@id");
    $new_field_id = (string) $field_id[0]['id'];
    $this->assertFieldByXPath("//input[@id='{$new_field_id}']", 'version_major', "Value 'version_major' found in field {$new_field_id}");

    // Add and confirm a new XML field on the new View.
    $field_add = "/admin/structure/views/nojs/add-handler/{$this->viewsXMLBackendViewId}/default/field";
    $this->drupalGet($field_add);
    $this->assertField('name[views_xml_backend.text]', "The XML check field 'name[views_xml_backend.text]' was found");
    $fields = [
      'name[views_xml_backend.text]' => 'views_xml_backend.text',
    ];
    $this->drupalPostForm(NULL, $fields, t('Add and configure fields'));

    $this->assertField('options[xpath_selector]', "The XML input 'options[xpath_selector]' was found");
    $fields = [
      'options[xpath_selector]' => 'download_link'
    ];
    $this->drupalPostForm(NULL, $fields, t('Apply'));
    $this->drupalPostForm(NULL, $edit = array(), t('Update preview'));

    $edit_handler_url = "admin/structure/views/nojs/handler/{$this->viewsXMLBackendViewId}/default/field/text_1";
    $this->drupalGet($edit_handler_url);
    $field_id = $this->xpath("//*[starts-with(@id, 'edit-options-xpath-selector')]/@id");
    $new_field_id = (string) $field_id[0]['id'];
    $this->assertFieldByXPath("//input[@id='{$new_field_id}']", 'download_link', "Value 'download_link' found in field {$new_field_id}");
  }

  /**
   * Navigate within the Views Pager.
   */
  protected function navigateViewsPager($pager_path) {
    $content = $this->content;
    $drupal_settings = $this->drupalSettings;
    $ajax_settings = array(
      'wrapper' => 'views-preview-wrapper',
      'method' => 'replaceWith',
    );
    $url = $this->getAbsoluteUrl($pager_path);
    $post = array('js' => 'true') + $this->getAjaxPageStatePostData();
    $result = Json::decode($this->drupalPost($url, 'application/vnd.drupal-ajax', $post));
    if (!empty($result)) {
      $this->drupalProcessAjaxResponse($content, $result, $ajax_settings, $drupal_settings);
    }
  }

}
