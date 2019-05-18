<?php
/**
 * @file
 * Contains
 *   \Drupal\collect\Plugin\search_api\datasource\CollectDatasourceDeriver.
 */

namespace Drupal\collect\Plugin\search_api\datasource;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Collect datasource plugins.
 *
 * @see Drupal\collect\Plugin\search_api\datasource\CollectDatasource
 */
class CollectDatasourceDeriver implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * Derivative plugin definitions.
   *
   * @var array[]
   */
  protected $definitions;

  /**
   * The injected schema config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $modelStorage;

  /**
   * Constructs a CollectDatasourceDeriver object.
   */
  public function __construct(ConfigEntityStorageInterface $model_storage, TranslationInterface $translation) {
    $this->modelStorage = $model_storage;
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('collect_model'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $definitions = $this->getDerivativeDefinitions($base_plugin_definition);
    return isset($definitions[$derivative_id]) ? $definitions[$derivative_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->definitions)) {
      foreach ($this->modelStorage->loadByProperties(['status' => TRUE]) as $model) {
        $this->definitions[$model->id()] = [
          'model' => $model->id(),
          'label' => $this->t('@type: @label', ['@type' => $model->getEntityType()->getLabel(), '@label' => $model->label()]),
        ] + $base_plugin_definition;
      }
    }
    return $this->definitions;
  }

}
