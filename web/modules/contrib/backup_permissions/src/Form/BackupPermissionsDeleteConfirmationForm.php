<?php

namespace Drupal\backup_permissions\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\backup_permissions\BackupPermissionsStorageTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a confirmation form for deleting backup.
 */
class BackupPermissionsDeleteConfirmationForm extends ConfirmFormBase {

  use BackupPermissionsStorageTrait;

  /**
   * The ID of the item to delete.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'backup_permissions_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %id?', array('%id' => $this->id));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('backup_permissions.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
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
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bid = NULL) {
    $this->id = $bid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (is_numeric($this->id)) {
      // Getting title from backup id.
      $backup = $this->load(array('id' => $this->id));
      if (!empty($backup)) {
        $title = $backup[0]->title;
        // Removing selected backup.
        $this->delete($this->id);
        drupal_set_message($this->t('@title has been deleted.', array('@title' => $title)));
      }
      $form_state->setRedirect('backup_permissions.settings');
    }
    else {
      // We will just show a standard "access denied" page in this case.
      throw new AccessDeniedHttpException();
    }
  }

}
