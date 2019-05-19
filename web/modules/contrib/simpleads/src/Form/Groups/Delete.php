<?php

namespace Drupal\simpleads\Form\Groups;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Groups;

/**
 * Delete advertisement form.
 */
class Delete extends ConfirmFormBase {

  protected $group;

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'simpleads_campaign_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you would like to delete <em>%name</em> group?', ['%name' => $this->group->getGroupName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('simpleads.groups');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Are you sure you would like to continue? Please make sure you are not using this group in any of your ads. This operation cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Group');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $form_id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->group = (new Groups)->setId($id)->load();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->group->delete();
    $form_state->setRedirect('simpleads.groups');
  }

}
