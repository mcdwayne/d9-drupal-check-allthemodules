<?php

namespace Drupal\simpleads\Form\Campaigns;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Campaigns;

/**
 * New advertisement campaign form.
 */
class Create extends FormBase {

  /**
   * Set page title.
   */
  public function setTitle($type = NULL) {
    $campaigns = new Campaigns();
    return $this->t('Create new <em>@type</em>', ['@type' => $campaigns->getName($type)]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_campaign_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $campaigns = new Campaigns();
    $form['#attached']['library'][] = 'simpleads/admin.assets';
    $form['name'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Campaign Name'),
      '#required'    => TRUE,
      '#description' => $this->t('This adminstrative name and visible to advertisement editors only.'),
    ];
    $form['description'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Description'),
      '#description' => $this->t('The value of this field only visible to advertisement editors.'),
    ];
    $form = $campaigns->getBuildForm($form, $form_state, $type);
    $form['status'] = [
      '#type'        => 'select',
      '#options'     => $campaigns->getStatuses(),
      '#title'       => $this->t('Status'),
      '#description' => $this->t('Where to redirect when clicked'),
      '#required'    => TRUE,
    ];
    $form['campaign_type'] = [
      '#type'  => 'hidden',
      '#value' => $type,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Create'),
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
    $campaigns = new Campaigns();
    $type = $form_state->getValue('campaign_type');
    $options = [];
    $options = $campaigns->getSubmitForm('create', $options, $form_state, $type);
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
