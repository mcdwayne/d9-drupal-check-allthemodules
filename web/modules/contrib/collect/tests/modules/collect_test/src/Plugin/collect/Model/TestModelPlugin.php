<?php
/**
 * @file
 * Contains \Drupal\collect_test\Plugin\collect\Model\TestModelPlugin.
 */

namespace Drupal\collect_test\Plugin\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\ModelPluginBase;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Collect model plugin used for testing.
 *
 * This model plugin supports data as a JSON object with the single property
 * "greeting".
 *
 * @Model(
 *   id = "test",
 *   label = @Translation("Test Model Plugin"),
 *   description = @Translation("Used only for testing.")
 * )
 */
class TestModelPlugin extends ModelPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function suggestConfig(CollectContainerInterface $container, array $plugin_definition) {
    $model = parent::suggestConfig($container, $plugin_definition);
    $model->setLabel("Test label");
    // Remove last component from URI.
    $model->setUriPattern('https://drupal.org/project/collect/schema/test');
    return $model;
  }

  /**
   * {@inheritdoc}
   */
  public function parse(CollectContainerInterface $collect_container) {
    return Json::decode($collect_container->getData());
  }

  /**
   * {@inheritdoc}
   */
  public function buildTeaser(CollectDataInterface $data) {
    return $this->build($data);
  }

  /**
   * {@inheritdoc}
   */
  public static function getStaticPropertyDefinitions() {
    $properties['greeting'] = new PropertyDefinition('greeting', DataDefinition::create('string')
      ->setLabel('Greeting'));
    return $properties;
  }

}
