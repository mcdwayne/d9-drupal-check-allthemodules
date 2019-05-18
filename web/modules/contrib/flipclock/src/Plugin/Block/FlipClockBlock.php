<?php

namespace Drupal\flipclock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flipclock\FlipClockManager;

/**
 * @Block(
 *   id = "flipclock_block",
 *   admin_label = @Translation("Clock"),
 * )
 */
class FlipClockBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $date = new DrupalDateTime($config['date']);
    $date->setTimezone(new \DateTimeZone(drupal_get_user_timezone()));
    $form['date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Date'),
      '#date_increment' => 1,
      '#date_timezone' => drupal_get_user_timezone(),
      '#description' => $this->t('The date you want to initialize the clock with.'),
      '#required' => TRUE,
      '#default_value' => isset($config['date']) ? $date : NULL,
    ];

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
      '#default_value' => $config['clock_face'],
    ];

    $form['auto_play'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto play'),
      '#description' => $this->t('Indicate if the clock should automatically add the play class to start the animation.'),
      '#default_value' => isset($config['auto_play']) ? $config['auto_play'] : TRUE,
    ];

    $form['auto_start'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto start'),
      '#description' => $this->t('Indicate if the clock should start automatically.'),
      '#default_value' => isset($config['auto_start']) ? $config['auto_start'] : TRUE,
    ];

    $form['countdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Countdown'),
      '#description' => $this->t('Indicate if the clock will count down instead of up.'),
      '#default_value' => $config['countdown'],
    ];

    $form['show_seconds'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show seconds'),
      '#description' => $this->t('Indicate if the clock should include a seconds display.'),
      '#default_value' => isset($config['show_seconds']) ? $config['show_seconds'] : TRUE,
    ];

    $form['language'] = [
      '#type' => 'select',
      '#options' => FlipClockManager::getLanguages(),
      '#required' => TRUE,
      '#description' => $this->t('The language to render the flipclock in.'),
      '#default_value' => isset($config['language']) ? $config['language'] : 'en-us',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $date = $form_state->getValue('date');
    if($date instanceof  DrupalDateTime){
      $date = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }

    $this->setConfigurationValue('date', $date);
    $this->setConfigurationValue('clock_face', $form_state->getValue('clock_face'));
    $this->setConfigurationValue('auto_play', $form_state->getValue('auto_play'));
    $this->setConfigurationValue('auto_start', $form_state->getValue('auto_start'));
    $this->setConfigurationValue('countdown', $form_state->getValue('countdown'));
    $this->setConfigurationValue('show_seconds', $form_state->getValue('show_seconds'));
    $this->setConfigurationValue('language', $form_state->getValue('language'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $id = 'flipclock-' . uniqid();

    $instance = [
      'timestamp' => (int) strtotime($this->configuration['date']),
      'options' => [
        'clockFace' => $this->configuration['clock_face'],
        'autoPlay' => ($this->configuration['auto_play']) ? TRUE : FALSE,
        'autoStart' => ($this->configuration['auto_start']) ? TRUE : FALSE,
        'countdown' => ($this->configuration['countdown']) ? TRUE : FALSE,
        'showSeconds' => ($this->configuration['show_seconds']) ? TRUE : FALSE,
        'language'=> ($this->configuration['language']) ? $this->configuration['language'] : 'en-us',
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

    return $build;
  }


}