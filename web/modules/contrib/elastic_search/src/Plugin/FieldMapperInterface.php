<?php

namespace Drupal\elastic_search\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for Field mapper plugin plugins.
 */
interface FieldMapperInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Const that can be used to indicate that the field type is always available
   */
  const ALWAYS_AVAILABLE = 'all';

  /**
   * @return mixed
   */
  public function getElasticType();

  /**
   * @return array
   */
  public function getSupportedTypes();

  /**
   * @param array $defaults
   * @param int   $depth
   *
   * @return array|mixed
   */
  public function getFormFields(array $defaults, int $depth = 0): array;

  /**
   * @return bool
   */
  public function supportsFields(): bool;

  /**
   * @param array $data
   *
   * @return array
   */
  public function getDslFromData(array $data): array;

  /**
   * @param string $id
   * @param array  $data
   * @param array  $fieldMappingData
   *
   * @return array
   * @throws \Drupal\elastic_search\Exception\FieldMapperFlattenSkipException
   */
  public function normalizeFieldData(string $id,
                                     array $data,
                                     array $fieldMappingData);

}
