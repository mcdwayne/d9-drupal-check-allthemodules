<?php

namespace Drupal\token_custom\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting a custom token type entity.
 */
class TokenCustomTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $queryFactory;

  /**
   * Constructs a query factory object.
   *
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
    $tokens = $this->queryFactory->get('token_custom')->condition('type', $this->entity->id())->execute();
    if (!empty($tokens)) {
      $caption = '<p>' . $this->formatPlural(count($tokens), '%label is used by 1 custom token on your site. You can not remove this token type until you have removed all of the %label tokens.', '%label is used by @count custom tokens on your site. You may not remove %label until you have removed all of the %label custom tokens.', [
        '%label' => $this->entity->label(),
      ]) . '</p>';
      $form['description'] = ['#markup' => $caption];
      return $form;
    }
    else {
      return parent::buildForm($form, $form_state);
    }
  }

}
