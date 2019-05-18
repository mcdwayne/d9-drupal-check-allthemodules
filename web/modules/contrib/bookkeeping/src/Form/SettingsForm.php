<?php

namespace Drupal\bookkeeping\Form;

use Drupal\bookkeeping\Entity\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Bookkeeping settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  use AccountSettingsTrait;

  /**
   * Construct the commerce settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bookkeeping_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bookkeeping.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('bookkeeping.settings');
    $form['accounts_receivable_account'] = [
      '#type' => 'select',
      '#title' => $this->t('Accounts Receivable account'),
      '#description' => $this->t('The default account for any accounts receivable.'),
      '#options' => $this->getAccountsOptions(AccountInterface::TYPE_ASSET),
      '#default_value' => $config->get('accounts_receivable_account'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bookkeeping.settings')
      ->set('accounts_receivable_account', $form_state->getValue('accounts_receivable_account'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
