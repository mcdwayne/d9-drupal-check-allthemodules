<?php

namespace Drupal\digitallocker_issuer\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DigitalLockerEntityTypeSettings.
 *
 * @package Drupal\digitallocker_issuer\Form
 */
class DigitalLockerEntityTypeSettings extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @inheritDoc
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   *
   * We need to blank out the base form ID so that poorly written form alters
   * that use the base form ID to target both add and edit forms don't pick
   * up our form. This should be fixed in core.
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    /* @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $bundle */
    $field_types = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $fields = \Drupal::entityManager()
      ->getFieldDefinitions('node', $bundle->get('type'));

    foreach ($fields as $field_name => $field_description) {
      if (substr($field_name, 0, 6) == 'field_') {
        $field_types[$field_description->getType()][$field_name] = $field_description->getLabel();
      }
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is a Digital Locker Certificate type.'),
      '#description' => $this->t('Tick this if nodes of this content type will store Digital Locker certificates.'),
      '#default_value' => $bundle->getThirdPartySetting('digitallocker_issuer', 'enabled', FALSE),
    ];

    $form['auto_publish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Publish'),
      '#description' => $this->t('Tick this if you would like to publish this url to Digital Locker directly upon creation.'),
      '#default_value' => $bundle->getThirdPartySetting('digitallocker_issuer', 'auto_publish', FALSE),
    ];

    $form['doctype'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DocType'),
      '#description' => $this->t('This is the unique doctype for the certificate. It should be 5 chars alpha.'),
      '#default_value' => $bundle->getThirdPartySetting('digitallocker_issuer', 'doctype', FALSE),
      '#size' => 5,
    ];

    $form['field_aadhaar'] = [
      '#type' => 'select',
      '#title' => $this->t('Aadhaar Field'),
      '#description' => $this->t('Please select the field that will store the Aadhaar Number.'),
      '#default_value' => $bundle->getThirdPartySetting('digitallocker_issuer', 'field_aadhaar', FALSE),
      '#options' => array_merge(
        ['' => '- Select -'],
        $field_types['integer']
      ),
    ];

    $form['field_validity'] = [
      '#type' => 'select',
      '#title' => $this->t('Validity Field'),
      '#description' => $this->t('Please select the field that will store the certificate Validity.'),
      '#default_value' => $bundle->getThirdPartySetting('digitallocker_issuer', 'field_validity', FALSE),
      '#options' => array_merge(
        ['' => '- Select -'],
        $field_types['datetime']
      ),
    ];

    $form['#entity_builders'][] = [$this, 'formBuilderCallback'];

    return parent::form($form, $form_state);
  }

  /**
   * Form builder callback.
   *
   * @param string $entity_type
   *   The type of entity.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle
   *   The entity whose settings are beign saved.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function formBuilderCallback($entity_type, ConfigEntityInterface $bundle, array &$form, FormStateInterface $form_state) {
    $bundle->setThirdPartySetting('digitallocker_issuer', 'enabled', $form_state->getValue('enabled'));
    $bundle->setThirdPartySetting('digitallocker_issuer', 'doctype', $form_state->getValue('doctype'));
    $bundle->setThirdPartySetting('digitallocker_issuer', 'auto_publish', $form_state->getValue('auto_publish'));
    $bundle->setThirdPartySetting('digitallocker_issuer', 'field_aadhaar', $form_state->getValue('field_aadhaar'));
    $bundle->setThirdPartySetting('digitallocker_issuer', 'field_validity', $form_state->getValue('field_validity'));
  }

}
