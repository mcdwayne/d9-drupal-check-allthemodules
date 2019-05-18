<?php
/**
 * @file
 * Contains \Drupal\collect\Tests\CollectWebTest.
 */

namespace Drupal\collect\Tests;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Collect UI.
 *
 * @group collect
 */
class CollectWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'collect',
    'node',
    'collect_test',
    'field_ui',
    'link',
    'entity_test',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Place tasks, actions and page title blocks.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('system_menu_block:admin');
  }

  /**
   * Tests the configuration UI.
   */
  public function testSettings() {
    // Test access to settings form.
    $this->drupalGet('admin/config/services/collect');
    $this->assertResponse(403);

    $admin = $this->drupalCreateUser([
      'administer collect',
      'administer node fields',
    ]);
    $this->drupalLogin($admin);
    $this->drupalGet('admin/config/services/collect');

    // Assert that reference fields table is not displayed if it is empty.
    $this->assertNoText(t('There are no fields that can be used for reference capturing.'));

    // Default uid field should be checked.
    $this->assertText(t('Standard user references have been selected by default.'));
    $this->assertFieldChecked('edit-entity-capture-node-fields-reference-fields-uid');

    // Create a field on two different node types. Both types should be included
    // in the settings form.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article'])->save();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page'])->save();
    $this->drupalPostForm('admin/structure/types/manage/article/fields/add-field', [
      'new_storage_type' => 'field_ui:entity_reference:user',
      'label' => 'User reference',
      'field_name' => 'user',
    ], t('Save and continue'));
    $this->drupalPostForm('admin/structure/types/manage/page/fields/add-field', [
      'existing_storage_name' => 'field_user',
      'existing_storage_label' => 'User reference',
    ], t('Save and continue'));
    $this->drupalGet('admin/config/services/collect');
    $this->assertText('Article, Page');
    $this->assertLink('Article');
    $this->assertLink('Page');
  }

  /**
   * Tests routing, contents and behavior of Container pages.
   */
  public function testContainerUi() {

    $this->drupalGet('admin/content/collect');
    $this->assertResponse(403);
    $user = $this->drupalCreateUser(array(
      'administer collect',
      'access administration pages',
      'access content overview',
    ));
    $this->drupalLogin($user);

    $this->drupalGet('admin/content');
    $this->clickLink('Collect');
    $this->assertResponse(200);
    $this->assertNoRaw('<strong>Namespace</strong> collect: http://schema.md-systems.ch/collect/0.0.1/');
    $container = Container::create(array(
      'origin_uri' => 'https://drupal.org/project/collect/test',
      'type' => 'text/plain',
      'data' => 'Hello <strong>World!</strong>',
      'date' => (new DrupalDateTime('2014-12-16 09:40'))->getTimestamp(),
    ));
    $container->save();
    $this->drupalGet($this->url);
    $this->assertText('text/plain');
    $this->assertText('drupal.org/project/collect/test');
    $this->assertText('12/16/2014 - 09:40');

    $this->clickLink(t('View'));
    $this->assertText(t('There is no plugin configured to display data.'));
    // Raw data container view should display plain data.
    $this->clickLink(t('Raw data'));
    $this->assertText('Hello &lt;strong&gt;World!&lt;/strong&gt;');

    // Change data type to HTML text.
    $container->setType('text/html');
    $container->save();
    // Raw data container view should display formatted HTML.
    $this->drupalGet($this->getUrl());
    $this->assertRaw('Hello <strong>World!</strong>');
    $this->assertText('Hello World!');

    // Change data to JSON.
    $container->setType('application/json');
    $container->setData('{"greeting": "Hello World!"}');
    $container->save();
    // Raw data container view should display pretty-printed data.
    $this->drupalGet($this->getUrl());
    $this->assertText('&quot;greeting&quot;: &quot;Hello World!&quot;');

    // Change model plugin to test model plugin. A matching model is installed
    // by collect_test.
    $container->setSchemaUri('https://drupal.org/project/collect/schema/test');
    $container->save();
    // Single container view should show values in table.
    $this->drupalGet($container->urlInfo()->setAbsolute(TRUE));
    $this->assertRaw('<td>Hello World!</td>');

    // Set multiple value and test the table output.
    $container->setData('{"hobbies": ["Hacking", "Feeding Druplicon"]}');
    $container->save();
    $this->drupalGet($this->getUrl());
    $this->assertRaw('<ul><li>Hacking</li><li>Feeding Druplicon</li></ul>');

    // Change model plugin to specialized test model plugin. A matching model is
    // installed by collect_test.
    $container->setData('{"greeting": "Hello World!"}');
    $container->setSchemaUri('https://drupal.org/project/collect/schema/test/greeting');
    $container->save();
    // Single container view should show data rendered with specialized method.
    $this->drupalGet($this->getUrl());
    $this->assertText('Let me say Hello World!');

    // Remove schema URI and MIME type.
    $container->setSchemaUri('');
    $container->setType('');
    $container->save();
    // Raw data container view should not display not data of unknown type.
    $this->drupalGet($this->getUrl());
    $this->clickLink(t('Raw data'));
    $this->assertNoText('Hello World!');
    $this->assertText(t('Unknown schema and MIME type.'));


    // Tests container listing.
    $url = $this->fetchWebResource(200);
    $web_page_container_id = $this->getLatestId();
    $this->drupalGet('admin/content/collect', ['query' => ['schema_uri' => 'urls']]);
    $this->assertText(t('There are no containers'));
    $this->drupalGet('admin/content/collect', ['query' => ['origin_uri' => $url]]);
    $this->assertEqual(count($this->xpath('//tbody/tr')), 1);
    $web_page_container = Container::load($web_page_container_id);
    $this->assertText($web_page_container->getType());
    $this->assertRaw($web_page_container->getSchemaUri());
    $this->assertNoLink($web_page_container->getOriginUri());
    $this->clickLink(t('2'));
    $this->assertResponse(200);

    // Assert no link to the model is displayed.
    $this->assertNoText(t('Model'));
    $this->assertNoLink(t('Collect Fetch URL'));

    // Apply Fetch URL model plugin.
    // Assert a generated web resource is accessible.
    $this->clickLink(t('Set up a @plugin model', [
      '@plugin' => t('Collect Fetch URL'),
    ]));
    $this->drupalPostForm(NULL, [
      'id' => 'collect_fetch_url',
    ], t('Save'));

    // Assert the link to the model is displayed.
    $this->assertText(t('Model'));
    $this->assertLink(t('Collect Fetch URL'));

    $this->clickLink(t('Show content in a new window'));
    $this->assertResponse(200);
    $this->drupalGet('admin/content/collect');
    $this->assertRaw('<strong>Namespace</strong> collect: http://schema.md-systems.ch/collect/0.0.1/');
    $this->clickLink(t('View'));
    $this->clickLink(t('Show content in a new window'));
    $this->assertResponse(200);

    // Test Uri tail formatter doesn't displays html tags.
    $this->drupalGet('admin/content/collect');
    $this->assertNoRaw('&lt;span title=&quot;http://schema.md-systems.ch/collect/0.0.1/url&quot;&gt;collect:url&lt;/span&gt;');
  }

  /**
   * Tests fetching a web resource.
   */
  public function testFetchWebResource() {
    $user = $this->drupalCreateUser(array(
      'administer collect',
      'access administration pages',
    ));
    $this->drupalLogin($user);

    // Get Fetch URL form.
    $this->drupalGet('admin/content/collect');
    $this->clickLink(t('Fetch URL'));

    // Test Fetch URL form to empty URL and check the error message.
    $this->drupalPostForm(NULL, array('url' => ''), t('Get page'));
    $this->assertText(t('Missing URL.'));

    // Test Fetch URL form to invalid URL and check the error message.
    $url = 'admin';
    $this->drupalPostForm(NULL, array('url' => $url), t('Get page'));
    $this->assertText(t('Invalid URL @url', ['@url' => $url]));

    // Test Fetch URL form to valid URL without Accept request header.
    $url = $this->fetchWebResource(200, '');
    $this->assertText(t('Web resource from @url has been saved.', array('@url' => $url)));

    // Ensure it is listed.
    // Assert the schema URI is shortened.
    $this->assertText('collect:url');
    $this->assertRaw('http://schema.md-systems.ch/collect/0.0.1/url');

    // Assert that schema URI and MIME type are displayed on the detail page.
    $this->clickLink(t('View'));
    $this->assertText($url);
    $this->assertText(t('Schema URI'));
    $this->assertRaw('http://schema.md-systems.ch/collect/0.0.1/url');
    $this->assertText(t('MIME Type'));
    $this->assertText('application/json');
    $container_url = $this->getUrl();
    $this->clickLink(t('Raw data'));
    $this->assertRaw('request-headers');
    $this->drupalGet($container_url);

    // Assert Fetch URL model plugin suggestion is displayed.
    // Assert the model plugin is applied.
    $this->clickLink(t('Set up a @plugin model', ['@plugin' => t('Collect Fetch URL')]));
    $this->assertFieldByXPath('//details[summary="' . t('Properties') . '"]', NULL, 'Found properties table.');
    $this->assertText(t('Request header: Accept'));
    $this->assertText(t('Response header: Content-Type'));
    $this->assertText(t('Raw body content'));
    $this->assertText(t('Body text'));
    $this->drupalPostForm(NULL, array(
      'label' => 'Collect Fetch URL',
      'id' => 'collect_fetch_url',
      'uri_pattern' => 'http://schema.md-systems.ch/collect/0.0.1/url',
      'plugin_id' => 'collect_fetch_url',
      'container_revision' => TRUE,
    ), t('Save'));
    $this->assertText(t('Model @plugin has been saved.', [
      '@plugin' => t('Collect Fetch URL'),
    ]));

    // Assert empty Accept header.
    // Assert that body content on the generated web resource is equal to the body
    // content stored in collect container.
    $first_url_container_id = $this->getLatestId();
    $this->assertNoLink(t('Set up a @plugin model', [
      '@plugin' => t('Collect Fetch URL'),
    ]));
    $this->clickLink(t('Show content in a new window'));
    $data = Json::decode(Container::load($first_url_container_id)->getData());
    $this->assertEqual($data['request-headers']['Accept'][0], '');
    $body = $data['body'];
    $this->assertEqual($body, $this->getRawContent());
    $this->drupalGet('admin/content/collect/' . $first_url_container_id);

    // Test Fetch URL with custom schema URI.
    $url = 'http://schema.md-systems.ch/custom';
    $this->fetchWebResource(200, '', $url);
    // Assert the schema URI is present.
    $this->assertRaw($url);

    // Test Fetch URL form to page that does not exist.
    $not_found_page_url = $this->fetchWebResource(404);
    $this->assertText(t('Oops! Web resource from :url has not been saved. Error code @code with message @message.', [
      ':url' => $not_found_page_url,
      '@code' => 404,
      '@message' => 'Not Found'
    ]));

    // Test Fetch URL form with text/html header and assert Accept header.
    $this->fetchWebResource(200);
    $this->clickLink(t('here'));
    \Drupal::entityManager()->getStorage('collect_container')->resetCache();
    $data = Json::decode(Container::load($first_url_container_id)->getData());
    $this->assertEqual($data['request-headers']['Accept'][0], 'text/html');

    // Test Fetch URL form with application/json header and assert JSON format.
    $this->fetchWebResource(200, 'application/json');
    $this->clickLink(t('here'));
    \Drupal::entityManager()->getStorage('collect_container')->resetCache();
    $data = Json::decode(Container::load($first_url_container_id)->getData());
    $this->assertEqual($data['body'], '{"headers": {"status_code": 200}}');
    $this->assertEqual($data['request-headers']['Accept'][0], 'application/json');

    // Test Fetch URL form with custom application/json header.
    $url = \Drupal::url('collect.make_response', ['status_code' => 200], ['absolute' => TRUE]);
    $this->drupalPostForm('admin/content/collect/url', array(
      'url' => $url,
      'accept_header' => 'custom',
      'custom' => 'application/json',
    ), t('Get page'));
    $this->clickLink(t('here'));
    \Drupal::entityManager()->getStorage('collect_container')->resetCache();
    $data = Json::decode(Container::load($first_url_container_id)->getData());
    $this->assertEqual($data['request-headers']['Accept'][0], 'application/json');

    // Test Fetch URL form with application/json header and assert JSON format.
    $this->fetchWebResource(200, 'application/json');
    $this->clickLink(t('here'));
    \Drupal::entityManager()->getStorage('collect_container')->resetCache();
    $data = Json::decode(Container::load($first_url_container_id)->getData());
    $this->clickLink(t('Show content in a new window'));

    // Tests body, headers of the generated web resource.
    $this->assertEqual($data['body'], '{"headers": {"status_code": 200}}');
    $url = $this->getUrl();
    $this->assertEqual($data['body'], $this->getRawContent());
    $this->assertEqual($data['request-headers']['Accept'][0], 'application/json');
    $this->assertRaw('{"headers": {"status_code": 200}}');
    $this->assertEqual($this->drupalGetHeader('Content-Type'), 'application/json');
    $this->drupalLogout();
    $this->drupalGet($url);
    $this->assertResponse(403);
    $this->drupalLogin($user);

    // Tests Fetch URL form with web resource content charset other than UTF-8.
    $url = \Drupal::url('collect.make_response', ['status_code' => 200], ['absolute' => TRUE]);
    $this->drupalPostForm('admin/content/collect/url', array(
      'url' => $url,
      'accept_header' => 'custom',
      'custom' => 'text/html; charset=ISO-8859-1',
    ), t('Get page'));

    // Tests Fetch URL form with ISO-8859-1 encoded web resource content and
    // specific Content-Type charset=ISO-8859-1 header.
    $this->fetchNonUtf8WebPage('ISO-8859-1', '; charset=ISO-8859-1');

    $this->clickLink(t('here'));
    $this->assertText(t('Web resource content has been converted from ISO-8859-1 to UTF-8 charset.'));
    \Drupal::entityManager()->getStorage('collect_container')->resetCache();
    $data = Json::decode(Container::load($this->getLatestId())->getData());
    $this->clickLink(t('Show content in a new window'));
    $this->assertEqual($data['body'], $this->getRawContent());

    // Tests Fetch URL form with ISO-8859-2 encoded web resource content and
    // specific Content-Type charset=ISO-8859-2 header.
    $this->fetchNonUtf8WebPage('ISO-8859-2', '; charset=ISO-8859-2');
    $this->clickLink(t('here'));
    $this->assertText(t('Web resource content has been converted from ISO-8859-2 to UTF-8 charset.'));

    // Tests Fetch URL form with ISO-8859-2 encoded web resource content and
    // empty charset in Content-Type header field.
    $url = $this->fetchNonUtf8WebPage('ISO-8859-2', '');
    $this->assertText(t('Web resource from :url has not been saved. The JSON encoding failed. Content charset is invalid.', [':url' => $url]));
  }

  /**
   * Fetches a non UTF-8 encoded web resource content.
   */
  public function fetchNonUtf8WebPage($charset, $content_type) {
    $url = \Drupal::url('collect.make_non_utf8_response', ['charset' => $charset], ['absolute' => TRUE]);
    $this->drupalPostForm('admin/content/collect/url', array(
      'url' => $url,
      'accept_header' => 'custom',
      'custom' => 'text/html' . $content_type,
    ), t('Get page'));
    return $url;
  }

  /**
   * Fetches a web resource.
   */
  public function fetchWebResource($status_code, $accept_header = 'text/html', $schema_uri = 'http://schema.md-systems.ch/collect/0.0.1/url') {
    $url = \Drupal::url('collect.make_response', ['status_code' => $status_code], ['absolute' => TRUE]);
    $this->drupalPostForm('admin/content/collect/url', array(
      'url' => $url,
      'accept_header' => $accept_header,
      'schema_uri' => $schema_uri,
    ), t('Get page'));
    return $url;
  }

  /**
   * Returns the highest stored ID of a given entity type.
   *
   * @param string $entity_type
   *   The entity type ID to get the ID for.
   *
   * @return int
   *   The highest ID of the stored entities.
   */
  protected function getLatestId($entity_type = 'collect_container') {
    $id_key = \Drupal::entityManager()->getStorage($entity_type)->getEntityType()->getKey('id');
    $ids = \Drupal::entityQuery($entity_type)
      ->sort($id_key, 'DESC')
      ->pager(1)
      ->execute();
    return current($ids);
  }

  /**
   * Tests the CRUD flow of models.
   */
  public function testModelUi() {
    $user = $this->drupalCreateUser(['access administration pages', 'administer collect']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/structure/collect');
    $this->clickLink('Models');
    $this->assertUrl('admin/structure/collect/model');

    // The test model plugin is configured by default.
    $this->assertText('Test Greeting Model');
    $this->assertText('https://drupal.org/project/collect/schema/test/greeting');
    $this->assertText('Test Model Plugin');

    // Use the disable/enable operation links.
    $this->clickLink('Disable');
    $this->assertText('Model Test Greeting Model has been disabled.');
    $this->assertFalse(Model::load('test_greeting')->status());
    $this->clickLink('Enable');
    $this->assertText('Model Test Greeting Model has been enabled.');
    $this->assertTrue(Model::load('test_greeting')->status());

    // Use the disable/enable checkbox.
    $this->drupalGet('admin/structure/collect/model/manage/test');
    $this->assertFieldChecked('edit-status');
    $edit = array(
      'status' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t("Save"));
    $this->drupalGet('admin/structure/collect/model/manage/test');
    $this->assertNoFieldChecked('edit-status');
    $this->drupalGet('admin/structure/collect/model');
    $this->clickLink(t('Enable'));

    // Use the edit form.
    $this->clickLink('Edit');
    $edit = array(
      'label' => 'Edited Model',
      'uri_pattern' => 'https://drupal.org/project/collect/schema/edited',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Model Edited Model has been saved.');
    $this->assertText('https://drupal.org/project/collect/schema/edited');
    $this->assertNoText('Test Greeting Model');

    // Edit properties.
    $this->clickLink(t('Edit'));
    $this->assertText(t('Hobbies'));
    $this->assertText(t('list of @type', ['@type' => 'string']));
    $this->assertLink(t('Remove'));
    // Add suggested property.
    $this->drupalGet('admin/structure/collect/model/manage/test/property/greeting/remove');
    $this->drupalPostForm(NULL, array(), t('Add'));
    $this->assertText(t('No suggested property selected.'));
    $this->assertFieldByXPath('//*[@aria-invalid="true"]/@name', 'property_name');
    $this->drupalPostForm(NULL, array('property_name' => 'greeting'), t('Add'));
    $this->assertFieldByXPath('//td[contains(*,"' . t('Greeting') . '")]', NULL, 'Label of added suggested property found in properties table');
    // Add custom property.
    $this->clickLink(t('Add custom property'));
    $this->assertUrl('admin/structure/collect/model/manage/test/property/add');
    $this->assertLink(t('Cancel'));
    $this->assertRaw(t('Add property to %model', ['%model' => 'Test Model']));
    $this->drupalPostForm(NULL, [
      'query' => 'icecream',
      'name' => 'glace',
      'type' => 'boolean',
      'label' => t('Ice cream'),
      'description' => t('Cold sweet stuff'),
    ], t('Save'));
    $this->assertRaw(t('The %label property has been saved to %model', ['%label' => 'Ice cream', '%model' => 'Test Model']));
    $this->assertText('icecream');
    $this->assertText('glace');
    $this->assertText('boolean');
    $this->assertText(t('Ice cream'));
    $this->assertText(t('Cold sweet stuff'));
    // Edit property.
    $this->clickLink(t('Edit'));
    $this->assertRaw(t('Edit property %label on %model', ['%label' => 'Hobbies', '%model' => 'Test Model']));
    $this->drupalPostForm(NULL, ['type' => 'language'], t('Save'));
    $this->assertText('language');
    // Remove property.
    $this->clickLink(t('Remove'));
    $this->assertRaw(t('Property %property removed from %model', ['%property' => 'hobbies', '%model' => 'Test Model']));
    $this->assertNoText('language');

    // Use the delete link.
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete the model Test Model?');
    $this->drupalPostForm(NULL, array(), 'Delete');
    $this->assertText('The model Test Model has been deleted.');
    $this->assertNoText('https://drupal.org/project/collect/schema/test');
    $this->clickLink('Delete');
    $this->assertText('Are you sure you want to delete the model Edited Model?');
    $this->drupalPostForm(NULL, array(), 'Delete');
    $this->assertText('The model Edited Model has been deleted.');
    $this->assertNoText('https://drupal.org/project/collect/schema/edited');

    // The remaining CollectJSON field definition model plugin should be locked.
    $this->assertText(t('Collect JSON Definition'));
    $this->assertText('http://schema.md-systems.ch/collect/0.0.1/collectjson-definition/global/fields');
    $this->assertLink(t('Manage processing'));
    $this->clickLink(t('Edit'));
    $this->assertText(t('This model is locked, possibly because it was added by default. It cannot be deleted and some basic settings can not be changed'));
    $this->assertFieldByXPath('//input[@name="uri_pattern" and @disabled="disabled"]');
    $this->assertFieldByXPath('//select[@name="plugin_id" and @disabled="disabled"]');
    $this->assertNoLink('Delete');
    $this->drupalPostForm(NULL, array(), t('Save'));
    $this->assertText(t('Model Collect JSON Definition has been saved.'));
    $this->assertNoLink(t('Delete'));
    $this->drupalGet('admin/structure/collect/model/manage/collectjson_definition/delete');
    $this->assertResponse(403);

    // Use the add button and form.
    $this->drupalGet('admin/structure/collect/model');
    $this->clickLink(t('Add model'));
    $this->assertFieldByXPath('//select[@name="plugin_id"]//option[@value="collectjson"]', NULL, 'Collect JSON plugin is present');
    $this->assertNoFieldByXPath('//select[@name="plugin_id"]//option[@value="collect_field_definition"]', NULL, 'Field Definition plugin is hidden');
    $label = 'New Model';
    $edit = array(
      'label' => $label,
      'id' => 'new',
      'uri_pattern' => 'https://drupal.org/project/collect/schema/new',
      'plugin_id' => 'test',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertRaw(t('Model %label has been saved.', array('%label' => $label)));
    $this->assertText('https://drupal.org/project/collect/schema/new');
    $this->assertText('Test Model Plugin');

    // Use the same fields and check for 'unique URI'
    $this->drupalGet('admin/structure/collect/model');
    $this->clickLink(t('Add model'));
    $label = 'Newer Model';
    $edit = array(
      'label' => $label,
      'id' => 'new_two',
      'uri_pattern' => 'https://drupal.org/project/collect/schema/new',
      'plugin_id' => 'test',
    );
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertRaw(t('The URI pattern must be unique!'));
    $this->assertNoRaw(t('Model %label has been saved.', array('%label' => $label)));
  }

  /**
   * Test the model suggestion feature.
   */
  public function testModelSuggestion() {
    // Create a test container.
    $container = Container::create(array(
      'data' => '{"greeting": "Good morning"}',
      'schema_uri' => 'https://drupal.org/project/collect/schema/test/specific',
    ));
    $container->save();

    // Log in and view the container.
    $user = $this->drupalCreateUser(array('administer collect', 'access administration pages'));
    $this->drupalLogin($user);
    $this->drupalGet('admin/content/collect/' . $container->id());

    // Default test model should be active, and nothing suggested.
    $this->assertRaw('<td>Good morning</td>');
    $this->assertNoText('Set up a Test Model Plugin with specialized display model');

    // Remove default test model.
    Model::load('test')->delete();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalGet($this->getUrl());

    // Test model plugin should be suggested.
    $this->clickLink(t('Set up a Test Model Plugin with specialized display model'));

    // Model form should be prefilled.
    $this->assertFieldByName('label', 'Test label');
    $this->assertFieldByName('uri_pattern', 'https://drupal.org/project/collect/schema/test');
    $this->assertFieldByName('plugin_id', 'test_specialized_display');
    // Ignore testing prefilled ID, because it is filled in by JavaScript.
    $this->drupalPostForm(NULL, ['id' => 'test_label'], t('Save'));

    // Form submission should redirect back to container view, and new model
    // should take effect.
    $this->assertUrl('admin/content/collect/' . $container->id());
    $this->assertText('Model Test label has been saved.');
    $this->assertText('Let me say Good morning');
  }

}
