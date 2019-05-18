<?php

namespace Drupal\image_styles_mapping\Plugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins able to add columns on image styles mapping reports.
 *
 * @ingroup plugin_api
 */
abstract class ImageStylesMappingPluginBase extends PluginBase implements ImageStylesMappingPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return ['image'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->t('Image styles (not sortable)');
  }

  /**
   * {@inheritdoc}
   */
  public function getRowData(array $field_settings) {
    return new FormattableMarkup('', []);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Helper function.
   *
   * Checks if a value is used in an array.
   *
   * @param string $needle
   *   The value searched.
   * @param array $haystack
   *   The array in which the value is searched.
   * @param bool $result
   *   If the needle has been found.
   *
   * @return bool
   *   TRUE if the value is found. FALSE otherwise.
   */
  public function recursiveSearch($needle, array $haystack, &$result) {
    if (!is_array($haystack)) {
      return FALSE;
    }

    foreach ($haystack as $value) {
      if (is_array($value)) {
        $this->recursiveSearch($needle, $value, $result);
      }
      elseif ($needle === $value) {
        $result = TRUE;
      }
    }
  }

}
