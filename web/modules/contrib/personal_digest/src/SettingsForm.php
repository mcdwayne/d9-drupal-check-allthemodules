<?php

namespace Drupal\personal_digest;

use Drupal\Core\Link;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Drupal\user\Entity\User;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Provides a user password reset form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var MailManagerInterface
   */
  protected $mailManager;

  /**
   * @param ConfigFactoryInterface $config_factory
   * @param ModuleHandlerInterface $module_handler
   * @param AccountProxyInterface $current_user
   * @param MailManagerInterface $mail_manager
   */
  function __construct($config_factory, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user, MailManagerInterface $mail_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personal_digest_site_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $checkboxes = $options = [];
    foreach (Views::getViewsAsOptions(FALSE, 'enabled') as $key => &$view_display) {
      list($view_id, $display_id) = explode(':', $key);
      $view = View::load($view_id)->getExecutable();
      $view->setDisplay($display_id);
      if ($this->digestible($view)) {
        $options[$key] = $view_display;
        if ($this->moduleHandler->moduleExists('views_ui')) {
          $options[$key] .= ' '. Link::createFromRoute(
            t('Edit'),
            'entity.view.edit_display_form',
            ['view' => $view_id, 'display_id' => $display_id],
            ['attributes' => ['target' => 'edit digest view']]
          )->toString();
        }
      }
    }

    $settings = $this->config('personal_digest.settings');
    $hours = [];
    foreach (range(0, 23) as $number) {
      $hours[$number] = $number . ":00";
    }

    if ($options) {
      $form['views'] = [
        '#title' => $this->t('Available views displays.'),
        '#description' => $this->t("Views displays with a first contextual filter '@arg'.", ['@arg' => 'date_fulldate_since']),
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => $settings->get('views'),
      ] + $checkboxes;

      $form['include_change_settings_link'] = [
        '#title' => $this->t("Include 'change settings' link in the email."),
        '#description' => $this->t('Include a link in the mails to let the users changes their mail preferences.'),
        '#type' => 'checkbox',
        '#default_value' => $settings->get('include_change_settings_link')
      ];

      $form['hour'] = [
        '#title' => 'Hour',
        '#description' => 'Select the hour to automatically send the Digest. (The mails will be send in the first cron after this hour)',
        '#type' => 'select',
        '#options' => $hours,
        '#default_value' => $settings->get('hour'),
      ];

      $week_days = [];
      $day_start = date("d", strtotime("next Sunday"));
      for ($x = 0; $x < 7; $x++) {
        $unixtime = mktime(0, 0, 0, date("m"), $day_start + $x, date("y"));
        // Create weekdays array.
        $week_days[date('l', $unixtime)] = date('l', $unixtime);
      }

      $form['defaultdayoftheweek'] = [
        '#title' => 'Default Day of the week',
        '#description' => 'Define the default day when the digest will be send',
        '#type' => 'select',
        '#options' => $week_days,
        '#default_value' => $settings->get('defaultdayoftheweek'),
      ];

      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
        ],
        'test' => [
          '#type' => 'submit',
          '#value' => $this->t('Submit & test'),
          '#submit' => [[$this, 'test']]
        ],
      ];
    }
    else {
      $this->messenger($this->t("There are no views yet with with a '@name' argument", ['@name' => 'date_fulldate_since']));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('personal_digest.settings')
      ->set('views', array_values(array_filter($values['views'])))
      ->set('include_change_settings_link', $values['include_change_settings_link'])
      ->set('hour', $values['hour'])
      ->set('defaultdayoftheweek', $values['defaultdayoftheweek'])
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Submit callback
   */
  public function test(array &$form, FormStateInterface $form_state) {
    $this->submitForm($form, $form_state);
    $recipient = User::load($this->currentUser->id());
    $dummy_settings = [
      'displays' => array_flip($this->config('personal_digest.settings')->get('views'))
    ] + \Drupal::service('personal_digest.settings_manager')->defaultUserSettings();
    $sent_message = $this->mailManager->mail('personal_digest',
        'digest',
        $recipient->getEmail(),
        $recipient->getPreferredLangcode(),
        [
          'user' => $recipient,
          'since' => $dummy_settings['last'],
          'settings' => $dummy_settings
        ]
      );
    if ($sent_message['result']) {
      $this->messenger($this->t('Check your mail'));
    }
    else {
      debug($sent_message['body'], $sent_message['subject']);
    }
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['personal_digest.settings'];
  }

  /**
   * Check if a views display can be used for digest.
   *
   * A view is suitable if it has the tag 'digest' and an argument based on a time stamp, e.g. created, updated
   *
   * @param string $view
   * @return bool | NULL
   *   TRUE if the display's first arg is date_fulldate_since
   */
  private function digestible($view) {
    if ($args = $view->getHandlers('argument')) {
      $arg = reset($args);
      if ($arg['plugin_id'] == 'date_fulldate_since') {
        return $arg['field'];
      }
    }
  }


}
