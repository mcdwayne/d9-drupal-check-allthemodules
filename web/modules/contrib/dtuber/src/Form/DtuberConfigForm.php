<?php

namespace Drupal\dtuber\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form for Dtuber.
 */
class DtuberConfigForm extends ConfigFormBase {

  protected $dtuberYtService;

  /**
   * {@inheritdoc}
   */
  public function __construct($dtuberYoutube) {
    $this->dtuberYtService = $dtuberYoutube;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('dtuber_youtube_service'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dtuber_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dtuber.settings'];
  }

  /**
   * Check is item empty.
   */
  public function isEmpty($item) {
    return ($item === NULL || $item === '');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    // Get config.
    $config = $this->config('dtuber.settings');
    $form['authentication'] = array(
      '#type' => 'details',
      '#title' => $this->t('Google Authentication'),
      '#description' => $this->t('DTuber requires Google Account authentication to upload videos to YouTube.'),
      '#open' => TRUE,
    );
    $form['credentials'] = array(
      '#type' => 'details',
      '#title' => $this->t('Credentials'),
      '#open' => TRUE,
    );
    $hasAccessToken = $config->get('access_token');
    if ($hasAccessToken) {
      $authorized = $this->t('<p>Status: <strong>Authorized</strong>.</p><p><a class="button" href=":url">Revoke Current Authentication</a></a>', [':url' => $base_url . '/dtuber/revoke']);
      $form['authentication']['dtuber_access_token'] = array(
        '#type' => 'markup',
        '#markup' => $authorized,
      );

      // Channel Details.
      $form['channel'] = array(
        '#type' => 'details',
        '#title' => $this->t('YouTube Details'),
        '#open' => TRUE,
      );

      $channelSettings = $this->dtuberYtService->youTubeAccount();

      $details = $this->t('<p><strong>Channel Name:</strong> :value</p>', [':value' => $channelSettings->title]);
      $details .= $this->t('<p><strong>Channel Description:</strong> :value</p>', [':value' => $channelSettings->description]);
      $details .= $this->t('<p><strong>Channel Keywords:</strong> :value</p>', [':value' => $channelSettings->keywords]);
      $form['channel']['details'] = array(
        '#type' => 'markup',
        '#markup' => $details,
      );

      $form['authentication']['#open'] = FALSE;
      $form['credentials']['#open'] = FALSE;

    }
    else {
      $hasClientIds = $config->get('client_id');
      $hasClientSecret = $config->get('client_secret');
      $hasRedirectUri = $config->get('redirect_uri');

      if (!$this->isEmpty($hasClientIds) && !$this->isEmpty($hasClientSecret) && !$this->isEmpty($hasRedirectUri)) {
        $auth_url = $this->dtuberYtService->getAuthUrl();
        $unauthorized = $this->t('<p>Status: <strong>Unauthorized</strong>.</p><p><a class="button" href=":url">Authorize</a></p>', [':url' => $auth_url]);
        $form['authentication']['authorize'] = array(
          '#type' => 'markup',
          '#markup' => $unauthorized,
        );
      }
      else {
        $status = $this->t('<p>Status: <strong>Credentials required</strong>.</p><p>Provide values for Client ID, Secret and Redirect Uri</p>');
        $form['authentication']['authorize'] = array(
          '#type' => 'markup',
          '#markup' => $status,
        );
      }
    }

    $form['credentials']['dtuber_client_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Set Client Id'),
      '#disabled' => $hasAccessToken,
    );

    $form['credentials']['dtuber_client_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Set Client Secret'),
      '#disabled' => $hasAccessToken,
    );

    $redirect_uri = $base_url . '/dtuber/authorize';
    $form['credentials']['dtuber_redirect_uri'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Redirect uri'),
      '#default_value' => ($config->get('redirect_uri')) ? $config->get('redirect_uri') : $redirect_uri,
      '#description' => $this->t("Redirect uri should be set to '%redirect_uri'", array('%redirect_uri' => $redirect_uri)),
      '#disabled' => $hasAccessToken,
    );

    $form['dtuber_allowed_exts'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed Extensions'),
      '#default_value' => $config->get('allowed_exts'),
      '#description' => $this->t('Provide allowed extensions separated by a space. Eg: "mov mp4 avi mkv 3gp".'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('dtuber.settings')
      ->set('client_id', $values['dtuber_client_id'])
      ->set('client_secret', $values['dtuber_client_secret'])
      ->set('redirect_uri', $values['dtuber_redirect_uri'])
      ->set('allowed_exts', $values['dtuber_allowed_exts'])
      ->save();
  }

}
