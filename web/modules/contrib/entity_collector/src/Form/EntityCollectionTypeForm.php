<?php

namespace Drupal\entity_collector\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_collector\EntityCollectionSourceFieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityCollectionTypeForm.
 */
class EntityCollectionTypeForm extends EntityForm {

  protected $collectionSourceFieldManager;

  public function __construct(EntityCollectionSourceFieldManager $collectionSourceFieldManager) {
    $this->collectionSourceFieldManager = $collectionSourceFieldManager;

  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_collection.source_field_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType */
    $entityCollectionType = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entityCollectionType->label(),
      '#description' => $this->t("Label for the Entity collection type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entityCollectionType->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_collector\Entity\EntityCollectionType::load',
      ],
      '#disabled' => !$entityCollectionType->isNew(),
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    $options = [];
    foreach ($entity_types as $entity_type => $definition) {
      $options[$definition->id()] = $definition->get('label')->render();
    }
    asort($options);

    $form['source_dependent'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'source-dependent'],
    ];

    $form['source_dependent']['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity source'),
      '#default_value' => $entityCollectionType->getSource(),
      '#options' => $options,
      '#description' => $this->t('Media source that is responsible for additional logic related to this media type.'),
      '#empty_option' => $this->t('- Select entity source -'),
      '#required' => TRUE,
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    /** @var \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType */
    $entityCollectionType = $this->entity;
    $status = $entityCollectionType->save();

    $sourceField = $this->collectionSourceFieldManager->addEntitiesField($entityCollectionType);
    $entityCollectionType->setSourceFieldName($sourceField->getName());
    $entityCollectionType->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Entity collection type.', [
          '%label' => $entityCollectionType->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Entity collection type.', [
          '%label' => $entityCollectionType->label(),
        ]));
    }
    $form_state->setRedirectUrl($entityCollectionType->toUrl('collection'));
  }
}
