<?php

namespace Drupal\elastic_search\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\elastic_search\Elastic\ElasticIndexManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Elastic index entities.
 */
class ElasticIndexDeleteForm extends EntityConfirmFormBase {

  /**
   * @var ElasticIndexManager
   */
  protected $indexManager;

  /**
   * @inheritDoc
   */
  public function __construct(ElasticIndexManager $indexManager) {
    $this->indexManager = $indexManager;
  }

  /**
   * @inheritDoc
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('elastic_search.indices.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?',
                    ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.elastic_index.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    try {
      $this->indexManager->deleteIndexOnServer($this->entity);
    } catch (\Throwable $t) {
      $decoded = json_decode($t->getMessage());
      if ($decoded->status !== 404) {
        //If it is not an index not found error cancel the delete
        drupal_set_message($this->t('Error: @status : @type : @reason',
                                    [
                                      '@status' => $decoded->status,
                                      '@type'   => $decoded->error->type,
                                      '@reason' => $decoded->error->reason,
                                    ]));
        $form_state->setRedirectUrl($this->getCancelUrl());
        return;
      }
    }

    $this->entity->delete();
    drupal_set_message(
      $this->t('content @type: deleted @label.',
               [
                 '@type'  => $this->entity->bundle(),
                 '@label' => $this->entity->label(),
               ]
      )
    );
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
