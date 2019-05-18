<?php

namespace Drupal\aws_cloud\Plugin\Field\FieldFormatter;

use Drupal\aws_cloud\Entity\Ec2\Volume;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'unused_volume_formatter' formatter.
 *
 * This formatter adds a css class called unused-volume to the created date.
 *
 * @FieldFormatter(
 *   id = "unused_volume_string_formatter",
 *   label = @Translation("Unused volume string formatter"),
 *   field_types = {
 *     "string",
 *     "uri",
 *   }
 * )
 */
class UnusedVolumeStringFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $entity_type_manager);

    $this->configFactory = $config_factory;
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if ($this->configFactory->get('aws_cloud.settings')->get('aws_cloud_volume_notification')) {
      $entity = $items->getEntity();
      foreach ($items as $delta => $item) {
        if ($entity instanceof Volume) {
          /* @var \Drupal\aws_cloud\Entity\Ec2\Volume $entity */
          if ($entity->isVolumeUnused()) {
            $elements[$delta]['#markup'] = '<span class="unused-volume">' . $item->value . '</span>';
          }
          else {
            $elements[$delta]['#markup'] = $item->value;
          }
        }
      }
      $elements['#attached']['library'][] = 'aws_cloud/aws_cloud_view_builder';
    }
    return $elements;
  }

}
