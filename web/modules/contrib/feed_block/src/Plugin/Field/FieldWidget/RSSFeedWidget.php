<?php

namespace Drupal\feed_block\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Plugin implementation of the 'rss_feed_widget' widget.
 *
 * @FieldWidget(
 *   id = "rss_feed_widget",
 *   label = @Translation("RSS Feed Widget"),
 *   field_types = {
 *     "rss_feed_field"
 *   }
 * )
 */
class RSSFeedWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->dateFormatter = $date_formatter;
    $this->dateStorage = $date_storage;
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
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $items->getName();
    $item = $items[$delta];
    $element['feed_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Feed URL'),
      '#default_value' => isset($item->feed_uri) ? $item->feed_uri : NULL,
      '#maxlength' => 2048,
      '#link_type' => LinkItemInterface::LINK_EXTERNAL,
      '#description' => $this->t('This must be a valid RSS feed.'),
    ];
    $element['count'] = [
      '#type' => 'select',
      '#title' => t('Number of items to display'),
      '#options' => array_combine(range(1, 10), range(1, 10)),
      '#default_value' => isset($item->count) ? $item->count : 5,
    ];
    $element['display_date'] = [
      '#type' => 'checkbox',
      '#title' => t('Display date'),
      '#default_value' => isset($item->display_date) ? $item->display_date : 1,
    ];
    $date_formats = [];
    foreach ($this->dateStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', [
        '@name' => $value->label(),
        '@date' => $this->dateFormatter->format(REQUEST_TIME, $machine_name),
      ]);
    }
    $element['date'] = [
      '#type' => 'details',
      '#title' => t('Date settings'),
      '#open' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="' . $field_name . '[0][display_date]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $element['date']['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats + [
        'custom' => $this->t('Custom'),
        'raw time ago' => $this->t('Time ago'),
        'time ago' => $this->t('Time ago (with "ago" appended)'),
        'raw time hence' => $this->t('Time hence'),
        'time hence' => $this->t('Time hence (with "hence" appended)'),
        'raw time span' => $this->t('Time span (future dates have "-" prepended)'),
        'inverse time span' => $this->t('Time span (past dates have "-" prepended)'),
        'time span' => $this->t('Time span (with "ago/hence" appended)'),
      ],
      '#default_value' => isset($item->date_format) ? $item->date_format : 'small',
    ];
    $element['date']['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('If "Custom", see <a href="http://us.php.net/manual/en/function.date.php" target="_blank">the PHP docs</a> for date formats. Otherwise, enter the number of different time units to display, which defaults to 2.'),
      '#default_value' => isset($item->custom_date_format) ? $item->custom_date_format : 'F j, Y',
    ];
    foreach ([
      'custom',
      'raw time ago',
      'time ago',
      'raw time hence',
      'time hence',
      'raw time span',
      'time span',
      'raw time span',
      'inverse time span',
      'time span',
    ] as $custom_date_possible) {
      $element['date']['custom_date_format']['#states']['visible'][] = [
        ':input[name="' . $field_name . '[0][date][date_format]"]' => [
          'value' => $custom_date_possible,
        ],
      ];
    }
    $element['display_description'] = [
      '#type' => 'checkbox',
      '#title' => t('Display description'),
      '#default_value' => isset($item->display_description) ? $item->display_description : 1,
    ];
    $element['description'] = [
      '#type' => 'details',
      '#title' => t('Description settings'),
      '#open' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="' . $field_name . '[0][display_description]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $element['description']['description_length'] = [
      '#type' => 'number',
      '#title' => t('Description trim length (in characters)'),
      '#min' => 0,
      '#max' => 1024,
      '#default_value' => isset($item->description_length) ? $item->description_length : 255,
      '#description' => t('For no trimming, leave this field blank (i.e, "0").'),
    ];
    $element['description']['description_plaintext'] = [
      '#type' => 'checkbox',
      '#title' => t('Remove HTML markup from description text.'),
      '#default_value' => isset($item->description_plaintext) ? $item->description_plaintext : 1,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Send nested values to correct level.
    foreach ($values as $delta => $item) {
      $values[$delta]['description_plaintext'] = $item['description']['description_plaintext'];
      $values[$delta]['description_length'] = $item['description']['description_length'];
      $values[$delta]['date_format'] = $item['date']['date_format'];
      $values[$delta]['custom_date_format'] = $item['date']['custom_date_format'];
    }
    return $values;
  }

}
