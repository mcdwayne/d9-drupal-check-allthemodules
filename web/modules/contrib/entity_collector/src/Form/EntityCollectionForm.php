<?php

namespace Drupal\entity_collector\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Entity collection edit forms.
 *
 * @ingroup entity_collector
 */
class EntityCollectionForm extends ContentEntityForm {

  /**
   * Entity Collectin Manager.
   *
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  protected $entityCollectionManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityCollectionManagerInterface $entityCollectionManager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->entityCollectionManager = $entityCollectionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_collection.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\entity_collector\Entity\EntityCollection */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection */
    $entityCollection = $this->getEntity();

    $owner = $form_state->getValue('owner');
    $participants = $form_state->getValue('participants');

    if (isset($owner) || isset($participants)) {
      $participantIds = $entityCollection->getParticipantsIds();
      $ownerId = [$entityCollection->getOwnerId()];

      if (!empty($owner)) {
        $ownerId = array_map(function ($item) {
          return is_array($item) && isset($item['target_id']) ? $item['target_id'] : NULL;
        }, $owner);
        $participantIds = array_filter($participantIds);
      }

      if (!empty($participants)) {
        $participantIds = array_map(function ($item) {
          return is_array($item) && isset($item['target_id']) ? $item['target_id'] : NULL;
        }, $participants);
        $participantIds = array_filter($participantIds);
      }

      if (!empty(array_intersect($ownerId, $participantIds))) {
        $field = 'participants';
        if(empty($participants)) {
          $field = 'owner';
        }

        $form_state->setErrorByName($field, $this->t('You cannot add the owner to the list of participants, or vice versa.'));
      }
    }

    return parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection */
    $entityCollection = $this->entity;


    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entityCollection->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entityCollection->setRevisionCreationTime(REQUEST_TIME);
      $entityCollection->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entityCollection->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Entity collection.', [
          '%label' => $entityCollection->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Entity collection.', [
          '%label' => $entityCollection->label(),
        ]));
    }

    if ($this->operation != 'block') {
      $form_state->setRedirect('entity.entity_collection.canonical', ['entity_collection' => $entityCollection->id()]);
    }
    else {
      $entityCollectionType = $this->entityCollectionManager->getEntityCollectionBundleType($entityCollection);
      $this->entityCollectionManager->setActiveCollection($entityCollectionType, $entityCollection);
    }
  }

}
