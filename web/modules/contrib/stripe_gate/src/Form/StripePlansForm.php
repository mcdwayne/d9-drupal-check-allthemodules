<?php

namespace Drupal\stripe\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\stripe\StripeService;

/**
 * StripePlansForm class.
 */
class StripePlansForm extends ConfigFormBase {

  /**
   * Drupal\stripe\StripeService definition.
   *
   * @var StripeService $stripeService
   */
  protected $stripeService;

  /**
   * {@inheritdoc}
   */
  public function __construct(StripeService $stripeService) {
    $this->stripeService = $stripeService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stripe.stripe_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stripe_plans_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    $form['stripe_create_plan'] = array(
      '#type' => 'details',
      '#title' => t('Create new plan'),
      '#open' => TRUE,
    );

    $form['stripe_create_plan']['nickname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plan nickname'),
      '#description' => $this->t('This won\'t be visible to customers, but will help you find this plan later.'),
    ];

    $products = array_reduce($this->stripeService->getProducts(), function ($result, $product) {
        $result[$product['id']] = $product['name'];
        return $result;
    }, array());

    $form['stripe_create_plan']['product'] = [
      '#type' => 'select',
      '#title' => t('Product'),
      '#options' => $products,
      '#required' => TRUE,
    ];

    $form['stripe_create_plan']['currency'] = [
      '#type' => 'select',
      '#title' => t('Currency'),
      '#options' => [
        'usd' => t('US Dollars'),
        'eur' => t('Euros'),
      ],
      '#required' => TRUE,
    ];

    $form['stripe_create_plan']['interval'] = [
      '#type' => 'select',
      '#title' => t('Interval'),
      '#options' => [
        'day' => t('Daily'),
        'week' => t('Weekly'),
        'month' => t('Monthly'),
        'year' => t('Yearly'),
      ],
      '#required' => TRUE,
    ];

    $form['stripe_create_plan']['price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price of plan'),
      '#placeholder' => t('$'),
      '#required' => TRUE,
    ];

    $form['stripe_create_plan']['trial_days'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trial days'),
      '#description' => $this->t('If nothing is sent, the default is 0.'),
    ];

    // Header of the plans table.
    $header = [
      'plan_id' => t('Plan ID'),
      'plan_nickname' => t('Plan nickname'),
      'plan_price' => t('Price'),
      'plan_interval' => t('Interval'),
      'product' => t('Product'),
      'trial_days' => t('Trial days'),
    ];

    // Variable to hold the plans.
    $plans = [];

    foreach ($this->stripeService->getPlans() as $plan) {
      $plans[$plan->id] = [
        'plan_id' => $plan->id,
        'plan_nickname' => $plan->nickname,
        'plan_price' => '$' . ($plan->amount / 100),
        'plan_interval' => $plan->interval,
        'product' => $plan->product,
        'trial_days' => !$plan->trial_period_days ? '0' : (string) $plan->trial_period_days,
      ];
    }

    $form['stripe_plans'] = array(
      '#type' => 'details',
      '#title' => t('List of plans'),
      '#open' => TRUE,
    );

    $form['stripe_plans']['plans'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $plans,
      '#empty' => t('No plans found'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->stripeService->createPlan(
      $form_state->getValue('nickname'),
      $form_state->getValue('product'),
      $form_state->getValue('currency'),
      $form_state->getValue('interval'),
      $form_state->getValue('price'),
      $form_state->getValue('trial_days')
    );

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This function returns the name of the settings files we will
    // create / use.
    return [
      'stripe.settings',
    ];
  }

}
