<?php

namespace Drupal\contacts\Plugin\Field\FieldFormatter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'status_log_list' field formatter.
 *
 * @FieldFormatter(
 *   id = "status_log_list",
 *   label = @Translation("Status Log (list)"),
 *   field_types = {
 *     "status_log"
 *   }
 * )
 */
class StatusLogFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManagaer;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManagaer = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $container->get('entity_type.manager'),
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [
      '#title' => $this->fieldDefinition->getLabel(),
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [],
    ];

    foreach ($items as $delta => $item) {
      /* @var \Drupal\drs_submission\Plugin\Field\FieldType\StatusLog $item */
      $values = $item->getValue();
      $user = $this->entityTypeManagaer->getStorage('user')
        ->load($values['uid']);
      $elements['#items'][$delta] = $this->t('@time: Status was changed from %status_old to %status_new by @username.', [
        '%status_new' => $values['value'],
        '%status_old' => $values['previous'],
        '@time' => DateTimePlus::createFromTimestamp($values['timestamp'])
          ->format('Y-m-d H:i:s'),
        '@username' => $user->label(),
      ]);
    }

    return $elements;
  }

}
