<?php

/**
 * @file
 * Contains \Drupal\cronpub\Plugin\Field\FieldWidget\CronpubWidgetType.
 */

namespace Drupal\cronpub\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\cronpub\Plugin\Cronpub\CronpubActionManager;
use Drupal\cronpub\Plugin\Field\FieldType\CronpubFieldType;



/**
 * Plugin implementation of the 'cronpub_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "cronpub_subform_widget",
 *   label = @Translation("Cronpub default widget"),
 *   field_types = {
 *     "cronpub_field_type"
 *   }
 * )
 */
class CronpubSubformWidget extends WidgetBase {

  /**
   * Limit the max count of event recursions.
   */
  const MAX_COUNT = 250;

  /**
   * Sequence start date.
   *
   * @var \DateTime
   */
  private $start;

  /**
   * Sequence end date.
   *
   * @var \DateTime
   */
  private $end;

  /**
   * The iCallibrary.
   *
   * @var object
   *    The library generating the ICAL items.
   */
  protected $ical;

  private $freq;
  private $interval;
  private $weekdays;
  private $endson;
  private $endsondate;
  private $endsoncount;

  /**
   * The Rules given to the computing object.
   *
   * @var array
   *   The collection of parameters.
   */
  protected $rruleArray = [];
  protected $rrule = '';

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

  private $wrapperId = 0;

