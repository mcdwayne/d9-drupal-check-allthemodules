<?php

namespace Drupal\aws_cloud\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'tag_item' widget.
 *
 * @FieldWidget(
 *   id = "tag_item",
 *   label = @Translation("AWS tag"),
 *   field_types = {
 *     "tag"
 *   }
 * )
 */
class TagItem extends WidgetBase implements ContainerFactoryPluginInterface {

  const RESERVED_KEYS = [
    'Name',
    'cloud_launch_origin',
    'cloud_launch_software',
    'cloud_launched_by',
    'cloud_launched_by_uid',
    'cloud_termination_timestamp',
  ];

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * Constructs an EntityLinkFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    DateFormatterInterface $date_formatter) {

    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings);

    $this->dateFormatter = $date_formatter;
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
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $key = isset($items[$delta]->tag_key) ? $items[$delta]->tag_key : NULL;
    $value = isset($items[$delta]->tag_value) ? $items[$delta]->tag_value : NULL;
    $disabled = in_array($key, self::RESERVED_KEYS);

    // Disable special tags.
    if ($key != NULL && strpos($key, 'aws:') === 0) {
      $disabled = TRUE;
    }

    $element['tag_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#size' => 60,
      '#default_value' => $key,
      '#maxlength' => 255,
      '#prefix' => '<div class="col-sm-6">',
      '#suffix' => '</div>',
      '#disabled' => $disabled,
    ];

    // If $key contains a keyword '_timestamp'.
    // e.g. $key = 'cloud_termination_timestamp'.
    if ((mb_strpos($key, '_timestamp') !== FALSE)
    && !empty($value)
    && is_numeric($value)) {
      $value = $this->dateFormatter->format($value, 'short');
    }

    $element['tag_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#size' => 60,
      '#default_value' => $value,
      '#maxlength' => 255,
      '#prefix' => '<div class="col-sm-6">',
      '#suffix' => '</div>',
      '#disabled' => $disabled,
    ];

    return $element;
  }

}
