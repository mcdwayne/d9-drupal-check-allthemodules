<?php

namespace Drupal\commerce_installments\Entity;

use Drupal\commerce_installments\UrlParameterBuilderTrait;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Defines the Installment Plan entity.
 *
 * @ingroup commerce_installments
 *
 * @ContentEntityType(
 *   id = "installment_plan",
 *   label = @Translation("Installment Plan"),
 *   label_collection = @Translation("Installment Plans"),
 *   label_singular = @Translation("installment plan"),
 *   label_plural = @Translation("installment plans"),
 *   label_count = @PluralTranslation(
 *     singular = "@count installment plan",
 *     plural = "@count installment plans",
 *   ),
 *   bundle_label = @Translation("Installment Plan type"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_installments\InstallmentPlanStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_installments\InstallmentPlanListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_installments\Form\InstallmentPlanForm",
 *       "add" = "Drupal\commerce_installments\Form\InstallmentPlanForm",
 *       "edit" = "Drupal\commerce_installments\Form\InstallmentPlanForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\commerce_installments\InstallmentPlanAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_installments\Routing\InstallmentPlanHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "installment_plan",
 *   data_table = "installment_plan_field_data",
 *   revision_table = "installment_plan_revision",
 *   revision_data_table = "installment_plan_field_revision",
 *   admin_permission = "administer installment plan entities",
 *   entity_keys = {
 *     "id" = "plan_id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/orders/{commerce_order}/plans",
 *     "canonical" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}",
 *     "add-page" = "/admin/commerce/orders/{commerce_order}/plan/add",
 *     "add-form" = "/admin/commerce/orders/{commerce_order}/plan/add/{installment_plan_type}",
 *     "edit-form" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}/edit",
 *     "delete-form" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}/delete",
 *     "version-history" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}/revisions",
 *     "revision" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}/revisions/{installment_plan_revision}/view",
 *     "revision-revert-form" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}/revisions/{installment_plan_revision}/revert",
 *     "revision-delete-form" = "/admin/commerce/orders/{commerce_order}/plan/{installment_plan}/revisions/{installment_plan_revision}/delete",
 *   },
 *   bundle_entity_type = "installment_plan_type",
 *   field_ui_base_route = "entity.installment_plan_type.edit_form"
 * )
 */
class InstallmentPlan extends RevisionableContentEntityBase implements InstallmentPlanInterface {

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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly, make the installment_plan owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // UIs should use the number formatter to show a more user-readable version.
    return $this->t('Payment plan #:id', [':id' => $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGateway() {
    return $this->get('payment_gateway')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGatewayId() {
    return $this->get('payment_gateway')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->get('payment_method')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodId() {
    return $this->get('payment_method')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
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
   * @inheritDoc
   */
  public function getInstallments() {
    $this->get('installments')->referencedEntities();
  }

  /**
   * @inheritDoc
   */
  public function setInstallments(array $installments) {
    $this->set('installments', $installments);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function hasInstallments() {
    return !$this->get('installments')->isEmpty();
  }

  /**
   * @inheritDoc
   */
  public function addInstallment(InstallmentInterface $installment) {
    if (!$this->hasInstallment($installment)) {
      $this->get('installments')->appendItem($installment);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeInstallment(InstallmentInterface $installment) {
    $index = $this->getInstallmentIndex($installment);
    if ($index !== FALSE) {
      $this->get('installments')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function hasInstallment(InstallmentInterface $installment) {
    return $this->getInstallmentIndex($installment) !== FALSE;
  }

  /**
   * Gets the index of the given installment.
   *
   * @param \Drupal\commerce_installments\Entity\InstallmentInterface $installment
   *   The installment.
   *
   * @return int|bool
   *   The index of the given installment, or FALSE if not found.
   */
  protected function getInstallmentIndex(InstallmentInterface $installment) {
    $values = $this->get('installments')->getValue();
    $installments = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    return array_search($installment->id(), $installments);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the installments of a deleted plan.
    $installments = [];
    foreach ($entities as $entity) {
      if (empty($entity->installments)) {
        continue;
      }
      foreach ($entity->installments as $item) {
        $installments[$item->target_id] = $item->entity;
      }
    }
    $variation_storage = \Drupal::service('entity_type.manager')->getStorage('installment');
    $variation_storage->delete($installments);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Installment Plan entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The parent order.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_order')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
        'settings' => [
          'link' => TRUE,
        ]
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['payment_gateway'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment gateway'))
      ->setDescription(t('The payment gateway.'))
      ->setSetting('target_type', 'commerce_payment_gateway')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 1,
        'settings' => [
          'link' => FALSE,
        ]
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['payment_method'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The payment method.'))
      ->setSetting('target_type', 'commerce_payment_method')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 2,
        'settings' => [
          'link' => TRUE,
        ]
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
