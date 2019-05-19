<?php

namespace Drupal\simpleads\Form\Ads;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Ads;
use Drupal\simpleads\Groups;
use Drupal\simpleads\Campaigns;

/**
 * Edit advertisement form.
 */
class Edit extends FormBase {

  /**
   * Set page title.
   */
  public function setTitle($type = NULL, $id = NULL) {
    $ad = (new Ads())->setId($id)->load();
    return $this->t('Edit <em>@name</em>', ['@name' => $ad->getAdName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $ads = (new Ads())->setId($id)->setType($type)->load();
    $form['#attached']['library'][] = 'simpleads/admin.assets';
    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Advertisement Name'),
      '#required'      => TRUE,
      '#description'   => $this->t('This adminstrative name and visible to advertisement editors only'),
      '#default_value' => $ads->getAdName(),
    ];
    $form['description'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Description'),
      '#description'   => $this->t('The value of this field only visible to advertisement editors'),
      '#default_value' => $ads->getDescription(),
    ];
    $form['url'] = [
      '#type'          => 'url',
      '#title'         => $this->t('Redirect URL'),
      '#description'   => $this->t('Where to redirect when clicked'),
      '#default_value' => (!empty($ads->getOptions(TRUE)['url']) && $url = $ads->getOptions(TRUE)['url']) ? $url : '',
    ];
    $form['url_target'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Open Redirect URL in a new window'),
      '#default_value' => (!empty($ads->getOptions(TRUE)['url_target']) && $url_target = $ads->getOptions(TRUE)['url_target']) ? $url_target : FALSE,
    ];
    $form = $ads->getBuildForm($form, $form_state, $type, $id);
    $form['group_id'] = [
      '#type'          => 'select',
      '#options'       => (new Groups())->loadAsOptions(),
      '#title'         => $this->t('Advertisement Group'),
      '#description'   => $this->t('Where to redirect when clicked'),
      '#default_value' => $ads->getGroup()->getId(),
    ];
    $form['campaign_id'] = [
      '#type'          => 'select',
      '#options'       => (new Campaigns())->loadAsOptions(),
      '#title'         => $this->t('Advertisement Campaign'),
      '#description'   => $this->t('Where to redirect when clicked'),
      '#default_value' => $ads->getCampaign()->getId(),
    ];
    $form['status'] = [
      '#type'          => 'select',
      '#options'       => $ads->getStatuses(),
      '#title'         => $this->t('Status'),
      '#description'   => $this->t('Where to redirect when clicked'),
      '#required'      => TRUE,
      '#default_value' => $ads->getStatus(),
    ];
    $form['ad_type'] = [
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
      '#url'   => Url::fromRoute('simpleads.ads'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('id');
    $ads = (new Ads())->setId($id)->load();
    $type = $form_state->getValue('ad_type');
    $options = $ads->getOptions(TRUE);
    $options['url'] = $form_state->getValue('url');
    $options['url_target'] = $form_state->getValue('url_target');
    $options = $ads->getSubmitForm('update', $options, $form_state, $type, $id);

    $ads->setAdName($form_state->getValue('name'))
      ->setDescription($form_state->getValue('description'))
      ->setType($type)
      ->setGroup((new Groups)->setId($form_state->getValue('group_id'))->load())
      ->setCampaign((new Campaigns)->setId($form_state->getValue('campaign_id'))->load())
      ->setOptions($options)
      ->setStatus($form_state->getValue('status'))
      ->setChangedAt()
      ->save();

    $form_state->setRedirect('simpleads.ads');
  }

}
