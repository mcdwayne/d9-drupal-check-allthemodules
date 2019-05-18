<?php

namespace Drupal\cloud\Plugin\Field\FieldFormatter;

use Drupal\cloud\Plugin\CloudConfigPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'cloud_id_formatter' formatter.
 *
 * This formatter links a cloud config name to the list of server templates.
 *
 * @FieldFormatter(
 *   id = "cloud_id_formatter",
 *   label = @Translation("Cloud Id formatter"),
 *   field_types = {
 *     "string",
 *     "uri",
 *   }
 * )
 */
class CloudIdLinkFormatter extends FormatterBase implements ContainerFactoryPluginInterface {


  /**
   * CloudConfigPlugin.
   *
   * @var \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface
   */
  protected $cloudConfigPluginManager;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\cloud\Plugin\CloudConfigPluginManagerInterface $cloud_config_plugin_manager
   *   The cloud config plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, CloudConfigPluginManagerInterface $cloud_config_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->cloudConfigPluginManager = $cloud_config_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.cloud_config_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $this->cloudConfigPluginManager->setCloudContext($item->value);
        $route = $this->cloudConfigPluginManager->getInstanceCollectionTemplateName();

        $name = $this->getCloudContextName($item->value);
        $elements[$delta] = [
          '#type' => 'link',
          '#url' => Url::fromRoute($route, ['cloud_context' => $item->value]),
          '#title' => !empty($name) ? $name : $item->value,
        ];
      }
    }
    return $elements;
  }

  /**
   * Resolve the cloud context name.
   *
   * @param string $cloud_context
   *   The cloud_context to resolve.
   *
   * @return string
   *   The cloud context name.
   */
  private function getCloudContextName($cloud_context) {
    $name = '';
    $entities = $this->entityTypeManager->getStorage('cloud_config')
      ->loadByProperties(
        [
          'cloud_context' => $cloud_context,
        ]);
    if (count($entities) == 1) {
      $entity = array_shift($entities);
      $name = $entity->getName();
    }
    return $name;
  }

}
