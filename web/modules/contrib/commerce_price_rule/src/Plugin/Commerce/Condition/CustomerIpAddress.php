<?php

namespace Drupal\commerce_price_rule\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a condition that checks for a user field value.
 *
 * @CommerceCondition(
 *   id = "price_rule_customer_ip_address",
 *   label = @Translation("IP address"),
 *   display_label = @Translation("Limit by the IP address of the customer"),
 *   category = @Translation("Customer"),
 *   entity_type = "user",
 * )
 */
class CustomerIpAddress extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * Indicates that the condition is an IP address whitelisting filter.
   */
  const TYPE_WHITELIST = 0;

  /**
   * Indicates that the condition is an IP address blacklisting filter.
   */
  const TYPE_BLACKLIST = 1;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new CustomerField object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
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
      'type' => self::TYPE_WHITELIST,
      'whitelist' => '',
      'blacklist' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#description' => $this->t('When creating a whitelist, the price rule will apply only for listed IP addresses. When creating a blacklist, the price rule will apply to all IP addresses apart from the listed.'),
      '#default_value' => $this->configuration['type'],
      '#options' => [
        self::TYPE_WHITELIST => $this->t('Whitelist'),
        self::TYPE_BLACKLIST => $this->t('Blacklist'),
      ],
    ];

    $selector = 'conditions[form][customer][price_rule_customer_ip_address][configuration][form][type]';
    $form['whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Whitelist'),
      '#description' => $this->t('Enter the IP addresses you would like to whitelist, one per line.'),
      '#default_value' => $this->arrayToText($this->configuration['whitelist']),
      '#states' => [
        'visible' => [
          ":input[name=\"$selector\"]" => ['value' => self::TYPE_WHITELIST],
        ],
      ],
    ];

    $form['blacklist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blacklist'),
      '#description' => $this->t('Enter the IP addresses you would like to blacklist, one per line.'),
      '#default_value' => $this->arrayToText($this->configuration['blacklist']),
      '#states' => [
        'visible' => [
          ":input[name=\"$selector\"]" => ['value' => self::TYPE_BLACKLIST],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['type'] = $values['type'];
    $this->configuration['whitelist'] = $this->textToArray($values['whitelist']);
    $this->configuration['blacklist'] = $this->textToArray($values['blacklist']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $user) {
    $this->assertEntity($user);

    $ip_address = $this->request->getClientIp();
    switch ($this->configuration['type']) {
      case self::TYPE_WHITELIST:
        $pass = in_array($ip_address, $this->configuration['whitelist']);
        break;

      case self::TYPE_BLACKLIST:
        $pass = !in_array($ip_address, $this->configuration['blacklist']);
        break;
    }

    return $pass;
  }

  /**
   * Converts a line-break separated textual list of values to an array.
   *
   * @param string $list
   *   The textual list to convert.
   *
   * @return array
   *   The array of values.
   */
  protected function textToArray($list) {
    return array_map(
      function ($item) { return trim($item); },
      explode("\n", $list)
    );
  }

  /**
   * Converts an array of values to a line-break separated textual list.
   *
   * @param array $list
   *   The array of values to convert.
   *
   * @return array
   *   The textual list.
   */
  protected function arrayToText($list) {
    return implode("\n", $list);
  }

}
