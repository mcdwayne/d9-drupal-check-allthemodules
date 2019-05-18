<?php

namespace Drupal\commerce_rental\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the rental period add/edit form.
 */
class RentalPeriodForm extends ContentEntityForm {

  /**
   * Constructs a new RentalPeriodForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($entity_manager, $entity_type_bundle_info);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_rental\Entity\RentalPeriodInterface $period */
    $period = $this->getEntity();
    $period->save();
    drupal_set_message($this->t('The rental period %label has been successfully saved.', ['%label' => $period->label()]));
  }

}
