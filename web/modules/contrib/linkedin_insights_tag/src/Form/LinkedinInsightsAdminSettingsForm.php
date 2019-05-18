<?php

namespace Drupal\linkedin_insights_tag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Google_Analytics settings for this site.
 */
class LinkedinInsightsAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkedin_insights_tag_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['linkedin_insights_tag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('linkedin_insights_tag.settings');
    $visibility_user_role_roles = $config->get('user_role_roles');
    $form['linkedin_insights_tag_partner_id'] = [
      '#default_value' => $config->get('partner_id'),
      '#description'   => $this->t('Partner ID provided by Linkedin Insights.'),
      '#maxlength'     => 50,
      '#required'      => TRUE,
      '#size'          => 20,
      '#title'         => $this->t('Partner ID'),
      '#type'          => 'textfield',
    ];

    $form['linkedin_insights_tag_visibility_user_role_roles'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Load Linkedin Insights for following user roles:'),
      '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
      '#options'       => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description'   => $this->t('If none of the roles are selected, all
       users will be tracked.If a user has any of the roles checked, that user
       will be tracked (or excluded, depending on the setting above).'),
    ];

    $form['linkedin_insights_image_only'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Force image usage only'),
      '#description'   => $this->t('Choose this option whenever you want to force
        the tracking to only use the image pixel and not javascript.'),
      '#default_value' => $config->get('image_only'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $form_state->setValue('linkedin_insights_tag_visibility_user_role_roles',
      array_filter($form_state->getValue('linkedin_insights_tag_visibility_user_role_roles')));
    $form_state->setValue('linkedin_insights_tag_partner_id',
      $form_state->getValue('linkedin_insights_tag_partner_id'));
    $form_state->setValue('linkedin_insights_image_only',
      $form_state->getValue('linkedin_insights_image_only'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('linkedin_insights_tag.settings');
    $config->set('user_role_roles',
      $form_state->getValue('linkedin_insights_tag_visibility_user_role_roles'))
      ->set('partner_id', $form_state->getValue('linkedin_insights_tag_partner_id'))
      ->set('image_only', $form_state->getValue('linkedin_insights_image_only'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
