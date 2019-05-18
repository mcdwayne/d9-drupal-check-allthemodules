<?php

namespace Drupal\openimmo\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\openimmo\Entity\OpenImmoInterface;

/**
 * Builds the form to delete queries from OpenImmo entities.
 */
class OpenImmoQueryDeleteForm extends ConfirmFormBase {

  /**
   * The connect entity the query being deleted belongs to.
   *
   * @var \Drupal\openimmo\OpenImmoInterface
   */
  protected $source;

  /**
   * The connect query being deleted.
   *
   * @var \Drupal\openimmo\QueryInterface
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
    return $this->t('Are you sure you want to delete query %query from source %connect?', ['%query' => $this->query->label(), '%connect' => $this->source->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->source->toUrl('queries-list');
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
   * @param \Drupal\openimmo\Entity\OpenImmoInterface $openimmo
   *   The connect entity being edited.
   * @param string|null $source_query
   *   The connect query being deleted.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, OpenImmoInterface $openimmo = NULL, $source_query = NULL) {
    try {
      $this->query = $openimmo->getQuery($source_query);
    }
    catch (\InvalidArgumentException $e) {
      throw new NotFoundHttpException();
    }
    $this->source = $openimmo;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->source
      ->deleteQuery($this->query->id())
      ->save();

    drupal_set_message($this->t('%query query deleted.', ['%query' => $this->query->label()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
