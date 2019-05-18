<?php

namespace Drupal\revive_adserver\Form;

use Artistan\ReviveXmlRpc\OpenAdsV2ApiXmlRpc;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Configure revive_adserver settings for this site.
 */
class ReviveAdserverSettingsForm extends ConfigFormBase {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revive_adserver_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'revive_adserver.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('revive_adserver.settings');

    $form['delivery_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delivery URL'),
      '#description' => $this->t('For example "<em>ads.example.org/delivery</em>". The protocol does not need to be specified.'),
      '#default_value' => $config->get('delivery_url'),
      '#required' => TRUE,
    ];
    $form['delivery_url_ssl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delivery URL SSL'),
      '#description' => $this->t('For example "<em>ads.example.org/delivery</em>". The protocol does not need to be specified.'),
      '#default_value' => $config->get('delivery_url_ssl'),
    ];

    $form['publisher_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher Id'),
      '#description' => $this->t('Revive Adserver Publisher Id.'),
      '#default_value' => $config->get('publisher_id'),
      '#required' => TRUE,
    ];

    $form['group_zones'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Zone configuration'),
      '#description' => $this->t('Sync the Zone information from the Revive adserver. Your user credentials will not be stored in Drupal.'),
    ];

    $zones = $config->get('zones');

    if (!empty($zones)) {
      $header = [
        $this->t('Zone Id'),
        $this->t('Zone name'),
        $this->t('Width'),
        $this->t('Height'),
      ];
      $form['group_zones']['zone_overview'] = [
        '#type' => 'table',
        '#caption' => $this->t('Available Zone configuration'),
        '#header' => $header,
        '#rows' => $zones,
      ];
    }
    else {
      $form['group_zones']['zone_overview'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Your site does not have any synced adserver zones. You can sync the available zones below.<br> Synced zones will made available as a select list.'),
      ];
    }

    $form['group_zones']['revive_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Revive username'),
    ];
    $form['group_zones']['revive_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Revive password'),
    ];
    $form['group_zones']['zone_sync_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync zones now'),
      '#submit' => ['::syncZones', '::submitForm'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Syncs the Revive adserver zones and store configuration in Drupal.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function syncZones(array &$form, FormStateInterface $form_state) {
    $config = $this->config('revive_adserver.settings');
    $username = $form_state->getValue('revive_username');
    $password = $form_state->getValue('revive_password');
    $publisher_id = $form_state->getValue('publisher_id');
    $delivery_url = $form_state->getValue('delivery_url');
    $delivery_url_ssl = $form_state->getValue('delivery_url_ssl');

    // Build Revive URL and xmlrpc baseurl.
    $url = 'http://' . $delivery_url;
    $ssl = FALSE;

    // A SSL Url has priority and will be use for the sync request.
    if (!empty($delivery_url_ssl)) {
      $url = 'https://' . $delivery_url_ssl;
      $ssl = TRUE;
    }
    $url_components = parse_url($url);

    // Build the xmlrpc path, based on the delivery url.
    $xmlrpc_basepath = substr($url_components['path'], 0, strpos($url_components['path'], '/delivery', 0)) . '/api/v2/xmlrpc/';

    // Don't allow to sync the zones without credentials.
    if (empty($username) || empty($password)) {
      $form_state->setErrorByName('revive_username', $this->t('You need to specify an username.'));
      $form_state->setErrorByName('revive_password', $this->t('You need to specify a password.'));
    }

    // Fetch the zones from the revive adserver API.
    try {
      $rpc = new OpenAdsV2ApiXmlRpc($url_components['host'], $xmlrpc_basepath, $username, $password, 0, $ssl, 15);
      $zoneList = $rpc->getZoneListByPublisherId($publisher_id);
    }
    catch (\Exception $e) {
      // Do nothing. Errors will be shown for "no zones" result.
    }
    // Build the zone configuration.
    if (!empty($zoneList)) {
      $zones = [];
      foreach ($zoneList as $zone) {
        $zones[$zone->zoneId] = [
          'id' => $zone->zoneId,
          'name' => $zone->zoneName,
          'width' => $zone->width,
          'height' => $zone->height,
        ];
      }
      $config->set('zones', $zones);
      $config->save();
      $this->messenger()->addMessage('The zones have been successfully synced.');
    }
    else {
      $this->messenger()
        ->addError('There was a problem while syncing the zones. Either you have not specified any zones or there was a problem during sync. Please check your logs.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('revive_adserver.settings');
    $config->set('delivery_url', $form_state->getValue('delivery_url'));
    $config->set('delivery_url_ssl', $form_state->getValue('delivery_url_ssl'));
    $config->set('publisher_id', $form_state->getValue('publisher_id'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
