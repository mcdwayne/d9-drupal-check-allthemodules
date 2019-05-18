<?php

namespace Drupal\cors_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure the behavior of the core cors middleware.
 */
class CorsConfigurationForm extends ConfigFormBase {

  /**
   * A new-line character.
   */
  const NEW_LINE = "\n";

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('cors_ui.configuration');
    $form['configuration'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $form['configuration']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CORS'),
      '#default_value' => $config->get('enabled'),
    ];
    $form['configuration']['maxAge'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Age'),
      '#default_value' => $config->get('maxAge'),
      '#description' => $this->t('Access-Control-Max-Age gives the value in seconds for how long the response to the preflight request can be cached for without sending another preflight request.  In this case, 86400 seconds is 24 hours.  Note that each browser has a maximum internal value that takes precedence when the Access-Control-Max-Age is greater.'),
    ];
    $form['configuration']['supportsCredentials'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Supports Credentials'),
      '#default_value' => $config->get('supportsCredentials'),
      '#description' => $this->t('When making cross-site cross-site XMLHttpRequest invocations, browsers will not send credentials. A specific flag has to be set on the XMLHttpRequest object when it is invoked. When this flag is set, the browser will reject any response that does not have the Access-Control-Allow-Credentials: true header, and not make the response available to the invoking web content.'),
    ];
    $form['configuration']['allowedHeaders'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed Headers'),
      '#default_value' => $config->get('allowedHeaders'),
      '#value_callback' => [static::class, 'textareaNewLinesToArray'],
      '#description' => $this->t('Used in response to a preflight request to indicate which HTTP headers can be used when making the actual request. One value per line, or you may use "*" to allow all.'),
    ];
    $form['configuration']['allowedMethods'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed Methods'),
      '#value_callback' => [static::class, 'textareaNewLinesToArray'],
      '#default_value' => $config->get('allowedMethods'),
      '#description' => $this->t('Specifies the method or methods allowed when accessing the resource.  This is used in response to a preflight request. One value per line, or you may use "*" to allow all.'),
    ];
    $form['configuration']['allowedOrigins'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed Origins'),
      '#value_callback' => [static::class, 'textareaNewLinesToArray'],
      '#default_value' => $config->get('allowedOrigins'),
      '#description' => $this->t('Specifies a URI that may access the resource. For requests without credentials, the server may specify "*" as a wildcard, thereby allowing any origin to access the resource. One value per line, or you may use "*" to allow all.'),
    ];
    $form['configuration']['exposedHeaders'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exposed Headers'),
      '#value_callback' => [static::class, 'textareaNewLinesToArray'],
      '#default_value' => $config->get('exposedHeaders'),
      '#description' => $this->t('This header lets a server whitelist headers that browsers are allowed to access. One value per line, or you may use "*" to allow all.'),
    ];

    $form['more_info'] = [
      '#markup' => $this->t('For more information about CORS, visit the <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS">MDN CORS documentation</a>.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cors_ui.configuration', 'cors_ui.status'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cors_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('cors_ui.configuration')
      ->setData($form_state->getValue('configuration'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * An element value callback to convert between newlines and arrays.
   */
  public static function textareaNewLinesToArray($element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      return !empty($input) ? explode(static::NEW_LINE, static::normalizeLineEndings(trim($input))) : [];
    }
    if (!empty($element['#default_value']) && is_array($element['#default_value'])) {
      return implode(static::NEW_LINE, $element['#default_value']);
    }
    return [];
  }

  /**
   * Normalize line endings.
   *
   * @param string $input
   *   The input string.
   *
   * @return string
   *   A normalized string.
   */
  protected static function normalizeLineEndings($input) {
    return str_replace(array("\r\n", "\r"), static::NEW_LINE, $input);
  }

}
