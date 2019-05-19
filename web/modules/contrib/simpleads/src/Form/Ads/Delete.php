<?php

namespace Drupal\simpleads\Form\Ads;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Ads;

/**
 * Delete advertisement form.
 */
class Delete extends ConfirmFormBase {

  protected $ad;

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'simpleads_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you would like to delete <em>%name</em>?', ['%name' => $this->ad->getAdName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('simpleads.ads');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Are you sure you would like to continue? This operation cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Advertisement');
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
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $form['ad_type'] = [
      '#type'  => 'hidden',
      '#value' => $type,
    ];
    $form['id'] = [
      '#type'  => 'hidden',
      '#value' => $id,
    ];
    $this->ad = (new Ads)->setId($id)->load();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = $this->ad->getOptions(TRUE);
    $this->ad->getSubmitForm('delete', $options, $form_state, $form_state->getValue('ad_type'), $form_state->getValue('id'));
    $this->ad->delete();
    $form_state->setRedirect('simpleads.ads');
  }

}
