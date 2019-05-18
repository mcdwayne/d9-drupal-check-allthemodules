<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlan;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\braintree_cashier\BraintreeCashierService;

/**
 * Class PlanSelectFormBase.
 */
class PlanSelectFormBase extends FormBase {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The billing plan entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $billingPlanStorage;

  /**
   * The braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * The Braintree Cashier logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The discount entity storate.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $discountStorage;

  /**
   * The money parser.
   *
   * @var \Money\MoneyParser
   */
  protected $moneyParser;

  /**
   * The international money formatter.
   *
   * @var \Money\Formatter\IntlMoneyFormatter
   */
  protected $moneyFormatter;

  /**
   * The braintree cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PlanSelectFormBase object.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entityTypeManager, BraintreeApiService $braintreeApi, LoggerChannelInterface $logger, BraintreeCashierService $braintreeCashierService) {
    $this->requestStack = $request_stack;
    $this->billingPlanStorage = $entityTypeManager->getStorage('braintree_cashier_billing_plan');
    $this->braintreeApi = $braintreeApi;
    $this->logger = $logger;
    $this->discountStorage = $entityTypeManager->getStorage('braintree_cashier_discount');
    $this->bcService = $braintreeCashierService;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('braintree_api.braintree_api'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_cashier.braintree_cashier_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'plan_select_form_base';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the plan ID from the query parameter.
    $plan_id = $this->requestStack->getCurrentRequest()->query->get('plan_id');
    if (empty($plan_id)) {
      // Since the plan ID wasn't found in the URL, try to get it from the
      // session.
      $plan_id = $this->requestStack->getCurrentRequest()
        ->getSession()
        ->get('plan_id');
    }
    if (!empty($plan_id) && is_numeric($plan_id)) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan */
      $billing_plan = $this->billingPlanStorage->load($plan_id);
      if (!empty($billing_plan) && $billing_plan->isAvailableForPurchase()) {
        $default_value = $billing_plan->id();
      }
    }
    $form['plan_entity_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a plan'),
      '#attributes' => [
        'id' => 'edit-plan-entity-id',
      ],
      '#options' => $this->getBillingPlanOptions(),
      '#multiple' => FALSE,
      '#default_value' => !empty($default_value) ? $default_value : NULL,
      '#attached' => [
        'library' => [
          'braintree_cashier/plan_select',
        ],
      ],
    ];

    if ($this->config('braintree_cashier.settings')->get('enable_coupon_field')) {
      $form['coupon_container'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Coupon'),
        '#attributes' => [
          'class' => [
            'container-inline',
            'fieldgroup',
            'form-composite',
          ],
        ],
      ];

      $form['coupon_container']['coupon_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Coupon code'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'id' => 'coupon-code',
          'placeholder' => $this->t('Coupon code'),
        ],
      ];

      $form['coupon_container']['confirm_coupon_code'] = [
        '#type' => 'button',
        '#name' => 'confirm_coupon',
        '#value' => $this->t('Confirm coupon'),
        '#ajax' => [
          'callback' => '::confirmCouponCode',
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ],
        ],
        '#submit' => [[$this, 'confirmCouponSubmit']],
        '#limit_validation_errors' => [],
        '#suffix' => '<div id="coupon-result"></div>',
      ];
    }

    $entity_type_definition = $this->entityTypeManager->getDefinition('braintree_cashier_billing_plan');
    $form['#cache']['tags'] = $entity_type_definition->getListCacheTags();

    return $form;
  }

  /**
   * Determine the billing plan options for the drop-down select list.
   *
   * @return array
   *   Return an array keyed by the billing plan entity id with a value
   *   that is the billing plan description.
   */
  public function getBillingPlanOptions() {
    $query = $this->billingPlanStorage->getQuery();
    $query->condition('is_available_for_purchase', TRUE)
      ->condition('environment', $this->braintreeApi->getEnvironment())
      ->sort('weight');
    $entity_ids = $query->execute();
    $options = [];
    foreach ($entity_ids as $entity_id) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlan $billing_plan */
      $billing_plan = $this->billingPlanStorage->load($entity_id);
      $options[$billing_plan->id()] = $billing_plan->getDescription();
    }
    return $options;
  }

  /**
   * Submit handler for AJAX coupon confirmation button.
   *
   * Rebuilds the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function confirmCouponSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * AJAX callback to validate the coupon code.
   */
  public function confirmCouponCode(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $coupon_code = $form_state->getValue('coupon_code');
    $plan_entity_id = $form_state->getValue('plan_entity_id');
    $billing_plan = BraintreeCashierBillingPlan::load($plan_entity_id);

    if (empty($billing_plan) || !$this->bcService->discountExists($billing_plan, $coupon_code)) {
      $message = [
        '#prefix' => '<div class="coupon-result coupon-result--error">',
        '#markup' => $this->t('The coupon code %coupon_code is invalid.', [
          '%coupon_code' => $coupon_code,
        ]),
        '#suffix' => '</div>',
      ];
      // Remove the coupon code from the text field.
      $response->addCommand(new InvokeCommand('#coupon-code', 'val', ['']));
    }
    else {
      $braintree_discount = $this->bcService->getBraintreeDiscount($coupon_code);
      // Setup Money.
      $currencies = new ISOCurrencies();
      $moneyParser = new DecimalMoneyParser($currencies);
      $numberFormatter = new \NumberFormatter($this->bcService->getLocale(), \NumberFormatter::CURRENCY);
      $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

      $amount = $moneyParser->parse($braintree_discount->amount, $this->config('braintree_cashier.settings')
        ->get('currency_code'));
      $formatted_amount = $moneyFormatter->format($amount);

      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan */
      $billing_plan = $this->billingPlanStorage->load($plan_entity_id);
      $braintree_plan = $this->bcService->getBraintreeBillingPlan($billing_plan->getBraintreePlanId());
      switch ($braintree_plan->billingFrequency) {
        case 1:
          $interval = 'each month';
          break;

        case 12:
          $interval = 'each year';
          break;

        default:
          $interval = 'every ' . $braintree_plan->billingFrequency . ' months';
      }
      $duration = '';
      if (!empty($braintree_discount->numberOfBillingCycles)) {
        $duration = 'for ' . $braintree_discount->numberOfBillingCycles . ' months';
      }
      $message = [
        '#prefix' => '<div class="coupon-result coupon-result--success">',
        '#markup' => $this->t('Success! The coupon code %code gives a discount of %discount_amount @interval @duration', [
          '@interval' => $interval,
          '%code' => $coupon_code,
          '%discount_amount' => $formatted_amount,
          '@duration' => $duration,
        ]),
        '#suffix' => '</div>',
      ];
      $response->addCommand(new InvokeCommand('#coupon-code', 'removeClass', ['error']));
    }

    $response->addCommand(new HtmlCommand('#coupon-result', $message));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Validate that the billing plan can be loaded.
    $values = $form_state->getValues();
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan */
    $billing_plan = $this->billingPlanStorage->load($values['plan_entity_id']);
    if ($form_state->getTriggeringElement()['#name'] != 'confirm_coupon') {
      if (empty($billing_plan) || !$billing_plan->isAvailableForPurchase()) {
        $message = $this->t('The plan selected, %plan, is invalid. Please choose a different plan', [
          '%plan' => $values['plan_entity_id'],
        ]);
        $form_state->setErrorByName('plan_entity_id', $message);
        $this->logger->error($message);
      }
      if (!empty($values['coupon_code']) && !empty($billing_plan) && !$this->bcService->discountExists($billing_plan, $values['coupon_code'])) {
        $message = $this->t('The coupon code %coupon_code is invalid.', [
          '%coupon_code' => $values['coupon_code'],
        ]);
        $form_state->setErrorByName('coupon_code', $message);
        $this->logger->error($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
