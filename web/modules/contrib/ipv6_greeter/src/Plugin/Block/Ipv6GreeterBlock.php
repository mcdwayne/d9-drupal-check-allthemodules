<?php

namespace Drupal\ipv6_greeter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the 'IPv6 Greeter' block.
 *
 * @Block(
 *   id = "ipv6_greeter_block",
 *   admin_label = @Translation("IPv6 Greeter"),
 * )
 */
class Ipv6GreeterBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The reqeust stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates an Ipv6GreeterBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack) {
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
  public function build() {
    $config = $this->getConfiguration();

    $client_ip = $this->requestStack->getCurrentRequest()->getClientIp();
    $is_client_ipv6 = filter_var($client_ip, FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV6]);
    $greet_ipv4 = isset($config['ipv6_greeter_greetIPv4']) ? $config['ipv6_greeter_greetIPv4'] : TRUE;

    return [
      '#theme' => 'ipv6_greeter_block',
      '#is_client_ipv6' => $is_client_ipv6,
      '#client_ip' => $client_ip,
      '#greet_ipv4' => $greet_ipv4,

      '#attached' => [
        'library' => [
          'ipv6_greeter/base',
        ],
      ],

      // Invalidate the cache when the client ip changes.
      '#cache' => [
        'contexts' => [
          'ip',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['ipv6_greeter_greetIPv4'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Greet IPv4 too'),
      '#description'   => t('Greet also IPv4 clients, showing them info about IPv6.'),
      '#default_value' => isset($config['ipv6_greeter_greetIPv4']) ? $config['ipv6_greeter_greetIPv4'] : TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('ipv6_greeter_greetIPv4', $form_state->getValue('ipv6_greeter_greetIPv4'));
  }

}
