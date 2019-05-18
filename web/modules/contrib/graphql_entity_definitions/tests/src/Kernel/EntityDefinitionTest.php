<?php

namespace Drupal\Tests\graphql_entity_definitions\Kernel;

use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\graphql_core\Kernel\GraphQLContentTestBase;

/**
 * Test graphql entity definitions.
 *
 * @group graphql_entity_definitions
 */
class EntityDefinitionTest extends GraphQLContentTestBase {
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'text',
    'filter',
    'graphql_core',
    'graphql_entity_definitions',
    'content_translation',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // The global CurrentUserContext doesn't work properly without a
    // fully-installed user module.
    // @see https://www.drupal.org/project/rules/issues/2989417
    $this->container->get('module_handler')->loadInclude('user', 'install');
    user_install();
  }

  /**
   * Test entity definition query label.
   */
  public function testDefinitionLabel() {
    $query = $this->getQueryFromFile('definition.gql');
    $result = $this->query($query, ['name' => 'user']);
    $content = json_decode($result->getContent(), TRUE);
    $definition = $content['data']['definition'];
    $label = $definition['label'];

    self::assertEquals('User', $label, 'Result has correct definition label.');
  }

  /**
   * Test entity definition query settings.
   */
  public function testDefinitionSettings() {
    $query = $this->getQueryFromFile('definition.gql');
    $result = $this->query($query, ['name' => 'user']);
    $content = json_decode($result->getContent(), TRUE);
    $definition = $content['data']['definition'];
    $settings = $definition['fields'][0]['settings'];

    self::assertEquals('unsigned', $settings[0]['key'], 'Result has correct setting keys.');
    self::assertTrue($settings[0]['value'], 'Result has correct setting values.');
  }

}
