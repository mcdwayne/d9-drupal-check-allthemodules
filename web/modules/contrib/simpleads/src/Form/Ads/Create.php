<?php

namespace Drupal\simpleads\Form\Ads;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Ads;
use Drupal\simpleads\Groups;
use Drupal\simpleads\Campaigns;

/**
 * New advertisement form.
 */
class Create extends FormBase {

  /**
   * Set page title.
   */
  public function setTitle($type = NULL) {
    $ads = new Ads();
    return $this->t('Create new <em>@type</em>', ['@type' => $ads->getName($type)]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL) {
    $ads = new Ads();
    $form['#attached']['library'][] = 'simpleads/admin.assets';
    $form['name'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Advertisement Name'),
      '#required'    => TRUE,
      '#description' => $this->t('This adminstrative name and visible to advertisement editors only'),
    ];
    $form['description'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Description'),
      '#description' => $this->t('The value of this field only visible to advertisement editors'),
    ];
    $form['url'] = [
      '#type'        => 'url',
      '#title'       => $this->t('Redirect URL'),
      '#description' => $this->t('Where to redirect when clicked'),
    ];
    $form['url_target'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Open Redirect URL in a new window'),
    ];
    $form = $ads->getBuildForm($form, $form_state, $type);
    $form['group_id'] = [
      '#type'        => 'select',
      '#options'     => (new Groups())->loadAsOptions(),
      '#title'       => $this->t('Advertisement Group'),
      '#description' => $this->t('Where to redirect when clicked'),
    ];
    $form['campaign_id'] = [
      '#type'        => 'select',
      '#options'     => (new Campaigns())->loadAsOptions(),
      '#title'       => $this->t('Advertisement Campaign'),
      '#description' => $this->t('Where to redirect when clicked'),
    ];
    $form['status'] = [
      '#type'        => 'select',
      '#options'     => $ads->getStatuses(),
      '#title'       => $this->t('Status'),
      '#description' => $this->t('Where to redirect when clicked'),
      '#required'    => TRUE,
    ];
    $form['ad_type'] = [
      '#type'  => 'hidden',
      '#value' => $type,
    ];
    $form['ad_op'] = [
      '#type'  => 'hidden',
      '#value' => 'create',
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
      '#url'   => Url::fromRoute('simpleads.ads'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ads = new Ads();
    $type = $form_state->getValue('ad_type');
    $options['url'] = $form_state->getValue('url');
    $options['url_target'] = $form_state->getValue('url_target');
    $options = $ads->getSubmitForm('create', $options, $form_state, $type);
    $ads->setAdName($form_state->getValue('name'))
      ->setDescription($form_state->getValue('description'))
      ->setType($type)
      ->setGroup((new Groups)->setId($form_state->getValue('group_id'))->load())
      ->setCampaign((new Campaigns)->setId($form_state->getValue('campaign_id'))->load())
      ->setOptions($options)
      ->setStatus($form_state->getValue('status'))
      ->save();
    $form_state->setRedirect('simpleads.ads');
  }

}
