<?php

namespace Drupal\blazyloading\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LazyLoadConfigForm.
 */
class BlazyLoadConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'blazyloading_configuration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blazyloading_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Get config.
    $config = $this->config('blazyloading_configuration.settings');

    $form['blazyloading'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Admin Configuration for BLazy Loading.'),
    ];

    // For blazy loading status.
    $form['blazyloading']['blazy_loading_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Blazy Loading'),
      '#description' => $this->t('Check this if you want to enable lazy loading for your website.'),
      '#default_value' => ($config->get('blazy_loading_status')) ? $config->get('blazy_loading_status') : '',
    ];

    // Roles for which lazy loading will be enabled.
    $form['blazyloading']['blazy_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles for which lazy loading will be enabled'),
      '#default_value' => ($config->get('blazy_roles')) ? $config->get('blazy_roles') : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#states' => ['visible' => [':input[name="blazy_loading_status"]' => ['checked' => TRUE]]],
      '#description' => $this->t('If you select no roles, the condition will evaluate to TRUE for all users.'),
    ];

    // For remove the lazy loading from the entered images.
    $form['blazyloading']['image_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Image URLs'),
      '#description' => $this->t("Enter Image Url by Line separator from which you want to remove lazy loading."),
      '#states' => ['visible' => [':input[name="blazy_loading_status"]' => ['checked' => TRUE]]],
      '#default_value' => $config->get('image_urls'),
    ];

    // For CDN server status.
    $form['blazyloading']['cdn_server_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('CDN Server Status'),
      '#description' => $this->t("Check if you have CDN server."),
      '#states' => ['visible' => [':input[name="blazy_loading_status"]' => ['checked' => TRUE]]],
      '#default_value' => ($config->get('cdn_server_status')) ? $config->get('cdn_server_status') : 0,
    ];

    // For CDN Server URL.
    $form['blazyloading']['cdn_server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN Server URL'),
      '#description' => $this->t("Enter CDN Server URL from which you want to pass the images with height and width parameter.
        Below I have mention the demo of cloundinary server as the CDN Server<br>
        https://res.cloudinary.com/fen-learning/image/fetch/c_limit,w_cdn_server_width,h_cdn_server_height/source_image_url<br>
        In the CDN server below are must variable which will replace the at the lazy loading<br>
        1. w_cdn_server_width: For Width
        2. w_cdn_server_height: For Height
        3. source_image_url: Image URL which contain the HTTP or HTTPS."),
      '#states' => [
        'visible' => [':input[name="cdn_server_status"]' => ['checked' => TRUE]],
        'required' => [':input[name="cdn_server_status"]' => ['checked' => TRUE]],
      ],
      '#default_value' => ($config->get('cdn_server_url')) ? $config->get('cdn_server_url') : '',
    ];

    // Loading Icon Status.
    $form['blazyloading']['loading_icon_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Loader Images'),
      '#states' => ['visible' => [':input[name="blazy_loading_status"]' => ['checked' => TRUE]]],
      '#default_value' => ($config->get('loading_icon_status')) ? $config->get('loading_icon_status') : 0,
    ];

    // Loading Image.
    $imageArray = [];
    if ($config->get('loading_icon_file')) {
      $imageArray = [$config->get('loading_icon_file')];
    }
    $form['blazyloading']['loading_icon_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Loading Icon'),
      '#upload_location' => 'public://images/',
      '#default_value' => $imageArray,
      '#description' => $this->t('A Loading Icon.'),
      '#states' => [
        'visible' => [':input[name="loading_icon_status"]' => ['checked' => TRUE]],
      ],
    ];

    // Class for Image at the time of loading.
    $form['blazyloading']['css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS Class'),
      '#default_value' => ($config->get('css_class')) ? $config->get('css_class') : '',
      '#states' => ['visible' => [':input[name="blazy_loading_status"]' => ['checked' => TRUE]]],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* Fetch the array of the file stored temporarily in database */
    $image = $form_state->getValue('loading_icon_file');
    $fid = '';
    if ($image) {
      $fid = $image[0];
    }
    // Store blazy loading setting.
    $roles = [];
    foreach ($form_state->getValue('blazy_roles') as $key => $value) {
      if ($value) {
        $roles[$key] = $value;
      }
    }
    $this->config('blazyloading_configuration.settings')
      ->set('blazy_loading_status', $form_state->getValue('blazy_loading_status'))
      ->set('image_urls', $form_state->getValue('image_urls'))
      ->set('cdn_server_status', $form_state->getValue('cdn_server_status'))
      ->set('cdn_server_url', $form_state->getValue('cdn_server_url'))
      ->set('loading_icon_status', $form_state->getValue('loading_icon_status'))
      ->set('blazy_roles', $roles)
      ->set('loading_icon_file', $fid)
      ->set('css_class', $form_state->getValue('css_class'))
      ->save();
    drupal_set_message($this->t('BLazy Loading setting is saved.'));
  }

}
