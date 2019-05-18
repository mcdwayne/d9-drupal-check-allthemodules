<?php

namespace Drupal\publishthis\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements an example form.
 */
class DeletePublishthisActionForm extends ConfirmFormBase {
  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;
  protected $title;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'publishthis_action_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $query = \Drupal::database()->select('pt_publishactions', 'pb')
      ->fields('pb', [])
      ->condition('pb.id', $id)
      ->execute();
    $result = $query->fetchAssoc();

    $this->id = $id;
    $this->title = $result['title'];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id = $this->id;
    \Drupal::database()->delete('pt_publishactions')
      ->condition('id', $id)
      ->execute();
    drupal_set_message($this->t('Publishthis Action deleted successfully.'));
    $url = Url::fromRoute('publishthis.publishthis-action');
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('publishthis.publishthis-action');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete publishthis action %title?', ['%title' => $this->title]);
  }

}
