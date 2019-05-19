<?php

namespace Drupal\social_feeds_block\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure custom settings for this site.
 */
class InstagramSocialFeedsBlockForm extends ConfigFormBase {

  /**
   * Constructor for SocialFeedsBlockForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {

    parent::__construct($config_factory);

  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'instagram_social_feeds_admin_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.instagram_social_feeds_block'];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $instagram_social_feeds = $this->config('config.instagram_social_feeds_block');

    // Facebook fieldset.
    $form['instagram_social_feeds'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Instagram Cridential'),
      '#weight' => 50,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $form['instagram_social_feeds']['insta_client_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $instagram_social_feeds->get('insta_client_id'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['instagram_social_feeds']['insta_redirec_uri'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#default_value' => $instagram_social_feeds->get('insta_redirec_uri'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['instagram_social_feeds']['insta_access_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $instagram_social_feeds->get('insta_access_token'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['instagram_social_feeds']['insta_image_resolution'] = array(
      '#type' => 'select',
      '#title' => $this->t('Image Resoluction'),
      '#options' => array(
        'thumbnail' => $this->t('Thumbnail'),
        'low_resolution' => $this->t('Low Resolution'),
        'standard_resolution' => $this->t('Standard Resolution'),
      ),
      '#default_value' => $instagram_social_feeds->get('insta_image_resolution'),
    // '#maxlength' => 255,.
      '#required' => TRUE,
    );

    $form['instagram_social_feeds']['insta_likes'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Likes Count'),
      '#default_value' => $instagram_social_feeds->get('insta_likes'),
    // '#maxlength' => 255,
    // '#required' => TRUE,.
    );

    $form['instagram_social_feeds']['insta_pic_counts'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Picture Count'),
      '#default_value' => $instagram_social_feeds->get('insta_pic_counts'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('config.instagram_social_feeds_block')
      ->set('insta_client_id', $form_state->getValue('insta_client_id'))
      ->set('insta_redirec_uri', $form_state->getValue('insta_redirec_uri'))
      ->set('insta_access_token', $form_state->getValue('insta_access_token'))
      ->set('insta_pic_counts', $form_state->getValue('insta_pic_counts'))
      ->set('insta_image_resolution', $form_state->getValue('insta_image_resolution'))
      ->set('insta_likes', $form_state->getValue('insta_likes'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
