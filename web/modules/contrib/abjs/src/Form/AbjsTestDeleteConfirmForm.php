<?php

namespace Drupal\abjs\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for confirm delete test.
 */
class AbjsTestDeleteConfirmForm extends ConfirmFormBase {
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
    return 'abjs_test_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete test %id?', ['%id' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('abjs.test_admin');
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
   * @param int $tid
   *   The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tid = NULL) {
    $this->id = $tid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->database->getConnection()->delete('abjs_test')
      ->condition('tid', $this->id)
      ->execute();
    $this->database->getConnection()->delete('abjs_test_condition')
      ->condition('tid', $this->id)
      ->execute();
    $this->database->getConnection()->delete('abjs_test_experience')
      ->condition('tid', $this->id)
      ->execute();

    $this->messenger()->addMessage(t('Test %id has been deleted.', ['%id' => $this->id]));

    $form_state->setRedirect('abjs.test_admin');
  }

}
