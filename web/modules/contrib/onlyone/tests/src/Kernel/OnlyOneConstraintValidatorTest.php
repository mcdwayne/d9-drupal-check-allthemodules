<?php

namespace Drupal\Tests\onlyone\Kernel;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests validation constraints for OnlyOneConstraintValidator.
 *
 * @coversDefaultClass \Drupal\onlyone\Plugin\Validation\Constraint\OnlyOneConstraintValidator
 * @group Validation
 * @group onlyone
 */
class OnlyOneConstraintValidatorTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'onlyone',
    'system',
    'field',
    'text',
    'user',
    'filter',
  ];

  /**
   * Tests the OnlyOneConstraintValidator.
   */
  public function testValidation() {
    // Installing schemas.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    // Installing configuration files.
    $this->installConfig('node');
    $this->installConfig('onlyone');
    $this->installConfig('filter');

    // Getting the entity.definition_update_manager service.
    $manager = $this->container->get('entity.definition_update_manager');
    // Getting the node definition.
    $node_definition = $manager->getEntityType('node');
    // Adding the constraint.
    $node_definition->addConstraint('OnlyOne');

    // Creating a Content type.
    $this->createContentType(['type' => 'page']);

    // Getting the config factory service.
    $config_factory = $this->container->get('config.factory');
    // Configuring page content type to have onlyone node.
    $config_factory->getEditable('onlyone.settings')->set('onlyone_node_types', ['page'])->save();

    // Setting values for a page node.
    $page_settings = [
      'body' => [
        [
          'value' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'uid' => \Drupal::currentUser()->id(),
    ];

    // Checking the constraint for page, for the first node we should not have
    // violations because is the first node of a configured content type, for
    // the second should be 1 violation because the content type is configured
    // to have only one node.
    for ($i = 0; $i <= 1; $i++) {
      // Creating the new node withtout saving it.
      $page_node = Node::create($page_settings);
      // Validating the node.
      $page_violations = $page_node->validate();
      // Asserting.
      $this->assertEqual($page_violations->count(), $i, 'Constraint validation failed.');
      // Only saving for the first node.
      if (!$i) {
        // Saving the page node.
        $page_node->save();
      }
    }

    // Testing for a not configured content type. Creating the article content
    // type.
    $this->createContentType(['type' => 'article']);

    // Setting values for a new article node.
    $article_settings = $page_settings;
    $article_settings['type'] = 'article';

    // Checking the constraint for article, we should not have violations.
    for ($i = 0; $i <= 2; $i++) {
      // Creating the new node withtout saving it.
      $article_node = Node::create($article_settings);
      // Validating the node.
      $article_violations = $article_node->validate();
      // Asserting.
      $this->assertEqual($article_violations->count(), 0, 'Constraint validation failed.');
      // Saving the page node.
      $article_node->save();
    }
  }

}
