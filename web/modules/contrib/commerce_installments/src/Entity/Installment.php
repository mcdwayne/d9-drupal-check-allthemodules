<?php

namespace Drupal\commerce_installments\Entity;

use Drupal\commerce_installments\UrlParameterBuilderTrait;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Defines the Installment entity.
 *
 * @ingroup commerce_installments
 *
 * @ContentEntityType(
 *   id = "installment",
 *   label = @Translation("Installment"),
 *   label_collection = @Translation("Installments"),
 *   label_singular = @Translation("installment"),
 *   label_plural = @Translation("installments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count installment",
 *     plural = "@count installments",
 *   ),
 *   bundle_label = @Translation("Installment type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_installments\Form\InstallmentForm",
 *     },
 *     "access" = "Drupal\commerce_installments\InstallmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_installments\Routing\InstallmentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "installment",
 *   data_table = "installment_field_data",
 *   admin_permission = "administer installment entities",
 *   entity_keys = {
 *     "id" = "installment_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}/installment/{installment}",
 *
 *   },
 *   bundle_entity_type = "installment_type",
 *   field_ui_base_route = "entity.installment_type.edit_form"
 * )
 */
class Installment extends ContentEntityBase implements InstallmentInterface {

  use EntityChangedTrait;
  use UrlParameterBuilderTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    return $this->getUrlParameters() + parent::urlRouteParameters($rel);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    /** @var \Drupal\commerce_price\RounderInterface $rounder */
    $rounder = \Drupal::service('commerce_price.rounder');
    $currencyStorage = $this->entityTypeManager()->getStorage('commerce_currency');
    $price = $rounder->round($this->getAmount());
    $args = [
      ':amount' => \Drupal::service('commerce_price.number_formatter_factory')->createInstance()->formatCurrency($price->getNumber(), $currencyStorage->load($price->getCurrencyCode())),
      ':date' => \Drupal::service('date.formatter')->format($this->getPaymentDate(), 'html_date'),
      ':state' => $this->getState()->getLabel(),
    ];
    return $this->t(':amount on :date is :state', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state_id) {
    $this->set('state', $state_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentDate($timestamp) {
    $this->set('payment_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentDate() {
    return $this->get('payment_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    if (!$this->get('amount')->isEmpty()) {
      return $this->get('amount')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_installments\Entity\Installment $installment
   *   The installment payment.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(Installment $installment) {
    $workflow = InstallmentType::load($installment->bundle())->getWorkflowId();
    return $workflow;
  }

  /**
   * @inheritDoc
   */
  public function getInstallmentPlan() {
    $results = $this->entityTypeManager()->getStorage('installment_plan')->getQuery()
      ->condition('installments', $this->id())->execute();
    return $this->entityTypeManager()->getStorage('installment_plan')->load(reset($results));
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Installment entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('Payment state'))
      ->setDescription(t('The installment payment state.'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'type' => 'state_transition_form',
        'weight' => 2,
        'label' => 'inline',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', [Installment::class, 'getWorkflowId']);

    $fields['payment_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Installment date'))
      ->setDescription(t('The date to process installment payment.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
        'settings' => [
          'date_format' => 'html_date',
        ],
        'label' => 'inline',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDescription(t('The payment amount.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'type' => 'commerce_price_default',
        'weight' => 1,
        'settings' => [
          'strip_trailing_zeroes' => FALSE,
          'display_currency_code' => FALSE,
        ],
        'label' => 'inline',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
