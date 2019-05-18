<?php

namespace Drupal\condition_plugins\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Request parameter' condition.
 *
 * @Condition(
 *   id = "condition_plugins_request_parameter",
 *   label = @Translation("Request parameter"),
 * )
 */
class RequestParameter extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a RequestPath condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'parameters' => '',
      'all' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['parameters'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Parameters'),
      '#default_value' => $this->configuration['parameters'],
      '#description' => $this->t("Specify parameters. Enter one path per line."),
    ];
    $form['all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require all parameters'),
      '#default_value' => $this->configuration['all'],
      '#return_value' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['parameters'] = $form_state->getValue('parameters');
    $this->configuration['all'] = boolval($form_state->getValue('all'));

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $parameters = array_map('trim', explode("\n", $this->configuration['parameters']));
    $parameters = implode(', ', $parameters);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true if the current request does not have @mode parameters: @parameters.', [
        '@mode' => $this->configuration['all'] ? $this->t('all') : $this->t('any of the'),
        '@parameters' => $parameters,
      ]);
    }
    return $this->t('Return true if the current request has @mode parameters: @parameters.', [
      '@mode' => $this->configuration['all'] ? $this->t('all') : $this->t('any of the'),
      '@parameters' => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $needed_parameters = array_map('trim', explode("\n", mb_strtolower($this->configuration['parameters'])));
    if (!$needed_parameters) {
      return TRUE;
    }

    // Remove empty parameters.
    $current_parameters = array_filter($this->requestStack->getCurrentRequest()->query->all());

    $result = array_filter(
      $needed_parameters,
      function ($value) use ($current_parameters) {
        return !array_key_exists($value, $current_parameters);
      }
    );

    return $this->configuration['all'] ? empty($result) : $result < $needed_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.query_args';

    return $contexts;
  }

}
