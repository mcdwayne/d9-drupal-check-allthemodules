<?php

namespace Drupal\countdown\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Countdown' Block.
 *
 * @Block(
 *   id = "countdown_block",
 *   admin_label = @Translation("Countdown block"),
 * )
 */
class Countdown extends BlockBase {

  /**
   * Implements a block render.
   */
  public function build() {

    $config = $this->getConfiguration();
    $time = time();
    $event_name = isset($config['event_name']) ? $config['event_name'] : '';
    $url = isset($config['url']) ? $config['url'] : '';

    if ($url != "") {
      if (UrlHelper::isExternal($url)) {
        $event_name = Link::fromTextAndUrl($event_name, Url::fromUri($url, []))->toString();
      }
      else {
        if ($url == "<front>") {
          $url = "/";
        }
        $event_name = Link::fromTextAndUrl($event_name, Url::fromUri('internal:/' . ltrim($url, '/'), []))->toString();
      }
    }
    $accuracy = isset($config['accuracy']) ? $config['accuracy'] : '';
    $countdown_timestamp = isset($config['timestamp']) ? $config['timestamp'] : $time;
    $difference = $countdown_timestamp - $time;
    if ($difference < 0) {
      $passed = 1;
      $difference = abs($difference);
    }
    else {
      $passed = 0;
    }
    if ($passed) {
      $event_name = $this->t(' since @event_name.', ['@event_name' => $event_name]);
    }
    else {
      $event_name = $this->t(' until @event_name.', ['@event_name' => $event_name]);
    }

    $days_left = floor($difference / 60 / 60 / 24);
    $hrs_left = floor(($difference - $days_left * 60 * 60 * 24) / 60 / 60);
    $min_left = floor(($difference - $days_left * 60 * 60 * 24 - $hrs_left * 60 * 60) / 60);
    $secs_left = floor(($difference - $days_left * 60 * 60 * 24 - $hrs_left * 60 * 60 - $min_left * 60));

    $days_left = $this->formatPlural($days_left, '1 day', '@count days');
    if ($accuracy == 'h' || $accuracy == 'm' || $accuracy == 's') {
      $hrs_left = $this->formatPlural($hrs_left, ', 1 hour', ', @count hours');
    }
    if ($accuracy == 'm' || $accuracy == 's') {
      $min_left = $this->formatPlural($min_left, ', 1 minute', ', @count minutes');
    }
    if ($accuracy == 's') {
      $secs_left = $this->formatPlural($secs_left, ', 1 second', ', @count seconds');
    }

    $build = [
      '#theme' => 'countdown',
      '#cache' => ['max-age' => 0],
      '#accuracy' => $accuracy,
      '#countdown_url' => $url,
      '#countdown_event_name' => $event_name,
      '#days_left' => $days_left,
      '#hrs_left' => $hrs_left,
      '#min_left' => $min_left,
      '#secs_left' => $secs_left,
      '#attached' => [
        'library' => 'countdown/countdownblock',
        'drupalSettings' => [
          'countdown' => [
            'countdownblock' => [
              'accuracy' => $accuracy,
            ],
          ],
        ],
      ],
    ];
    return $build;
  }

  /**
   * Implements a block form handler.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $time = time();
    $timestamp = $time;
    $event_name = '';
    $countdown_url = '';
    $countdown_accuracy = '';

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    if (isset($config['event_name'])) {
      $event_name = $config['event_name'];
      $countdown_url = $config['url'];
      $countdown_accuracy = $config['accuracy'];
      $timestamp = $config['timestamp'];
    }
    $form['countdown_event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#default_value' => $event_name,
      '#size' => 30,
      '#maxlength' => 200,
      '#description' => $this->t("Event name you're counting to or from."),
      '#required' => TRUE,
    ];
    $form['countdown_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event URL'),
      '#default_value' => $countdown_url,
      '#size' => 30,
      '#maxlength' => 200,
      '#description' => $this->t('Turn the event description into a link to more
        information about the event.
        Start typing the title of a piece of content to select it.
        You can also enter an internal path such as %add-node or an external URL
        such as %url. Enter %front to link to the front page.',
          [
            '%front' => '<front>',
            '%add-node' => '/node/add',
            '%url' => 'http://example.com',
          ]),
      '#required' => FALSE,
    ];

    $form['countdown_accuracy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Accuracy'),
      '#default_value' => $countdown_accuracy,
      '#options' => [
        'd' => $this->t('days'),
        'h' => $this->t('hours'),
        'm' => $this->t('minutes'),
        's' => $this->t('seconds'),
      ],
      '#description' => $this->t('Select the smallest amount of detail to display. For example, selecting "days" will display only days, selecting "hours" will display the number of days and hours.'),
    ];

    $form['target_time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Target date/time'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => $this->t('Select a date relative to the server time: %s', ['%s' => format_date($time)]),
    ];

    for ($years = [], $i = 1970; $i < 2032; $years[$i] = $i, $i++) {

    }
    $form['target_time']['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#default_value' => (int) date('Y', $timestamp),
      '#options' => $years,
    ];
    unset($years);

    $form['target_time']['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#default_value' => (int) date('n', $timestamp),
      '#options' => [
        1 => $this->t('January'),
        2 => $this->t('February'),
        3 => $this->t('March'),
        4 => $this->t('April'),
        5 => $this->t('May'),
        6 => $this->t('June'),
        7 => $this->t('July'),
        8 => $this->t('August'),
        9 => $this->t('September'),
        10 => $this->t('October'),
        11 => $this->t('November'),
        12 => $this->t('December'),
      ],
    ];

    for ($month_days = [], $i = 1; $i < 32; $month_days[$i] = $i, $i++) {

    }
    $form['target_time']['day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#default_value' => (int) date('j', $timestamp),
      '#options' => $month_days,
    ];
    unset($month_days);

    for ($hrs = [], $i = 0; $i < 24; $hrs[] = $i, $i++) {

    }
    $form['target_time']['hour'] = [
      '#type' => 'select',
      '#title' => $this->t('Hour'),
      '#default_value' => (int) date('G', $timestamp),
      '#options' => $hrs,
    ];
    unset($hrs);

    for ($mins = [], $i = 0; $i < 60; $mins[] = $i, $i++) {

    }
    $form['target_time']['min'] = [
      '#type' => 'select',
      '#title' => $this->t('Minute'),
      '#default_value' => (int) date('i', $timestamp),
      '#options' => $mins,
    ];
    $form['target_time']['sec'] = [
      '#type' => 'select',
      '#title' => $this->t('Seconds'),
      '#default_value' => (int) date('s', $timestamp),
      '#options' => $mins,
    ];

    return $form;
  }

  /**
   * Implements a block submit handler.
   *
   * Save configuration into system.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('event_name', $form_state->getValue('countdown_event_name'));
    $this->setConfigurationValue('url', $form_state->getValue('countdown_url'));
    $this->setConfigurationValue('accuracy', $form_state->getValue('countdown_accuracy'));
    $timestamp = $form_state->getValue('target_time');

    $countdown_timestamp = mktime(
        (int) $timestamp['hour'], (int) $timestamp['min'], (int) $timestamp['sec'], (int) $timestamp['month'], (int) $timestamp['day'], (int) $timestamp['year']);

    $this->setConfigurationValue('timestamp', $countdown_timestamp);
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $url = $form_state->getValue('countdown_url');
    if ($url != "" || $url == "<front>") {
      if (!UrlHelper::isExternal($url) && !(strpos($url, '/') === 0)) {
        $form_state->setErrorByName('countdown_url', $this->t('Event URL is not vaild. Entered paths should start with /'));
      }
    }
  }

}
