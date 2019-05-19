<?php

namespace Drupal\taxonomy_reference_shield\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\taxonomy\Form\TermDeleteForm;
use Drupal\taxonomy_reference_shield\ReferenceHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replacement delete form for taxonomy terms.
 */
class ShieldDeleteForm extends TermDeleteForm {

  /**
   * The reference handler.
   *
   * @var \Drupal\taxonomy_reference_shield\ReferenceHandlerInterface
   */
  protected $referenceHandler;

  /**
   * The Drupal render utility.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\taxonomy_reference_shield\ReferenceHandlerInterface $reference_handler
   *   The reference handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal render utility.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, ReferenceHandlerInterface $reference_handler, RendererInterface $renderer) {
    $this->referenceHandler = $reference_handler;
    $this->renderer = $renderer;
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('taxonomy_reference_shield.relationship_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Unable to delete the @entity-type %label.', [
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $term = $this->getEntity();

    $list_items = [];
    foreach ($this->referenceHandler->getReferences($term) as $entity_type) {
      foreach ($entity_type['bundles'] as $bundle) {
        foreach ($bundle['entities'] as $entity_id => $entity) {
          foreach ($entity['fields'] as $field) {
            $list_items[] = $this->t('Referenced from the field @field_label in the entity @id of type @bundle_label belonging to the entity type @entity_type_label.', [
              '@id' => $entity_id,
              '@bundle_label' => $bundle['label'],
              '@entity_type_label' => $entity_type['label'],
              '@field_label' => $field['label'],
            ]);
          }
        }
      }
    }

    $list = [
      '#theme' => 'item_list',
      '#items' => $list_items,
    ];

    return '<p>' . $this->t('The term you wish to delete is currently being referenced:') . '</p>' . $this->renderer->render($list);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#access'] = FALSE;
    return $form;
  }

}
