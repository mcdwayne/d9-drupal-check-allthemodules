<?php

namespace Drupal\entity_generic\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting an entity type.
 */
class GenericTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_entities = $this->queryFactory->get($this->entity->getEntityType()->getBundleOf())
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_entities) {
      $caption = '<p>' . $this->formatPlural($num_entities, '%type is used by 1 object on your site. You can not remove this entity type until you have removed all of the %type entity.', '%type is used by @count objects on your site. You may not remove %type until you have removed all of the %type entities.', array('%type' => $this->entity->label())) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = array('#markup' => $caption);
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
