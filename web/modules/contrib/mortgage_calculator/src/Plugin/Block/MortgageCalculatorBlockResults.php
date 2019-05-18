<?php

namespace Drupal\mortgage_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Example: uppercase this please' block.
 *
 * @Block(
 *  id = "mortgage_calculator_block_results",
 *  admin_label = @Translation("Mortgage Calculator Results")
 * )
 */
class MortgageCalculatorBlockResults extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new BookNavigationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
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

    $request = $this->requestStack->getCurrentRequest();
    $session = $request->getSession();

    $loan_amount = $session->get('mortgage_calculator_loan_amount', '');
    $mortgage_rate = $session->get('mortgage_calculator_mortgage_rate', '');
    $years_to_pay = $session->get('mortgage_calculator_years_to_pay', '');
    $desired_display = $session->get('mortgage_calculator_desired_display', '');

    $output = [
      '#theme' => 'mortgage_calculator',
      '#help' => 'Help topics',
      '#loan_amount' => $loan_amount ? $loan_amount : '30000',
      '#mortgage_rate' => $mortgage_rate ? $mortgage_rate : '3',
      '#years_to_pay' => $years_to_pay ? $years_to_pay : 30,
      '#desired_display' => $desired_display,
    ];

    return $output;
  }

}
