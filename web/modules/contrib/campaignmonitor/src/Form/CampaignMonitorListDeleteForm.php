<?php

namespace Drupal\campaignmonitor\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form for deleting mymodule data.
 */
class CampaignMonitorListDeleteForm extends ConfirmFormBase {

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
    return 'campaignmonitor_list_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $options = campaignmonitor_get_extended_list_settings($this->id);
    return t('Do you want to delete %name?', ['%name' => $options['name']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('campaignmonitor.lists');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Only do this if you are sure!');
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
   * {@inheritdoc}
   *
   * @param int $list_id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $list_id = NULL) {
    $this->id = $list_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    campaignmonitor_delete_list($this->id);
    $url = Url::fromRoute('campaignmonitor.refresh_lists');
    $form_state->setRedirectUrl($url);
  }

}
