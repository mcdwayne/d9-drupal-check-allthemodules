<?php

namespace Drupal\collect\Tests;

use Drupal\collect\Entity\Container;
use Drupal\search_api\Utility\Utility;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Search API integration.
 *
 * @group collect
 *
 * @dependencies search_api
 */
class SearchApiTest extends WebTestBase {

  /**
   * Disable config schema checking because of Search API issues.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'search_api_db',
  ];

  /**
   * Tests setting up and using a search index for Collect.
   */
  public function testSearchApi() {
    // Log in.
    $user = $this->drupalCreateUser([
      'administer collect',
      'administer search_api',
    ], 'Nulla neque');
    $this->drupalLogin($user);

    // Capture users as containers.
    $this->drupalPostForm('admin/content/collect/capture', [
      'entity_type' => 'user',
    ], t('Select entity type'));
    $this->drupalPostForm(NULL, [
      'operation' => 'multiple',
    ], t('Capture'));
    $this->assertText(t('All User entites have been captured.'));
    $container_ids = \Drupal::entityQuery('collect_container')->execute();

    // Create the suggested model for captured users.
    $containers = Container::loadMultiple();
    $last_container = end($containers);
    $this->drupalPostForm('admin/structure/collect/model/add/suggested/' . $last_container->id(), [
      'label' => t('Captured user'),
      'id' => 'captured_user',
    ], t('Save'));
    $this->assertText(t('Model @label has been saved.', ['@label' => 'Captured user']));

    // Create a search server.
    $this->drupalPostForm('admin/config/search/search-api/add-server', [
      'name' => 'Database search server',
      'id' => 'database_search_server',
      'backend' => 'search_api_db',
    ], t('Save'));
    // Server form Ajax support is broken. Edit again to set database.
    $this->drupalPostForm('admin/config/search/search-api/server/database_search_server/edit', array(), t('Save'));
    $this->assertText(t('The server was successfully saved.'));

    // Create a search index.
    $this->drupalPostForm('admin/config/search/search-api/add-index', [
      'name' => t('Captured User entities'),
      'id' => 'captured_user_entities',
      'datasources[collect:captured_user]' => 'collect:captured_user',
      'server' => 'database_search_server',
      'options[index_directly]' => FALSE,
    ], t('Save'));

    // Select fields for the index.
    $this->drupalGet('admin/config/search/search-api/index/captured_user_entities/fields/add');
    $this->clickLinkByParentText(t('@type: @label', ['@type' => 'Model', '@label' => t('Captured user')]));
    $this->assertText(t('Name'));
    $this->assertText(t('Initial email'));
    $this->addField('collect:captured_user', 'name', 'captured_user_entities', 'Name');
    $this->drupalPostForm('admin/config/search/search-api/index/captured_user_entities/fields', [], t('Save changes'));
    $this->assertRaw('Name');

    // Index containers.
    $this->drupalPostForm('admin/config/search/search-api/index/captured_user_entities', array(), t('Index now'));
    $this->assertText(t('Successfully indexed 3 items.'));

    $index_item = \Drupal::database()->select('search_api_db_captured_user_entities', 's')
      ->fields('s', ['item_id', 'name'])
      ->condition('item_id', 'collect:captured_user/' . end($container_ids))
      ->execute()
      ->fetchAssoc();
    $this->assertEqual('Nulla neque', $index_item['name']);

    // @todo Test search view teasers.
  }

  /**
   * Clicks a link whose parent element contains the given text.
   *
   * @param string $text
   *   Text to search for.
   * @param int $index
   *   Link position counting from zero.
   */
  protected function clickLinkByParentText($text, $index = 0) {
    $this->clickLinkHelper('', $index, '//*[contains(text(),"' . $text . '")]/a/@href');
  }

  /**
   * Adds a field for a specific property to the index.
   *
   * This method was originally added in
   * \Drupal\search_api\Tests\IntegrationTest::addField().
   *
   * @param string|null $datasource_id
   *   The property's datasource's ID, or NULL if it is a datasource-independent
   *   property.
   * @param string $property_path
   *   The property path.
   * @param string $index_id
   *   The index ID.
   * @param string|null $label
   *   (optional) If given, the label to check for in the success message.
   */
  protected function addField($datasource_id, $property_path, $index_id, $label = NULL) {
    $path = 'admin/config/search/search-api/index/' . $index_id . '/fields/add';
    $url_options = array('query' => array('datasource' => $datasource_id));
    if ($this->getUrl() === $this->buildUrl($path, $url_options)) {
      $path = NULL;
    }

    // Unfortunately it doesn't seem possible to specify the clicked button by
    // anything other than label, so we have to pass it as extra POST data.
    $combined_property_path = Utility::createCombinedId($datasource_id, $property_path);
    $post = '&' . $this->serializePostValues(array($combined_property_path => t('Add')));
    $this->drupalPostForm($path, array(), NULL, $url_options, array(), NULL, $post);
    if ($label) {
      $args['%label'] = $label;
      $this->assertRaw(t('Field %label was added to the index.', $args));
    }
  }

}
