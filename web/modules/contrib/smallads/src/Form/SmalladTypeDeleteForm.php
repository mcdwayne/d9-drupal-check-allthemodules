<?php

namespace Drupal\smallads\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting a smallad type entity.
 */
class SmalladTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $queryFactory;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The entity being used by this form.
   *
   * @var Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $entity;

  /**
   * Constructs a query factory object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   */
  public function __construct(QueryFactory $query_factory, EntityManager $entity_manager) {
    $this->queryFactory = $query_factory;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $smallads = $this->queryFactory->get('smallad')->condition('smallad_type', $this->entity->id())->execute();
    $entity_type = $this->entity->getTargetEntityTypeId();
    $caption = '';
    foreach (array_keys($this->smalladManager->getFields($entity_type)) as $field_name) {
      /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
      if (($field_storage = FieldStorageConfig::loadByName($entity_type, $field_name)) && $field_storage->getSetting('smallad_type') == $this->entity->id() && !$field_storage->isDeleted()) {
        $caption .= '<p>' . $this->t('%label is used by the %field field on your site. You can not remove this smallad type until you have removed the field.', array(
          '%label' => $this->entity->label(),
          '%field' => $field_storage->label(),
        )) . '</p>';
      }
    }

    if (!empty($smallads)) {
      $caption .= '<p>' . $this->formatPlural(count($smallads), '%label is used by 1 smallad on your site. You can not remove this smallad type until you have removed all of the %label smallads.', '%label is used by @count smallads on your site. You may not remove %label until you have removed all of the %label smallads.', array('%label' => $this->entity->label())) . '</p>';
    }
    if ($caption) {
      $form['description'] = array('#markup' => $caption);
      return $form;
    }
    else {
      return parent::buildForm($form, $form_state);
    }
  }

}
