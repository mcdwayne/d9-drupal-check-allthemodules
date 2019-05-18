<?php

namespace Drupal\memory_limit_policy_query_param\Plugin\MemoryLimitConstraint;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\memory_limit_policy\MemoryLimitConstraintBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure the memory limit based on path.
 *
 * @MemoryLimitConstraint(
 *   id = "query_param",
 *   title = @Translation("Query Param"),
 *   description = @Translation("Provide a list of query params for which the memory limit must be overridden.")
 * )
 */
class QueryParam extends MemoryLimitConstraintBase implements ContainerFactoryPluginInterface {

  /**
   * Request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * QueryParam constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['query_param'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Query Param'),
      '#description' => $this->t('Enter one parameter key per line.'),
      '#default_value' => $this->getConfiguration()['query_param'] ?? '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['query_param'] = $form_state->getValue('query_param');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $params = explode(PHP_EOL, $this->configuration['query_param']);
    array_walk($params, function (&$param) {
      $param = rtrim(trim(trim($param, "\r"), '/'), '/');
    });

    $params = implode(', ', $params);

    return $this->t('Query Params: @params', ['@params' => $params]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $request_params = array_keys($this->requestStack->getCurrentRequest()->query->all());
    $params = explode(PHP_EOL, $this->configuration['query_param']);

    if (empty($request_params) || empty($params)) {
      return parent::evaluate();
    }

    return (count(array_intersect($request_params, $params)) > 0);
  }

}
