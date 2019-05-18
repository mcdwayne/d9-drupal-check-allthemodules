<?php

/**
 * @file
 * Contains \Drupal\cronpub\Plugin\Field\FieldWidget\CronpubWidgetType.
 */

namespace Drupal\cronpub\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\cronpub\Plugin\Cronpub\CronpubActionManager;



/**
 * Plugin implementation of the 'cronpub_rrule_js_widget' widget.
 *
 * @FieldWidget(
 *   id = "cronpub_rrule_js_widget",
 *   label = @Translation("ICal rrule.js widget"),
 *   field_types = {
 *     "cronpub_field_type"
 *   }
 * )
 */
class CronpubRruleJsWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'rrule_option' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * @var \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  private $plugin_manager;

  /**
   * Get the plugin manager for Cronpub plugins.
   * @return \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager
   */
  public function getPluginManager() {
    if (!$this->plugin_manager instanceof CronpubActionManager) {
      $this->plugin_manager = \Drupal::service('plugin.manager.cronpub');
    }
    return $this->plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['rrule_option'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Offer advanced options.'),
      '#default_value' => $this->getSetting('rrule_option'),
      '#required' => FALSE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Advanced options: %stand', [
      '%stand' => ($this->getSetting('rrule_option')) ? 'enabled ' : 'disabled',
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getFieldSettings();
    $plugin = $this->getPluginManager()->getDefinition($settings['plugin']);

    $element = [];
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

    if ($cardinality == 1) {
      $element['fieldset'] = [
        '#markup' => '<div class="cronpub-fieldset">',
      ];
      $element['legend'] = [
        '#markup' => '<div class="legend"><b>'. $items->getFieldDefinition()->getLabel() .'</b></div>',
      ];
    }

    if ($items->getEntity()->id() && $delta === 0) {
      $params = [
        'entity_type' => $items->getEntity()->getEntityTypeId(),
        'entity_id' => $items->getEntity()->id(),
        'field_name' => $items->getName(),
      ];
      $element['link'] = [
        '#title' => $this->t('Saved cron tasks from this field'),
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.cronpub_entity.taskfieldoverview', $params),
        '#prefix' => '<div class="current-cp-tasks">',
        '#suffix' => '</div>'
      ];
    }

    $disabled = (!\Drupal::currentUser()->hasPermission('edit cronpub task entities'));

    $element['wrapper_1'] = [
      '#type' => 'markup',
      '#markup' => '<div class="clearfix"><div class="sub-field-cronpub start">',
    ];

    $date_format = 'Y-m-d';
    $time_format = 'H:i';

    $default = ($items[$delta]->start)
      ? new DrupalDateTime($items[$delta]->start)
      : NULL;
    $element['start'] = [
      '#type' => 'datetime',
      '#disabled' => $disabled,
      '#title' => (string) $plugin['start']['label'],
      '#description' => (string) $plugin['start']['description'],
      '#default_value' => $default,
      '#date_date_element' => 'date',
      '#date_date_format' => $date_format,
      '#date_time_element' => 'time',
      '#date_time_format' => $time_format,
      '#date_increment' => 60,
      '#attached' => [
        'library' => [
          "cronpub/cronpub_subform_widget",
          "cronpub/cronpub_rrulejs_widget",
        ],
      ],
    ];

    $element['wrapper_2'] = [
      '#type' => 'markup',
      '#markup' => '</div><div class="sub-field-cronpub end">',
    ];

    $default = ($items[$delta]->end)
      ? new DrupalDateTime($items[$delta]->end)
      : NULL;
    $element['end'] = [
      '#type' => 'datetime',
      '#disabled' => $disabled,
      '#title' => (string) $plugin['end']['label'],
      '#description' => (string) $plugin['end']['description'],
      '#default_value' => $default,
      '#date_date_element' => 'date',
      '#date_date_format' => $date_format,
      '#date_time_element' => 'time',
      '#date_time_format' => $time_format,
      '#date_increment' => 60,
    ];

    $element['wrapper_3'] = [
      '#type' => 'markup',
      '#markup' => '</div></div>',
    ];

    if ($this->getSetting('rrule_option')) {

      $default = ($items[$delta]->rrule)
        ? $items[$delta]->rrule
        : '';
      $params = [
        '%example' => "'FREQ=WEEKLY;BYDAY=MO,WE;COUNT=10'",
        'links' => "&nbsp;(<a href=\"https://tools.ietf.org/html/rfc5545#section-3.3.10\" target=\"_blank\">RFC 5455</a>,
          &nbsp;<a href=\"http://www.kanzaki.com/docs/ical/rrule.html\" target=\"_blank\">"
          . $this->t('More examples') ."</a>)",
      ];
      $element['rrule'] = [
        '#type' => 'textfield',
        '#disabled' => $disabled,
        '#title' => $this->t('Recursion rule'),
        '#description' =>
          $this->t('Insert a valid recursion rule for this event like the following example: %example. ', $params)
          . $params['links'],
        '#default_value' => $default,
        '#prefix' => '<div class="rrule-js wrapper">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => [
            'ical-rrule',
          ],
        ],
        '#attached' => [
          'library' => [
            'cronpub/ical_event_field'
          ],
        ],
      ];
    }

    if ($cardinality == 1) {
      $element['fieldset_end'] = [
        '#markup' => '</div>',
      ];
    }
    return $element;
  }

}
