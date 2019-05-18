<?php

namespace Drupal\commerce_installments\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an installment plan selection pane.
 *
 * @CommerceCheckoutPane(
 *   id = "installment_selection",
 *   label = @Translation("Installment Selection"),
 *   default_step = "installments",
 * )
 */
class InstallmentSelection extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * The installment.
   *
   * @var \Drupal\commerce_installments\Entity\InstallmentInterface
   */
  protected $installmentStorage;

  /**
   * The installment plan.
   *
   * @var \Drupal\commerce_installments\Entity\InstallmentPlanInterface
   */
  protected $installmentPlanStorage;

  /**
   * The installment plan method storage.
   *
   * @var \Drupal\commerce_installments\InstallmentPlanMethodStorageInterface
   */
  protected $installmentPlanMethodStorage;

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface $currencyStorage
   */
  protected $currencyStorage;

  /**
   * The installment plan manager.
   *
   * @var \Drupal\commerce_installments\Plugin\InstallmentPlanMethodManager $installmentPlanManager
   */
  protected $installmentPlanManager;

  /**
 * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface $numberFormatter
   */
  protected $numberFormatter;

  /**
   * Skip this pane if there are no eligible installments plan methods.
   *
   * @var bool $skip
   */
  protected $skip;

  /**
   * Constructs a new CheckoutPaneBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $numberFormatter
   *   The number formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, NumberFormatterFactoryInterface $numberFormatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->installmentStorage = $this->entityTypeManager->getStorage('installment');
    $this->installmentPlanStorage = $this->entityTypeManager->getStorage('installment_plan');
    $this->installmentPlanMethodStorage = $this->entityTypeManager->getStorage('installment_plan_method');
    $this->currencyStorage = $this->entityTypeManager->getStorage('commerce_currency');
    $this->numberFormatter = $numberFormatter->createInstance();

    // If an installment plan isn't eligible, default to standard payment.
    if (!$this->installmentPlanMethodStorage->loadEligible($this->order)) {
      $this->skip = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('commerce_price.number_formatter_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'installment_plan' => 'monthly',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    return $this->t('Installment plan: %plan', ['%plan' => $this->getConfiguration()['installment_plan']]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface[] $plans */
    $plans = $this->installmentPlanMethodStorage->loadMultiple();

    $plans = array_map(function (InstallmentPlanMethodInterface $plan) {
      return $plan->label();
    }, $plans);

    $form['installment_plan'] = [
      '#type' => 'select',
      '#title' => $this->t('Installment plan'),
      '#default_value' => $this->getConfiguration()['installment_plan'],
      '#options' => $plans,
    ];

    if (empty($plans)) {
      $link = Link::createFromRoute($this->t('create'), 'entity.installment_plan_method.add_form');
      $form['installment_plan']['#description'] = $this->t('You must first @create an installment plan method.', ['@create' => $link->toString()]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface */
    if ($plan = $this->installmentPlanMethodStorage->load($this->getConfiguration()['installment_plan'])) {
      $pane_form['number_payments'] = [
        '#type' => 'select',
        '#title' => $this->t('Number of %plan', ['%plan' => $plan->label()]),
        '#options' => [0 => $this->t('None')] + $plan->getPluginConfiguration()['number_payments'],
        '#default_value' => $this->order->getData('commerce_installments_number_payments', 2),
        '#description' => $this->t('This is optional, an installment plan is not required.'),
      ];

      return $pane_form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $numberPayments = $values['number_payments'];

    $this->order->setData('commerce_installments_number_payments', $numberPayments);
  }


  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $summary = [];

    if ($this->skip) {
      return $summary;
    }

    $numberPayments = $this->order->getData('commerce_installments_number_payments', 2);

    $summary['installment_payments'] = [
      '#markup' => $this->t('No installment plan selected.'),
    ];
    // If there aren't any installment payments, don't calculate any.
    if (!empty($numberPayments)) {
      $totalPrice = $this->order->getTotalPrice()->divide($numberPayments);
      $summary['installment_payments'] = [
        '#markup' => $this->t('@number payments:', ['@number' => $numberPayments]),
      ];

      /** @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface */
      if ($plan = $this->installmentPlanMethodStorage->load($this->getConfiguration()['installment_plan'])) {
        $dates = $plan->getPlugin()->getInstallmentDates($numberPayments, $this->order);
        $amounts = $plan->getPlugin()->getInstallmentAmounts($numberPayments, $this->order->getTotalPrice());
        $rows = [];
        foreach ($dates as $delta => $date) {
          $row = [];
          $row[] = $date->format('m-d-Y');
          $row[] = $this->numberFormatter->formatCurrency($amounts[$delta]->getNumber(), $this->currencyStorage->load($amounts[$delta]->getCurrencyCode()));

          $rows[] = $row;
        }
        $summary['installment_table'] = [
          '#type' => 'table',
          '#rows' => $rows,
          '#header' => [
            $this->t('Date'),
            $this->t('Amount'),
          ],
        ];
      }

    }

    return $summary;
  }

}
