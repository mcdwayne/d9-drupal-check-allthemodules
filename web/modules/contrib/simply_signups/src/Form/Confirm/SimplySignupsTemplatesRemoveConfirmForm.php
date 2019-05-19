<?php

namespace Drupal\simply_signups\Form\Confirm;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class SimplySignupsTemplatesRemoveConfirmForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $path = \Drupal::service('path.current')->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $tid = $arg[4];
    $db = \Drupal::database();
    $query = $db->select('simply_signups_templates', 'p');
    $query->fields('p');
    $query->condition('id', $tid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $title = $row->title;
    }
    $this->id = $title;
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-template-remove-confirm-form', 'simply-signups-form'],
    ];
    $form['title'] = [
      '#type' => 'hidden',
      '#value' => $title,
    ];
    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo: Do the deletion.
    $values = $form_state->getValues();
    $db = \Drupal::database();
    $db->delete('simply_signups_templates')
      ->condition('id', $values['tid'], '=')
      ->execute();
    $db->delete('simply_signups_templates_fields')
      ->condition('tid', $values['tid'], '=')
      ->execute();
    $form_state->setRedirect('simply_signups.templates');
    drupal_set_message($this->t('Template: <em>@title</em> successfully removed.', ['@title' => $values['title']]));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "simply_signups_templates_remove_confirm_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('simply_signups.templates');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to remove template: %id?', ['%id' => $this->id]);
  }

}
