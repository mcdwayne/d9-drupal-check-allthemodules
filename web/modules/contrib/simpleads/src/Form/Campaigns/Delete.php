<?php

namespace Drupal\simpleads\Form\Campaigns;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Campaigns;

/**
 * Delete advertisement campaign form.
 */
class Delete extends ConfirmFormBase {

  protected $campaign;

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
    return t('Are you sure you would like to delete <em>%name</em> campaign?', ['%name' => $this->campaign->getCampaignName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('simpleads.campaigns');
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
    return $this->t('Delete Campaign');
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
    $form['campaign_type'] = [
      '#type'  => 'hidden',
      '#value' => $type,
    ];
    $form['id'] = [
      '#type'  => 'hidden',
      '#value' => $id,
    ];
    $this->campaign = (new Campaigns)->setId($id)->load();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = $this->campaign->getOptions(TRUE);
    $this->campaign->getSubmitForm('delete', $options, $form_state, $form_state->getValue('campaign_type'), $form_state->getValue('id'));
    $this->campaign->delete();
    $form_state->setRedirect('simpleads.campaigns');
  }

}
