<?php

namespace Drupal\flipclock\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flipclock\FlipClockManager;

/**
 * Plugin implementation of the 'FlipClock' formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "flipclock_clock",
 *   label = @Translation("FlipClock"),
 *   field_types = {
 *     "datetime"
 *   }
 *)
 */
class DateTimeFlipClockFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'clock_face' => 'HourlyCounter',
        'auto_play' => TRUE,
        'auto_start' => TRUE,
        'countdown' => FALSE,
        'show_seconds' => TRUE,
        'language' => 'en-us'
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->date) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item->date;

        $id = 'flipclock-' . uniqid();

        $instance = [
          'timestamp' => (int) strtotime($date->format("Y-m-d\TH:i:s") . 'Z'),
          'options' => [
            'clockFace' => $this->getSetting('clock_face'),
            'autoPlay' => $this->getSetting('auto_play') ? TRUE : FALSE,
            'autoStart' => $this->getSetting('auto_start') ? TRUE : FALSE,
            'countdown' => $this->getSetting('countdown') ? TRUE : FALSE,
            'showSeconds' => $this->getSetting('show_seconds') ? TRUE : FALSE,
            'language'=> $this->getSetting('language'),
          ],
        ];

        $build = [
          '#theme' => 'flipclock',
          '#id' => $id,
          '#attached' => [
            'library' => ['flipclock/flipclock.load'],
          ],
        ];

        $build['#attached']['drupalSettings']['flipClock']['instances'][$id] = $instance;
        $elements[$delta] = $build;
      }

    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['clock_face'] = [
      '#type' => 'select',
      '#title' => $this->t('Clock'),
      '#options' => [
        'HourlyCounter' => $this->t('Hourly Counter'),
        'MinuteCounter' => $this->t('Minute Counter'),
        'DailyCounter' => $this->t('Daily Counter'),
        'TwelveHourClock' => $this->t('12hr Clock'),
        'TwentyFourHourClock' => $this->t('24hr Clock'),
      ],
      '#description' => $this->t('This is the name of the clock that is used to build the clock display.'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('clock_face'),
    ];

    $form['auto_play'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto play'),
      '#description' => $this->t('Indicate if the clock should automatically add the play class to start the animation.'),
      '#default_value' => $this->getSetting('auto_play'),
    ];

    $form['auto_start'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto start'),
      '#description' => $this->t('Indicate if the clock should start automatically.'),
      '#default_value' => $this->getSetting('auto_start'),
    ];

    $form['countdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Countdown'),
      '#description' => $this->t('Indicate if the clock will count down instead of up.'),
      '#default_value' => $this->getSetting('countdown'),
    ];

    $form['show_seconds'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show seconds'),
      '#description' => $this->t('Indicate if the clock should include a seconds display.'),
      '#default_value' => $this->getSetting('show_seconds'),
    ];

    $form['language'] = [
      '#type' => 'select',
      '#options' => FlipClockManager::getLanguages(),
      '#required' => TRUE,
      '#description' => $this->t('The language to render the flipclock in.'),
      '#default_value' => $this->getSetting('language'),
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $clocks = [
      'HourlyCounter' => $this->t('Hourly Counter'),
      'MinuteCounter' => $this->t('Minute Counter'),
      'DailyCounter' => $this->t('Daily Counter'),
      'TwelveHourClock' => $this->t('12hr Clock'),
      'TwentyFourHourClock' => $this->t('24hr Clock'),
    ];

    $summary[] = $this->t('Clock: @clock', ['@clock' => $clocks[$this->getSetting('clock_face')]]);
    $summary[] = $this->t('Auto play: @auto_play', ['@auto_play' => $this->getSetting('auto_play') ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Auto start: @auto_start', ['@auto_start' => $this->getSetting('auto_start') ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Countdown: @countdown', ['@countdown' => $this->getSetting('countdown') ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Show seconds: @show_seconds', ['@show_seconds' => $this->getSetting('show_seconds') ? $this->t('Yes') : $this->t('No')]);
    $summary[] = $this->t('Language: @language', ['@language' => FlipClockManager::getLanguages()[$this->getSetting('language')]]);

    return $summary;
  }

}
