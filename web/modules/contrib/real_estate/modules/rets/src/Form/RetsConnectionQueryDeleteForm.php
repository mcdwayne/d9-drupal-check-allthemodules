<?php

namespace Drupal\real_estate_rets\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\real_estate_rets\Entity\RetsConnectionInterface;

/**
 * Builds the form to delete queries from RetsConnection entities.
 */
class RetsConnectionQueryDeleteForm extends ConfirmFormBase {

  /**
   * The connect entity the query being deleted belongs to.
   *
   * @var \Drupal\real_estate_rets\RetsConnectionInterface
   */
  protected $connection;

  /**
   * The connect query being deleted.
   *
   * @var \Drupal\real_estate_rets\QueryInterface
   */
  protected $query;

  /**
   * The query being deleted.
   *
   * @var string
   */
  protected $queryId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'connect_query_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete query %query from connection %connect?', ['%query' => $this->query->label(), '%connect' => $this->connection->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->connection->toUrl('queries-list');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\real_estate_rets\Entity\RetsConnectionInterface $real_estate_rets_connection
   *   The connect entity being edited.
   * @param string|null $connection_query
   *   The connect query being deleted.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RetsConnectionInterface $real_estate_rets_connection = NULL, $connection_query = NULL) {
    try {
      $this->query = $real_estate_rets_connection->getQuery($connection_query);
    }
    catch (\InvalidArgumentException $e) {
      throw new NotFoundHttpException();
    }
    $this->connection = $real_estate_rets_connection;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->connection
      ->deleteQuery($this->query->id())
      ->save();

    drupal_set_message($this->t('%query query deleted.', ['%query' => $this->query->label()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
