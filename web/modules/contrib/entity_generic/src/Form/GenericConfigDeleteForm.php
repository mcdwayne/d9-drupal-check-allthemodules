<?php

namespace Drupal\entity_generic\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting an entity type.
 */
class GenericConfigDeleteForm extends EntityDeleteForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
  public function buildForm(array $form, FormStateInterface $form_state) {

    $num_entities = $this->entityTypeManager->getStorage($this->entity->getEntityTypeId())->getQuery()
      ->condition('bundle', $this->entity->id())
      ->count()
      ->execute();
    if ($num_entities) {
      $form['#title'] = $this->getQuestion();
      $form['description'] = [
        '#type' => 'inline_template',
        '#template' => '<p>{{ message }}</p>',
        '#context' => [
          'message' => $this->formatPlural($num_entities,
            '%type is used by @count entity on your site. You can not remove this system until you have removed all of the %type entities.',
            '%type is used by @count entities on your site. You can not remove this system until you have removed all of the %type entities.',
            ['%type' => $this->entity->label()]),
        ],
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
