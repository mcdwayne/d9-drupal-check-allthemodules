<?php

namespace Drupal\abjs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Class for confirm delete experience.
 */
class AbjsExperienceDeleteConfirmForm extends ConfirmFormBase {
  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * Provides database connection service.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * Construct method.
   *
   * @param \Drupal\Core\Database\Database $database
   *   Provides database connection service.
   */
  public function __construct(Database $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abjs_experience_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete experience %id?', ['%id' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('abjs.experience_admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * Building form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   * @param int $eid
   *   The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eid = NULL) {
    $this->id = $eid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->database->getConnection()->delete('abjs_experience')
      ->condition('eid', $this->id)
      ->execute();
    $this->database->getConnection()->delete('abjs_test_experience')
      ->condition('eid', $this->id)
      ->execute();

    $this->messenger()->addMessage(t('Experience %id has been deleted.', ['%id' => $this->id]));

    $form_state->setRedirect('abjs.experience_admin');
  }

}
