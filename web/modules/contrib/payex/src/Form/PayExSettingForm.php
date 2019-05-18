<?php

/**
 * @file
 * Contains \Drupal\payex\Form\PayExSettingForm.
 */

namespace Drupal\payex\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for PayExSetting config entities.
 */
class PayExSettingForm extends EntityForm {

  /**
   * The PayExSetting entity.
   *
   * @var \Drupal\payex\Entity\PayExSettingInterface
   */
  protected $entity;

  /**
   * The PayExSetting storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a PayExSettingForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->storage = $entityTypeManager->getStorage('payex_setting');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $entity->label(),
    ];

    // If creating a new entity, calculate a safe default machine name.
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this PayEx setting instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'payExSettingExists'],
        'source' => ['name'],
        'replace_pattern' => '[^a-z0-9_]+',
        'replace' => '_',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['live'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable live transactions'),
      '#description' => $this->t('Warning, only use this for a live site running real transactions.'),
      '#default_value' => $entity->getLive(),
    ];

    $form['merchantAccount'] = [
      '#type' => 'textfield',
      '#title' => t('Merchant account'),
      '#description' => t('PayEx merchant account number. Can be found under “Merchant Profile” in the PayEx admin panel.'),
      '#default_value' => $entity->getMerchantAccount(),
      '#required' => TRUE,
    ];

    // When the encryption key is not set, provide a simple field for it.
    if ($entity->isNew()) {
      $form['encryptionKey'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Encryption key'),
        '#description' => $this->t('PayEx encryption key. You can generate a new encryption under “Merchant Profile” in the PayEx admin panel. If you do, remember to change it here as well, or all payments will fail.'),
        '#required' => TRUE,
      ];
    }
    else {
      $form['encryption_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('Encryption key'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['encryption_wrapper']['encryption_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New encryption key'),
        '#description' => $this->t('If your encryption key has changed, you can change the one stored in the Drupal database here.'),
      ];
    }

    $form['purchaseOperation'] = [
      '#type' => 'radios',
      '#title' => $this->t('Purchase operations'),
      '#required' => TRUE,
      '#options' => [
        'AUTHORIZATION' => $this->t('Authorize'),
        'SALE' => $this->t('Capture'),
      ],
      '#default_value' => $entity->getPurchaseOperation(),
    ];

    $form['PPG'] = [
      '#type' => 'select',
      '#title' => $this->t('PayEx Payment Gateway (PPG)'),
      '#description' => $this->t('Select which Payement gateway to use. See more information about the differences of the two at <a href=":payex_doc_url">Payex documentation</a>.', [':payex_doc_url' => 'http://www.payexpim.com']),
      '#required' => TRUE,
      '#options' => [
        '1.0' => '1.0',
        '2.0' => '2.0',
      ],
      '#default_value' => $entity->getPPG(),
    ];

    $form['defaultCurrencyCode'] = [
      '#type' => 'select',
      '#title' => $this->t('Default currency'),
      '#description' => $this->t('Select the default currency for PayEx payments. This setting may be overridden by implemeting modules.'),
      '#required' => TRUE,
      '#options' => [
        'DKK' => $this->t('Danish krone'),
        'EUR' => $this->t('Euro'),
        'GBP' => $this->t('Pound sterling'),
        'NOK' => $this->t('Norwegian krone'),
        'SEK' => $this->t('Swedish krona'),
        'USD' => $this->t('U.S. Dollar'),
      ],
      '#default_value' => $entity->getDefaultCurrencyCode(),
    ];

    $form['defaultVat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default VAT rate'),
      '#description' => $this->t('Select the default VAT (value added tax) rate for PayEx payments. 0 for no VAT. This setting may be overridden by implemeting modules.'),
      '#required' => TRUE,
      '#default_value' => $entity->getDefaultVat(),
      '#size' => 4,
      '#field_suffix' => '%',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Set the new encryption key if set
    if ($form_state->getValue('encryption_key')) {
      $this->entity->setEncryptionKey($form_state->getValue('encryption_key'));
    }
    $form_state->setRedirect('payex.admin_page');
  }

  /**
   * Returns whether a payex_setting id already exists.
   *
   * @param string $value
   *   The id of the payex_setting.
   *
   * @return bool
   *   Returns TRUE if the payex_setting already exists, FALSE otherwise.
   */
  public function payExSettingExists($value) {
    return (bool) $this->entityTypeManager->getStorage('payex_setting')->getQuery()
      ->condition('id', $value)
      ->count()
      ->execute();
  }

}
