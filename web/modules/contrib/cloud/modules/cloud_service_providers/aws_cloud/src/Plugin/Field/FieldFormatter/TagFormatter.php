<?php

namespace Drupal\aws_cloud\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'tag_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "tag_formatter",
 *   label = @Translation("Tag formatter"),
 *   field_types = {
 *     "tag"
 *   }
 * )
 */
class TagFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
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
    $label,
    $view_mode,
    array $third_party_settings,
    DateFormatterInterface $date_formatter) {

    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $rows = [];

    foreach ($items as $item) {
      /* @var \Drupal\aws_cloud\Plugin\Field\FieldType\Tag $item */
      if (!$item->isEmpty()) {
        $value = $item->tag_value;

        // If $item->tag_key contains a keyword '_timestamp'.
        // e.g. $item->tag_key = 'cloud_termination_timestamp'.
        if ((mb_strpos($item->tag_key, '_timestamp') !== FALSE)
        && !empty($value)
        && is_numeric($value)) {
          $value = $this->dateFormatter->format($value, 'short');
        }

        $rows[] = [
          $item->tag_key,
          $value,
        ];
      }
    }

    if (count($rows)) {
      $elements[0] = [
        '#theme' => 'table',
        '#header' => [
          $this->t('Key'),
          $this->t('Value'),
        ],
        '#rows' => $rows,
      ];
    }

    return $elements;
  }

}
