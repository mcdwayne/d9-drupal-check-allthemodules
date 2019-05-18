<?php

namespace Drupal\personal_digest;

use Drupal\user\UserDataInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Config\ConfigFactory;
use Drupal\views\Entity\View;


/**
 * Provides a user password reset form.
 */
class SettingsManager {

  /**
   * @var UserDataInterface
   */
  protected $userData;

  /**
   * @var Datetime
   */
  protected $time;

  /**
   * @var QueueInterface
   */
  protected $queue;

  /**
   * @var LoggerChannel
   */
  protected $logger;

  /**
   * @var Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @param UserDataInterface $user_data
   * @param Time $date_time
   * @param QueueFactory $queue
   * @param LoggerChannelFactory $logger
   * @param ConfigFactory $config_factory
   */
  function __construct(UserDataInterface $user_data, Time $date_time, QueueFactory $queue, LoggerChannelFactory $logger, ConfigFactory $config_factory) {
    $this->userData = $user_data;
    $this->time = $date_time;
    $this->queue = $queue->get('personal_digest_mail');
    $this->logger = $logger->get('Personal Digest');
    $this->config = $config_factory->get('personal_digest.settings');
  }

  /**
   * Get one user's settings.
   * @param int $uid
   */
  function forUser($uid) {
    //Retrieve the settings form the user store, with defaults.
    $settings = [
      'daysoftheweek' => (array)$this->config->get('defaultdayoftheweek')
    ];
    if ($uid) {
      $settings += (array) $this->userData->get('personal_digest', $uid, 'digest');
    }
    return $settings += $this->defaultUserSettings();
  }

  /**
   *
   * @return array
   *
   * @note that displays is empty
   */
  function defaultUserSettings() {
    return [
      'displays' => $this->config->get('views'),
      'daysoftheweek' => 1,
      'weeks_interval' => 1,
      'last' => strtotime('-1 week')
     ];
  }

  /**
   * Check all user settings and dispatch mail if needed.
   *
   * This will run once every day after the set hour or once every cron,
   * whichever is less frequent.
   *
   */
  function dispatch() {
    // weeks_interval value of -1 means the user wants the to get digest daily
    $daily = -1;

    $start = -$this->time->getCurrentMicroTime();

    // Find out which users to mail today
    // Check the digest settings for EVERY user.
    $all_users_data = $this->userData
      ->get('personal_digest', NULL, 'digest');
    $num = 0;
    foreach ($all_users_data as $uid => $settings) {
      // Is today a sending day? or does the user want the digest every day?
      if ($dayOfWeek == $settings['daysoftheweek'] || $settings['weeks_interval'] == $daily) {
        $settings['last'] = (isset($settings['last'])) ? $settings['last'] : 0;
        // Compare the last time the cron run with the start of the current day.
        if ($settings['last'] <  strtotime('today')) {
          // Send if the specified number of weeks has passed since the last
          // mail or if the user wants to get the digest on daily basis.
          $time_since = floor(($this->time->getCurrentTime() - $settings['last']) / 604800);
          if (($settings['weeks_interval'] != $daily && $time_since > $settings['weeks_interval'] - 1) || $settings['weeks_interval'] == $daily) {
            $this->queue->createItem($uid);
            $num++;
          }
        }
      }
    }

    // Monitor performance.
    $this->logger->info(
      'Checked @count user settings. Queued @num mails in @seconds seconds.',
      [
        '@count' => count($all_users_data),
        '@num' => $num,
        '@seconds' => $this->time->getCurrentMicroTime() - $start
      ]
    );

  }

  /**
   * Put the element on the user settings form
   * @param array $form
   * @param int $uid
   * @param int $weight
   */
  function userFormElement(&$form, $uid, $weight = 10) {
    if ($views = $this->config->get('views')) {
      $form['personal_digest'] = [
        '#title' => 'Digest settings',
        '#type' => 'details',
        '#open' => FALSE,
        '#weight' => $weight,
        'displays' => [
          '#type' => 'table',
          '#header' => [t('Views display'), t('Weight'), t('Enabled')],
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'weight',
            ],
          ],
        ],
      ];
      $settings = $this->foruser($uid);

      foreach ($views as $display_id) {
        list($view_name, $display_name) = explode(':', $display_id);
        // Skip any views which aren't there.
        $view = View::load($view_name);
        if (!$view) {
          continue;
        }
        $title = $view->getDisplay($display_name)['display_title'];
        $weight = isset($settings['displays'][$display_id]) ? $settings['displays'][$display_id] : 0;
        $form['personal_digest']['displays'][$display_id] = [
          'label' => ['#markup' => $title],
          'weight' => [
            '#title' => t('Weight for @title', ['@title' => $title]),
            '#title_display' => 'invisible',
            '#type' => 'weight',
            '#delta' => max(15, abs($weight)),
            '#default_value' => $weight,
            '#attributes' => ['class' => ['weight']],
          ],
          'enabled' => [
            '#type' => 'checkbox',
            '#default_value' => in_array($display_id, array_keys($settings['displays'])),
            '#attributes' => ['class' => ['weight']],
          ],
          '#weight' => $weight,
          '#attributes' => ['class' => ['draggable']],
        ];
      }
      uasort($form['personal_digest']['displays'], array('\Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));

      $week_days = [];
      // This is a way to get the weekdays translated by php
      // Hat-tip to Carlos http://stackoverflow.com/users/462084/carlos
      // Get next Sunday.
      $day_start = date("d", strtotime("next Sunday"));
      for ($x = 0; $x < 7; $x++) {
        $unixtime = mktime(0, 0, 0, date("m"), $day_start + $x, date("y"));
        // Create weekdays array.
        $week_days[date('l', $unixtime)] = date('l', $unixtime);
      }
      $form['personal_digest']['daysoftheweek'] = [
        '#title' => t('Days of the week'),
        '#type' => 'select',
        '#options' => $week_days,
        '#default_value' => $settings['daysoftheweek'],
        '#weight' => 5,
      ];
      $form['personal_digest']['weeks_interval'] = [
        '#title' => t('Delivery frequency'),
        '#type' => 'select',
        '#options' => [
          -1 => t('Every day'),
          1 => t('Every week'),
          2 => t('Every 2 weeks'),
          4 => t('Every month'),
          8 => t('Every 2 months'),
          13 => t('Every 3 months'),
        ],
        '#default_value' => $settings['weeks_interval'],
        '#weight' => 7,
      ];
      $form['actions']['submit']['#submit'][] = [$this, 'userFormSubmit'];
    }
  }

  /**
   * Form submit handler.
   */
  function userFormSubmit($form, $form_state) {
    $displays = [];
    if (!$form_state->hasValue('displays')) {
      return;
    }
    foreach ($form_state->getValue('displays') as $display_id => $props) {
      if (!$props['enabled']) {
        continue;
      }
      $displays[$display_id] = $props['weight'];
    }

    $this->userData->set(
      'personal_digest',
      $form_state->getFormObject()->getEntity()->id(),
      'digest',
      [
        'displays' => $displays,
        'daysoftheweek' => $form_state->getValue('daysoftheweek'),
        'weeks_interval' => $form_state->getValue('weeks_interval'),
      ]
    );
  }

}
