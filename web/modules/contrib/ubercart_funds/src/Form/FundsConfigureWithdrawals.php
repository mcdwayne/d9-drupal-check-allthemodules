<?php

namespace Drupal\ubercart_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ubercart_funds\WithdrawalMethodManager;

/**
 * Form to configure the withdrawals methods allowed.
 */
class FundsConfigureWithdrawals extends ConfigFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\ubercart_funds\WithdrawalMethodManager
   */
  protected $withdrawalMethodManager;

  /**
   * Class constructor.
   */
  public function __construct(WithdrawalMethodManager $withdrawal_manager) {
    $this->withdrawalMethodManager = $withdrawal_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.withdrawal_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_funds_configure_withdrawal_methods';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'uc_funds.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uc_funds.settings');
    $values = $config->get('withdrawal_methods');
    $methods = $this->withdrawalMethodManager->getDefinitions();

    foreach ($methods as $key => $method) {
      $readable_methods[$key] = $method['name']->render();
    }

    $form['methods'] = [
      '#type' => 'checkboxes',
      '#options' => $readable_methods,
      '#default_value' => $values ? $values['methods'] : [],
      '#title' => $this->t('Choose Payment methods allowed for withdrawals'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $this->config('uc_funds.settings')
      ->set('withdrawal_methods', $values)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
