<?php

namespace Drupal\record;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for record type forms.
 *
 * @internal
 */
class RecordTypeForm extends BundleEntityFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the RecordTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add record type');
      $fields = $this->entityManager->getBaseFieldDefinitions('node');
    }
    else {
      $form['#title'] = $this->t('Edit %label record type', ['%label' => $type->label()]);
      $fields = $this->entityManager->getFieldDefinitions('record', $type->id());
    }

    $form['label'] = [
      '#title' => t('Record type'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of this record type. This text will be displayed as part of the list on the <em>Add record</em> page. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => [
        'exists' => ['Drupal\record\Entity\RecordType', 'load'],
        'source' => ['name'],
      ],
      '#description' => t('A unique machine-readable name for this record type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %record-add page, in which underscores will be converted into hyphens.', [
        '%record-add' => t('Add record'),
      ]),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new record</em> page.'),
    ];

    $form['submission'] = [
      '#type' => 'details',
      '#title' => t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    ];
    $form['submission']['title_label'] = [
      '#title' => t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['title']->getLabel(),
      '#required' => TRUE,
    ];
    $form['submission']['help'] = [
      '#type' => 'textarea',
      '#title' => t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => t('This text will be displayed at the top of the page when creating or editing records of this type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save record type');
    $actions['delete']['#value'] = t('Delete record type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $type->setNewRevision($form_state->getValue(['options', 'revision']));
    $type->set('id', trim($type->id()));
    $type->set('label', trim($type->label()));

    $status = $type->save();

    $t_args = ['%name' => $type->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The record type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      record_add_data_field($type);
      drupal_set_message(t('The record type %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $type->link($this->t('View'), 'collection')]);
      $this->logger('record')->notice('Added record type %name.', $context);
    }

    $fields = $this->entityManager->getFieldDefinitions('record', $type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field->getLabel() != $title_label) {
      $title_field->getConfig($type->id())->setLabel($title_label)->save();
    }

    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->urlInfo('collection'));
  }

}
