<?php

namespace Drupal\field_encrypt\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form for removing encryption on field.
 */
class FieldEncryptDecryptForm extends ConfirmFormBase {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The field name to decrypt.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FieldEncryptDecryptForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
  public function getFormId() {
    return 'field_encrypt_decrypt_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to remove encryption for field %field on %entity_type?', array('%field' => $this->fieldName, '%entity_type' => $this->entityType));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('field_encrypt.field_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Remove field encryption');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action removes field encryption from the specified field. Existing field data will be decrypted through a batch process.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $field_name = NULL) {
    $this->entityType = $entity_type;
    $this->fieldName = $field_name;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('field_storage_config');
    $field_storage_config = $storage->load($this->entityType . '.' . $this->fieldName);
    $field_storage_config->unsetThirdPartySetting('field_encrypt', 'encrypt');
    $field_storage_config->unsetThirdPartySetting('field_encrypt', 'properties');
    $field_storage_config->unsetThirdPartySetting('field_encrypt', 'encryption_profile');
    $field_storage_config->save();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
