<?php

namespace Drupal\transaction\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete transaction type entities.
 */
class TransactionTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new TransactionTypeDeleteForm object.
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
    $transaction_count = $this->queryFactory->get('transaction')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if ($transaction_count) {
      $caption = '<p>' . $this->formatPlural($transaction_count,
        'There is 1 transaction of type %type that prevents it from being deleted.',
        'There are @count transactions of type %type that prevents it from being deleted.',
        ['%type' => $this->entity->label()]
      ) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      $form['link'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.transaction.collection'),
        '#title' => $this->t('Manage transactions'),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.transaction_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(
      $this->t('Transaction type @label deleted.',
        [
          '@label' => $this->entity->label(),
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
