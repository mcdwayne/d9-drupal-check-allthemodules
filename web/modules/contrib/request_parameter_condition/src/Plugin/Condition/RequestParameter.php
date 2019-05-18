<?php

namespace Drupal\request_parameter_condition\Plugin\Condition;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Request Parameter' condition.
 *
 * @Condition(
 *   id = "request_parameter",
 *   label = @Translation("Request Parameter"),
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
    return [
      'parameter' => '',
      'operation' => '',
      'checked_value' => '',
      'case_sensitive' => 'No',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parameter'),
      '#default_value' => $this->configuration['parameter'],
      '#description' => $this->t("Specify the query parameter."),
      // This should be required but this shows up on the "Configure block"
      // form and wont let it submit otherwise.
      //'#required' => TRUE,
    ];
    $form['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#default_value' => $this->configuration['operation'],
      '#description' => $this->t("Specify the operation to evaluate."),
      // This should be required but this shows up on the "Configure block"
      // form and wont let it submit otherwise.
      //'#required' => TRUE,
      '#options' => [
        'Equals' => 'Equals',
        'Starts With' => 'Starts With',
        'Ends With' => 'Ends With',
        'Contains' => 'Contains',
      ],
    ];
    $form['checked_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $this->configuration['checked_value'],
      '#description' => $this->t("Specify the value to perform the operation."),
    ];
    $form['case_sensitive'] = [
      '#type' => 'select',
      '#title' => $this->t('Case Sensitive'),
      '#default_value' => $this->configuration['case_sensitive'],
      // This should be required but this shows up on the "Configure block"
      // form and wont let it submit otherwise.
      //'#required' => TRUE,
      '#options' => [
        'No' => 'No',
        'Yes' => 'Yes',
      ],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['parameter'] = $form_state->getValue('parameter');
    $this->configuration['operation'] = $form_state->getValue('operation');
    $this->configuration['checked_value'] = $form_state->getValue('checked_value');
    $this->configuration['case_sensitive'] = $form_state->getValue('case_sensitive');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Checks the @param parameter for the "@operation" value of "@check"', [
      '@param' => $this->configuration['parameter'],
      '@operation' => Unicode::strtolower($this->configuration['operation']),
      '@check' => $this->configuration['checked_value'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $checked_value = $this->configuration['checked_value'];
    $request = $this->requestStack->getCurrentRequest();

    // Check if parameter is passed.
    $param_value = $request->query->get($this->configuration['parameter']);
    // If parameter does not exist and we're searching for a value, it fails.
    if (empty($param) and !empty($checked_value)) {
      return FALSE;
    }

    // Check case sensitivity.
    if ($this->configuration['case_sensitive'] == 'No') {
      $param_value = Unicode::strtolower($param_value);
      $checked_value = Unicode::strtolower($checked_value);
    }

    // Check by operation.
    $pass = FALSE;
    switch ($this->configuration['operation']) {
      case 'Equals':
        if ($param_value === $checked_value) {
          $pass = TRUE;
        }
        break;

      case 'Starts With':
        if (substr($param_value, 0, strlen($checked_value)) === $checked_value) {
          $pass = TRUE;
        }
        break;

      case 'Ends With':
        if (substr_compare($param_value, $checked_value, strlen($param_value) - strlen($checked_value), strlen($checked_value)) === 0) {
          $pass = TRUE;
        }
        break;

      case 'Contains':
        if (strpos($param_value, $checked_value) !== FALSE) {
          $pass = TRUE;
        }
        break;
    }
    if ($this->isNegated()) {
      $pass = !$pass;
    }
    return $pass;
  }

}
