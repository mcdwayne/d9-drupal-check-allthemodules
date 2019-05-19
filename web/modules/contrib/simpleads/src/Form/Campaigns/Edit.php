<?php

namespace Drupal\simpleads\Form\Campaigns;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Campaigns;

/**
 * Edit advertisement campaign form.
 */
class Edit extends FormBase {

  /**
   * Set page title.
   */
  public function setTitle($type = NULL, $id = NULL) {
    $campaigns = (new Campaigns())->setId($id)->load();
    return $this->t('Edit <em>@name</em>', ['@name' => $campaigns->getCampaignName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_campaign_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $campaigns = (new Campaigns())->setId($id)->load();
    $form['#attached']['library'][] = 'simpleads/admin.assets';
    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Campaign Name'),
      '#required'      => TRUE,
      '#description'   => $this->t('This adminstrative name and visible to advertisement editors only.'),
      '#default_value' => $campaigns->getCampaignName(),
    ];
    $form['description'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Description'),
      '#description'   => $this->t('The value of this field only visible to advertisement editors.'),
      '#default_value' => $campaigns->getDescription(),
    ];
    $form = $campaigns->getBuildForm($form, $form_state, $type, $id);
    $form['status'] = [
      '#type'          => 'select',
      '#options'       => $campaigns->getStatuses(),
      '#title'         => $this->t('Status'),
      '#description'   => $this->t('Where to redirect when clicked'),
      '#required'      => TRUE,
      '#default_value' => $campaigns->getStatus(),
    ];
    $form['campaign_type'] = [
      '#type'  => 'hidden',
      '#value' => $type,
    ];
    $form['id'] = [
      '#type'  => 'hidden',
      '#value' => $id,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Update'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type'  => 'link',
      '#title' => $this->t('Cancel'),
      '#url'   => Url::fromRoute('simpleads.campaigns'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('id');
    $type = $form_state->getValue('campaign_type');
    $campaigns = (new Campaigns())->setId($id)->load();
    $options = $campaigns->getOptions(TRUE);
    $options = $campaigns->getSubmitForm('update', $options, $form_state, $type, $id);
    $campaigns
      ->setCampaignName($form_state->getValue('name'))
      ->setDescription($form_state->getValue('description'))
      ->setType($type)
      ->setOptions($options)
      ->setStatus($form_state->getValue('status'))
      ->save();
    $form_state->setRedirect('simpleads.campaigns');
  }

}
