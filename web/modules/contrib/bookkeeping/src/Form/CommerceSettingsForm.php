<?php

namespace Drupal\bookkeeping\Form;

use Drupal\bookkeeping\Entity\AccountInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface as UserAccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Bookkeeping settings for this site.
 */
class CommerceSettingsForm extends ConfigFormBase {

  use AccountSettingsTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construct the commerce settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Check whether this form is accessible.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account requesting access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(UserAccountInterface $account) {
    return AccessResult::allowedIf($this->moduleHandler->moduleExists('commerce'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bookkeeping_commerce_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bookkeeping.commerce'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bookkeeping.commerce');
    $form['stores'] = $this->buildStoresForm($form_state, $config);
    $form['payment_gateways'] = $this->buildPaymentGatewaysForm($form_state, $config);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Build the stores configuration form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Config\Config $config
   *   The commerce config.
   *
   * @return array
   *   The stores configuration subform.
   */
  protected function buildStoresForm(FormStateInterface $form_state, Config $config): array {
    $form = [
      '#type' => 'fieldset',
      '#title' => $this->t('Stores'),
      '#tree' => TRUE,
    ];

    if (!$this->moduleHandler->moduleExists('commerce_store')) {
      $form['#description'] = $this->t('Please enable Commerce Store to configure store settings.');
      $form['#weight'] = 99;
      return $form;
    }

    $form['#description'] = $this->t('To configure the available stores please @link.', [
      '@link' => Link::createFromRoute($this->t('click here'), 'entity.commerce_store.collection')->toString(),
    ]);

    /** @var \Drupal\commerce_store\Entity\StoreInterface[] $gateways */
    $stores = $this->entityTypeManager->getStorage('commerce_store')->loadMultiple();
    $options = $this->getAccountsOptions();
    foreach ($stores as $id => $store) {
      $form[$id] = [
        '#type' => 'details',
        '#open' => $config->get('stores.' . $id) === NULL,
        '#title' => $store->label(),
        '#element_validate' => [[$this, 'validateStore']],
      ];

      $form[$id]['disabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Do not track'),
        '#description' => $this->t('This will disable tracking of income and payments relating to this store.'),
        '#default_value' => $config->get("stores.{$id}.disabled"),
      ];

      $form[$id]['income_account'] = [
        '#type' => 'select',
        '#title' => $this->t('Income account'),
        '#description' => $this->t('Please choose the account to track income for this store against.'),
        '#options' => $options[AccountInterface::TYPE_INCOME],
        '#default_value' => $config->get("stores.{$id}.income_account") ?? '',
        '#empty_value' => '',
        '#states' => [
          'visible' => [':input[name="stores[' . $id . '][disabled]"]' => ['checked' => FALSE]],
          'required' => [':input[name="stores[' . $id . '][disabled]"]' => ['checked' => FALSE]],
        ],
      ];

      $form[$id]['accounts_receivable_account'] = [
        '#type' => 'select',
        '#title' => $this->t('A/R account'),
        '#description' => $this->t('Please choose the account to track accounts receivable for this store against.'),
        '#options' => $options[AccountInterface::TYPE_ASSET],
        '#default_value' => $config->get("stores.{$id}.accounts_receivable_account") ?? '',
        '#empty_value' => '',
        '#states' => [
          'visible' => [':input[name="stores[' . $id . '][disabled]"]' => ['checked' => FALSE]],
          'required' => [':input[name="stores[' . $id . '][disabled]"]' => ['checked' => FALSE]],
        ],
      ];
    }

    return $form;
  }

  /**
   * Element validation callback for store settings.
   *
   * @param array $element
   *   The store element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateStore(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    if (empty($values['disabled'])) {
      if (empty($values['income_account'])) {
        $form_state->setError($element['income_account'], $this->t('Income account is required.'));
      }
      if (empty($values['accounts_receivable_account'])) {
        $form_state->setError($element['accounts_receivable_account'], $this->t('Income account is required.'));
      }
    }
  }

  /**
   * Build the payment methods configuration form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Config\Config $config
   *   The commerce config.
   *
   * @return array
   *   The payment methods configuration subform.
   */
  protected function buildPaymentGatewaysForm(FormStateInterface $form_state, Config $config): array {
    $form = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payment methods'),
      '#tree' => TRUE,
    ];

    if (!$this->moduleHandler->moduleExists('commerce_payment')) {
      $form['#description'] = $this->t('Please enable Commerce Payment to configure payment method settings.');
      $form['#weight'] = 99;
      return $form;
    }

    $form['#description'] = $this->t('To configure the available payment methods please @link.', [
      '@link' => Link::createFromRoute($this->t('click here'), 'entity.commerce_payment_gateway.collection')->toString(),
    ]);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $gateways */
    $gateways = $this->entityTypeManager->getStorage('commerce_payment_gateway')->loadMultiple();
    $options = $this->getAccountsOptions(AccountInterface::TYPE_ASSET);
    foreach ($gateways as $id => $gateway) {
      $form[$id] = [
        '#type' => 'details',
        '#open' => $config->get('payment_gateways.' . $id) === NULL,
        '#title' => $gateway->label(),
      ];

      $form[$id]['asset_account'] = [
        '#type' => 'select',
        '#title' => $this->t('Asset account'),
        '#description' => $this->t('Please choose the account to track received money for this gateway against. Only select %do_not_track if this method is only used for stores that are not tracked.', [
          '%do_not_track' => $this->t('Do not track'),
        ]),
        '#options' => $options,
        '#empty_option' => new FormattableMarkup('- @label - ', [
          '@label' => $this->t('Do not track'),
        ]),
        '#default_value' => $config->get("payment_gateways.{$id}.asset_account"),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bookkeeping.commerce')
      ->set('stores', $form_state->getValue('stores'))
      ->set('payment_gateways', $form_state->getValue('payment_gateways'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