  /**
   * Box header for a separated form.
   *
   * @param string $title
   *   Title of the box.
   * @param string $description
   *   Description of the box.
   * @param bool|TRUE $open
   *   If the box is expanded or not by default.
   *
   * @return string
   *   Formatted html-element.
   */
  private function htmlWrapperOpen($title, $description, $open = TRUE) {
    $this->wrapperId = $this->wrapperId + 1;
    $places = ['%id', '%open', '%openst', '%name', '%desc'];
    $variables = [
      (string) $this->wrapperId,
      ($open) ? 'true' : 'false',
      ($open) ? ' open' : '',
      (string) $title,
      (!empty($description)) ? "<p>$description</p>" : '',
    ];
    return str_replace($places, $variables, '<details data-drupal-selector="edit-cronpub-%id-rrule" id="edit-cronpub-%id-rrule" class="js-form-wrapper form-wrapper" %openst ><summary role="button" aria-controls="edit-cronpub-%id-rrule" aria-expanded="%open" aria-pressed="%open">%name</summary><div class="details-wrapper">%desc');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getFieldSettings();
    $plugin = $this->getPluginManager()->getDefinition($settings['plugin']);

    $options = $this->divorceRruleOptions($items[$delta]);

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
        '#suffix' => '</div>',
      ];
    }

    $element['wrapper_1'] = [
      '#type' => 'markup',
      '#markup' => '<div class="clearfix"><div class="sub-field-cronpub start">',
    ];

    $disabled = (!\Drupal::currentUser()->hasPermission('edit cronpub task entities'));

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

      $default = ($options)
        ? 1
        : 0;
      $element['ical'] = [
        '#type' => 'checkbox',
        '#disabled' => $disabled,
        '#title' => $this->t('Recurrence'),
        '#default_value' => $default,
        '#prefix' => $this->htmlWrapperOpen($this->t('Advanced options'), '', $default != 0),
      ];


      $element['wrapper_4'] = [
        '#type' => 'markup',
        '#markup' => '<div class="clearfix"><div class="sub-field-cronpub start">',
      ];

      $default = (isset($options['FREQ']) && in_array($options['FREQ'], ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY']))
        ? $options['FREQ']
        : 'WEEKLY';
      $element['freq'] = [
        '#type' => 'select',
        '#disabled' => $disabled,
        '#title' => $this->t('Frequence'),
        '#description' => $this->t('Frequency of recurrence of article publication.'),
        '#default_value' => $default,
        '#options' => [
          'DAILY' => $this->t('Daily'),
          'WEEKLY' => $this->t('Weekly'),
          'MONTHLY' => $this->t('Monthly'),
          'YEARLY' => $this->t('Yearly'),
        ],
      ];

      $element['wrapper_5'] = [
        '#type' => 'markup',
        '#markup' => '</div><div class="sub-field-cronpub start">',
      ];

      $interval_options = [];
      for ($i = 1; $i <= 30; $i++) {
        $interval_options[(string) $i] = (string) $i;
      }
      $default = (isset($options['INTERVAL']) && in_array($options['INTERVAL'], $interval_options))
        ? $options['INTERVAL']
        : '1';
      $element['interval'] = [
        '#type' => 'select',
        '#disabled' => $disabled,
        '#title' => $this->t('Interval'),
        '#description' => $this->t('Recurrence interval. Ex.: Weekly 3 = publication takes place every 3 weeks on the selected day/period.'),
        '#default_value' => $default,
        '#options' => $interval_options,
      ];

      $element['wrapper_6'] = [
        '#type' => 'markup',
        '#markup' => '</div></div>',
      ];

      $element['weekly_options'] = [
        '#type' => 'details',
        '#title' => $this->t('Weekly options'),
        '#description' => $this->t('Select the weekday(s) the event recurs.'),
        '#tree' => TRUE,
      ];

      $default = (isset($options['BYDAY']))
        ? $this->prepareBydayOpts($options['BYDAY'])
        : [];
      $element['weekly_options']['weekdays'] = [
        '#type' => 'checkboxes',
        '#disabled' => $disabled,
        '#title' => $this->t('Weekdays'),
        '#description' => $this->t('For example: "MO, WE, FR" or "SA, SU"'),
        '#default_value' => $default,
        '#multiple' => TRUE,
        '#inline' => TRUE,
        '#serialized' => TRUE,
        '#options' => [
          'MO' => $this->t('Mo'),
          'TU' => $this->t('Tu'),
          'WE' => $this->t('We'),
          'TH' => $this->t('Th'),
          'FR' => $this->t('Fr'),
          'SA' => $this->t('Sa'),
          'SU' => $this->t('Su'),
        ],
      ];

      $default = (isset($options['UNTIL']))
        ? 'ONUNTIL'
        : 'ONCOUNT';
      $element['endson'] = [
        '#type' => 'select',
        '#disabled' => $disabled,
        '#title' => $this->t('Ending mode'),
        '#default_value' => $default,
        '#multiple' => FALSE,
        '#options' => [
          'ONCOUNT' => $this->t('Ends after a fixed number of dates'),
          'ONUNTIL' => $this->t('Ends on a date.'),
        ],
      ];


      $element['wrapper_7'] = [
        '#type' => 'markup',
        '#markup' => '<div class="clearfix"><div class="sub-field-cronpub start">',
      ];

      if (isset($options['UNTIL'])) {
        $start = new \DateTime($items[$delta]->start);
        $tz = $start->getTimezone();
        $default = new DrupalDateTime(rtrim($options['UNTIL'],"Z"), $tz);
      }
      else {
        $default = NULL;
      }
      $element['endsondate'] = [
        '#type' => 'datetime',
        '#disabled' => $disabled,
        '#title' => $this->t('Un-/Publish series ends on'),
        '#description' => $this->t('Date and time, after the recurrence is to be terminated.'),
        '#default_value' => $default,
        '#date_date_element' => 'date',
        '#date_date_format' => $date_format,
        '#date_time_element' => 'time',
        '#date_time_format' => $time_format,
        '#date_increment' => 60,
      ];


      $element['wrapper_8'] = [
        '#type' => 'markup',
        '#markup' => '</div><div class="sub-field-cronpub start">',
      ];

      $default = (isset($options['COUNT']) && (int) $options['COUNT'])
        ? $options['COUNT']
        : '10';
      $element['endsoncount'] = [
        '#type' => 'textfield',
        '#disabled' => $disabled,
        '#title' => $this->t('Number of dates'),
        '#description' => $this->t('Sets after how many repetitions the recurrence is to be terminated.'),
        '#default_value' => $default,
        '#maxlength' => '3',
        '#size' => '5',
      ];
    }

    $element['wrapper_9'] = [
      '#type' => 'markup',
      '#markup' => '</div></div></div></details>',
    ];

    if ($cardinality == 1) {
      $element['fieldset_end'] = [
        '#markup' => '</div>',
      ];
    }
    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $value) {
      $this->ical = ((int) $value['ical'] == 1);
      if ($value['ical']) {
        $this->freq = "FREQ=".(in_array($value['freq'], ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY']) ? $value['freq'] : 'WEEKLY');
        $this->setInterval((int) $value['interval']);
        $this->setWeekdays($value['weekly_options']['weekdays'], $value['start']);
        $this->endson = $value['endson'];
        if (($value['endson'] == 'ONUNTIL') && $value['endsondate']) {
          $this->setEndsondate($value['endsondate']);
        }
        elseif (($value['endson'] == 'ONCOUNT') && $value['endsoncount']) {
          $this->setEndsoncount($value['endsoncount']);
        }
        else {
          $this->setEndsoncount(static::MAX_COUNT);
        }
        $this->createRule($value);
      }

      $new_val = [
        'start' => $value['start'],
        'end' => $value['end'],
        'rrule' => $this->rrule,
      ];

      $new_values[] = $new_val;
    }

    return $new_values;
  }

  /**
   * Set parameters for the rrule form.
   * FREQ=WEEKLY;UNTIL=20181231T235900Z;WKST=SU;BYDAY=TU,TH,SA
   *
   * @param \Drupal\cronpub\Plugin\Field\FieldType\CronpubFieldType $item
   *   A field item value array.
   *
   * @return array
   */
  public function divorceRruleOptions(CronpubFieldType $item) {
    $options = [];
    $rrule_string = $item->get('rrule')->getString();
    if ($rrule_string) {
      $params = explode(';', $rrule_string);
      foreach ($params as $param) {
        $interrim = explode('=', $param);
        if (count($interrim) >= 2) {
          $options[$interrim[0]] = $interrim[1];
        }
      }
    }
    return $options;
  }

  /**
   * Set the weekdays.
   *
   * @param array $raw
   *   The field value.
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start value.
   */
  public function setWeekdays(array $raw, DrupalDateTime $start = NULL) {
    $collected = [];
    $days = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    foreach ($raw as $key => $val) {
      if ($val && in_array($key, $days)) {
        $collected[] = $key;
      }
    }
    if (!count($collected) && $start) {
      $collected[] = $days[$start->format('w')];
    }
    $this->weekdays = 'BYDAY=' . implode(',', $collected);
  }

  private function prepareBydayOpts($weekdays) {
    $interim = explode(',', $weekdays);
    $days = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
    $return = [];
    foreach ($days as $day) {
      if (in_array($day, $interim)) {
        $return[$day] = $day;
      }
      else {
        $return[$day] = 0;
      }
    }
    return $return;
  }

  /**
   * Set the interval.
   *
   * @param int $raw
   *   The field value.
   */
  public function setInterval($raw) {
    $this->interval = ((int) $raw >= 2)
      ? "INTERVAL=" . (int) $raw
      : FALSE;
  }

  /**
   * Set the count parameter.
   *
   * @param int $raw
   *   The field value.
   */
  public function setEndsoncount($raw) {
    $this->endsoncount = (static::MAX_COUNT >= (int) $raw && (int) $raw >= 1)
      ? "COUNT=" . $raw
      : FALSE;
  }
  /**
   * Set the UNTIL parameter.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The field value.
   */
  public function setEndsondate(DrupalDateTime $date) {
    $formatted = $date->format('Ymd\THis\Z');
    $this->endsondate = 'UNTIL=' . $formatted;
  }

  /**
   * Create RRULE array and string from class data.
   *
   * @param array $value
   */
  public function createRule(array $value) {
    if ($this->ical) {
      $this->rruleArray = [];
      $this->rruleArray[] = $this->freq;
      $this->rruleArray[] = $this->interval;
      if ($this->endson == 'ONCOUNT' && $this->endsoncount) {
        $this->rruleArray[] = $this->endsoncount;
      }
      if ($this->endson == 'ONUNTIL' && $this->endsondate) {
        $this->rruleArray[] = $this->endsondate;
      }
      switch ($this->freq) {
        case 'FREQ=WEEKLY':
          $this->rruleArray[] = 'WKST=SU';
          $this->rruleArray[] = $this->weekdays;
          break;

        case 'FREQ=MONTHLY':
          $this->rruleArray[] = 'BYMONTHDAY=' . $value['start']->format('d');
          break;

        case 'FREQ=YEARLY':
          $this->rruleArray[] = 'BYMONTH=' . $value['start']->format('m');
          $this->rruleArray[] = 'BYMONTHDAY=' . $value['start']->format('d');
          break;

        default:
      }
    }
    $this->rrule = implode(';', $this->rruleArray);
  }

}
