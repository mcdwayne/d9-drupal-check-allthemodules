<?php
namespace Drupal\http_client_error_status\Plugin\Condition;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'HTTP 40x client error status code' condition.
 *
 * @Condition(
 *   id = "http_client_error_status",
 *   label = @Translation("HTTP 40x Client error status code")
 * )
 */
class HttpClientErrorStatus extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a RequestPath condition plugin.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(RequestStack $request_stack, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('request_stack'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['request_401' => 0, 'request_403' => 0, 'request_404' => 0] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['request_401'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display on 401 page'),
      '#default_value' => isset($this->configuration['request_401']) ? $this->configuration['request_401'] : 0,
      '#description' => $this->t("Select for the 401 Unauthorised error page."),
    ];
    $form['request_403'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display on 403 page'),
      '#default_value' => isset($this->configuration['request_403']) ? $this->configuration['request_403'] : 0,
      '#description' => $this->t("Select for the 403 Forbidden error page."),
    ];
    $form['request_404'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display on 404 page'),
      '#default_value' => isset($this->configuration['request_404']) ? $this->configuration['request_404'] : 0,
      '#description' => $this->t("Select for the 404 Not Found error page."),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['request_401'] = $form_state->getValue('request_401');
    $this->configuration['request_403'] = $form_state->getValue('request_403');
    $this->configuration['request_404'] = $form_state->getValue('request_404');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {

    $pages = [];
    if ($this->configuration['request_401']) {
      $pages[] = '401';
    }
    if ($this->configuration['request_403']) {
      $pages[] = '403';
    }
    if ($this->configuration['request_404']) {
      $pages[] = '404';
    }

    $pages = implode(', ', $pages);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following client error pages: @pages', ['@pages' => $pages]);
    }
    return $this->t('Return true on the following client error pages: @pages', ['@pages' => $pages]);

  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $request = $this->requestStack->getCurrentRequest();
    $status = $request->attributes->get('exception');

    if ($this->configuration['request_401']) {
      if ($status && $status->getStatusCode() == 401) {
        return TRUE;
      }
    }
    if ($this->configuration['request_403']) {
      if ($status && $status->getStatusCode() == 403) {
        return TRUE;
      }
    }
    if ($this->configuration['request_404']) {
      if ($status && $status->getStatusCode() == 404) {
        return TRUE;
      }
    }
    
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    return $contexts;
  }

}