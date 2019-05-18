<?php

namespace Drupal\block_ipaddress\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Cache\Cache;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Provides the 'Ip Address condition' condition.
 *
 * @Condition(
 *   id = "ipaddress",
 *   label = @Translation("IP Address"),
 * )
 */
class BlockIpaddress extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a new IpAddress instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(RequestStack $request_stack, array $configuration, $plugin_id, $plugin_definition) {
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
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['ipaddress'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('IP Address'),
      '#default_value' => !empty($this->configuration['ipaddress']) && isset($this->configuration['ipaddress']) ? $this->configuration['ipaddress'] : '',
      '#description' => $this->t('Enter one IP Address per line. Enter IP Address ranges in CIDR Notation<br /> Example: %ipAddress <br />%start_ipAddress<br />%mid_start_ipAddress<br />10.10.10.10/8<br />For more information on CIDR notation click <a href="http://www.brassy.net/2007/mar/cidr_basic_subnetting">here</a>.', [
        '%ipAddress' => '172.120.30.0',
        '%start_ipAddress' => '172.120.30.0/24',
        '%mid_start_ipAddress' => '192.168.0.1/32',
        '%mid_end_ipAddress' => '10.10.10.10/8',
      ]),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['ipaddress'] = $form_state->getValue('ipaddress');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Provides a human readable summary of the condition's configuration.
   */
  public function summary() {
    $ipaddress = array_map('trim', explode("\n", $this->configuration['ipaddress']));
    $ipaddress = implode(', ', $ipaddress);
    if (!empty($this->configuration['ipaddress'])) {
      return $this->t('Do not return true on the following ipaddress: @ipaddress', ['@ipaddress' => $ipaddress]);
    }
    return $this->t('Return true on the following ipaddress: @ipaddress', ['@ipaddress' => $ipaddress]);
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    if (empty($this->configuration['ipaddress']) && $this->configuration['negate'] == FALSE) {
      return TRUE;
    }
    else {
      $should_dispaly = FALSE;
      $config_ipaddress = array_map('trim', explode("\n", $this->configuration['ipaddress']));
      $client_ipaddress = $this->requestStack->getCurrentRequest()->getClientIp();
      try {
        if (IpUtils::checkIp($client_ipaddress, $config_ipaddress)) {
          // IP found to be from Configuration.
          $should_dispaly = TRUE;
        }
        return $should_dispaly;
      }
      catch (\Exception $e) {
        \Drupal::logger("Block IP Address error")->error($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['ip:ipaddress']);
  }

}
