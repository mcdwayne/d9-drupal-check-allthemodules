<?php

namespace Drupal\taarikh\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'taarikh_default' widget.
 *
 * @FieldWidget(
 *   id = "taarikh_default",
 *   label = @Translation("Taarikh date and time"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class TaarikhDefaultWidget extends DateTimeWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_format_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->dateFormatStorage = $date_format_storage;
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
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#type'] = 'taarikh_datetime';

    // If the field is date-only, make sure the title is displayed. Otherwise,
    // wrap everything in a fieldset, and the title will be shown in the legend.
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $element['value']['#title'] = $this->fieldDefinition->getLabel();
      $element['value']['#description'] = $this->fieldDefinition->getDescription();
    }
    else {
      $element['#theme_wrappers'][] = 'fieldset';
    }

    // Identify the type of date and time elements to use.
    switch ($this->getFieldSetting('datetime_type')) {
      case DateTimeItem::DATETIME_TYPE_DATE:
        $date_type = 'text';
        $time_type = 'none';
        $date_format = $this->dateFormatStorage->load('html_date')->getPattern();
        $time_format = '';
        break;

      default:
        $date_type = 'text';
        $time_type = 'time';
        $date_format = $this->dateFormatStorage->load('html_date')->getPattern();
        $time_format = $this->dateFormatStorage->load('html_time')->getPattern();
        break;
    }

    $element['value'] += [
      // @todo: Get first day from system variable.
      '#date_first_day' => 0,
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    return $element;
  }

}
