<?php

namespace Drupal\thumbor_effects\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides the Thumbor Effects settings form.
 *
 * @package Drupal\thumbor_effects\Form
 */
class ThumborEffectsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'thumbor_effects_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['thumbor_effects.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('thumbor_effects.settings');

    $form['thumbor_effects']['server'] = [
      '#type' => 'url',
      '#title' => $this->t('Thumbor server URL'),
      '#default_value' => $config->get('server'),
      '#description' => $this->t('The URL of the Thumbor server.'),
      '#required' => TRUE,
    ];

    $form['thumbor_effects']['serve_via_thumbor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Serve images via Thumbor directly on the client side'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('serve_via_thumbor'),
      '#description' => $this->t("Serve the images directly from Thumbor instead of Drupal when requestes from the client side. This has an impact on your caching strategy. Drupal will still create it's own derivative in order to retrieve the actual dimensions."),
    ];

    $form['thumbor_effects']['server_client_side_requests'] = [
      '#type' => 'url',
      '#title' => $this->t('Advanced: Thumbor server URL (client side)'),
      '#default_value' => $config->get('server_client_side_requests'),
      '#description' => $this->t("In certain hosting scenario's client side requests and server side requests require a different URL. Leave empty when in doubt."),
      '#states' => [
        'visible' => [
          ':input[name="serve_via_thumbor"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['thumbor_effects']['unsafe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use unsafe URL's"),
      '#return_value' => TRUE,
      '#description' => $this->t('<a href=":url" target="_blank">Read more</a> about the impact of this setting.', [':url' => 'https://github.com/thumbor/thumbor/wiki/security']),
      '#default_value' => $config->get('unsafe') ?? TRUE,

    ];

    $form['thumbor_effects']['security_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security key'),
      '#default_value' => $config->get('security_key'),
      '#states' => [
        'visible' => [
          ':input[name="unsafe"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="unsafe"]' => ['checked' => FALSE],
        ],
      ],
      '#description' => $this->t('The security key of Thumbor which encrypts all calls to prevent DDOS attacks.'),
    ];

    $form['thumbor_effects']['base_url_overwrite'] = [
      '#type' => 'url',
      '#title' => $this->t('Drupal public files base URL overwrite'),
      '#default_value' => $config->get('base_url_overwrite'),
      '#description' => $this->t('Thumbor needs a public accessible Drupal URL to retrieve the image. For example, if this site uses Basic Authentication the credentials can be prepended to the URL.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('thumbor_effects.settings');
    $config->delete();

    foreach (Element::children($form['thumbor_effects']) as $key) {
      $config->set($key, $form_state->getValue($form['thumbor_effects'][$key]['#parents']));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
