<?php

namespace Drupal\bridtv\Form;

use Drupal\bridtv\BridInfoNegotiator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class to build the Brid.TV settings form.
 */
class BridSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The negotiator service.
   *
   * @var \Drupal\bridtv\BridInfoNegotiator
   */
  protected $bridNegotiator;

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setBridNegotiator($container->get('bridtv.negotiator'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bridtv_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bridtv.settings'];
  }

  /**
   * Set the negotiator service.
   *
   * @param \Drupal\bridtv\BridInfoNegotiator $negotiator
   *   The negotiator service.
   */
  public function setBridNegotiator(BridInfoNegotiator $negotiator) {
    $this->bridNegotiator = $negotiator;
  }

  /**
   * Get the editable configuration, holding the Brid.TV settings.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\Config
   */
  protected function getEditableConfig() {
    return $this->config('bridtv.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getEditableConfig()->getRawData();
    $properly_setup = !empty($settings['partner_id']) && !empty($settings['access_token']);
    if (!$properly_setup) {
      $this->messenger()->addMessage($this->t('You need credentials for an API authorization. See the README section "Installation and configuration" of this module regards how to obtain the credentials.'), 'warning');
    }
    else {
      if ($this->currentUser()->hasPermission('sync bridtv')) {
        $this->messenger()->addMessage($this->t('The integration is properly set up. <a href="/admin/bridtv/sync" target="_blank">Synchronize video data</a>'));
      }
      else {
        $this->messenger()->addMessage($this->t('The integration is properly set up.'));
      }
    }

    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API credentials'),
    ];
    $form['api']['partner_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('API partner id'),
      '#default_value' => !empty($settings['partner_id']) ? $settings['partner_id'] : '',
    ];
    $form['api']['access_token'] = [
      '#type' => 'password',
      '#title' => $this->t('API access token'),
      '#default_value' => !empty($settings['access_token']) ? $settings['access_token'] : '',
    ];

    if ($properly_setup) {
      $form['player'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Player'),
      ];
      if ($players = $this->bridNegotiator->getPlayersListOptions()) {
        $form['player']['default_player'] = [
          '#type' => 'select',
          '#title' => $this->t('Default player'),
          '#description' => $this->t('Choose the player to use as default, when not specified otherwise.'),
          '#options' => $players,
          '#default_value' => $this->bridNegotiator->getDefaultPlayerId(),
        ];
      }
      else {
        $form['player']['default_player'] = [
          '#markup' => $this->t('No players found to choose from. Create some players at your Brid.TV CMS.'),
        ];
      }

      $form['sync'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Synchronization'),
      ];
      $form['sync']['cron_sync'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable video data synchronization during Cron runs.'),
        '#default_value' => !empty($settings['cron_sync']),
      ];
      $form['sync']['sync_autocreate'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('During synchronization, automatically create media entities for new videos.'),
        '#default_value' => !empty($settings['sync_autocreate']),
      ];
      $form['sync']['sync_autodelete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('During synchronization, automatically delete media entities for deleted videos.'),
        '#default_value' => !empty($settings['sync_autodelete']),
      ];
    }

    return $form + parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->getEditableConfig();
    $form_values = $form_state->getValues();

    if (!empty($form_values['partner_id'])) {
      $partner_id = trim($form_values['partner_id']);
      if (!empty($partner_id)) {
        $config->set('partner_id', $partner_id);
      }
    }
    if (!empty($form_values['access_token'])) {
      $access_token = trim($form_values['access_token']);
      if (!empty($access_token)) {
        $config->set('access_token', $access_token);
      }
    }
    $config->set('cron_sync', !empty($form_values['cron_sync']));
    $config->set('sync_autocreate', !empty($form_values['sync_autocreate']));
    $config->set('sync_autodelete', !empty($form_values['sync_autodelete']));
    if (!empty($form_values['default_player'])) {
      $players = $this->bridNegotiator->getPlayersListOptions();
      if (!empty($players) && isset($players[$form_values['default_player']])) {
        $config->set('default_player', $form_values['default_player']);
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
