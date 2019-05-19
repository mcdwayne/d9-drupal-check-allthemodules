<?php

namespace Drupal\tfa_duo\Plugin\TfaSetup;

use Drupal\Component\Utility\HTML;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaSetupInterface;
use Drupal\user\UserDataInterface;
use Duo\Web;

/**
 * Duo setup class to setup Duo validation.
 *
 * @TfaSetup(
 *   id = "tfa_duo_setup",
 *   label = @Translation("Tfa Duo Setup"),
 *   description = @Translation("Tfa Duo Setup Plugin"),
 *   helpLinks = {
 *    "Duo Mobile (Android)" = "https://guide.duo.com/android",
 *    "Duo Mobile (iPhone)" = "https://guide.duo.com/iphone",
 *    "Duo Mobile (Apple Watch)" = "https://guide.duo.com/apple-watch",
 *    "Duo Mobile (Windows phone)" = "https://guide.duo.com/windows-phone",
 *    "Duo Mobile (Blackberry)" = "https://guide.duo.com/blackberry",
 *    "Duo guide and other devices" = "https://guide.duo.com/"
 *   },
 *   setupMessages = {
 *    "saved" = @Translation("Tfa Duo setup verified."),
 *    "skipped" = @Translation("Tfa Duo not enabled.")
 *   }
 * )
 */
class TfaDuoSetup extends TfaBasePlugin implements TfaSetupInterface {
  use MessengerTrait;

  /**
   * Object containing the external validation library.
   *
   * @var \Duo\Web
   */
  protected $duo;

  /**
   * This plugin's settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The KeyRepository.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);

    $this->duo = new Web();
    $plugin_settings = \Drupal::config('tfa.settings')->get('validation_plugin_settings');
    $this->settings = !empty($plugin_settings['tfa_duo']) ? $plugin_settings['tfa_duo'] : [];
    $this->keyRepository = \Drupal::service('key.repository');
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state) {
    // @TODO Let drupal know that you have setup 2fa with duo somehow.
    $key = $this->keyRepository->getKey($this->settings['duo_key'])->getKeyValues();
    $sign_request = $this->duo->signRequest($key['duo_integration'], $key['duo_secret'], $key['duo_application'], \Drupal::currentUser()->getDisplayName());
    if ($this->responseErrors($sign_request)) {
      return $form;
    }

    $form['duo'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe id="duo_iframe" data-host="{{ host }}" data-sig-request="{{ sign_request }}"></iframe>',
      '#context' => [
        'host' => $key['duo_apihostname'],
        'sign_request' => $sign_request,
      ],
    ];

    $form['#attached'] = [
      'library' => [
        'tfa_duo/duo-sign',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelpLinks() {
    return ($this->pluginDefinition['helpLinks']) ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupMessages() {
    return ($this->pluginDefinition['setupMessages']) ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview($params) {
    $help_links = $this->getHelpLinks();
    $items = [];
    foreach ($help_links as $item => $link) {
      $items[] = Link::fromTextAndUrl($item, Url::fromUri($link, ['attributes' => ['target' => '_blank']]));
    }
    $markup = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => t('Help links:'),
    ];

    $form = [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => t('Duo 2nd-Factor authentication'),
      ],
      'help_links' => [
        '#type' => 'item_list',
        '#markup' => \Drupal::service('renderer')->render($markup),
      ],
      'link' => [
        '#theme' => 'links',
        '#links' => [
          'admin' => [
            'title' => !$params['enabled'] ? t('Set up application') : t('Reset application'),
            'url' => Url::fromRoute('tfa.validation.setup', [
              'user' => $params['account']->id(),
              'method' => $params['plugin_id'],
            ]),
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Helper function to deal with errors from Duo API calls.
   *
   * @param string $response
   *   The response string from Duo.
   * @param bool $verbose
   *   Whether or not to log the error message.
   *
   * @return bool
   *   Whether or not there were errors.
   */
  public function responseErrors($response, $verbose = TRUE) {
    list($response_code, $message) = explode('|', $response);
    $has_errors = ($response_code == 'ERR');
    if ($has_errors && $verbose) {
      $this->messenger()->addError(HTML::escape($message));
    }
    return $has_errors;
  }

}
