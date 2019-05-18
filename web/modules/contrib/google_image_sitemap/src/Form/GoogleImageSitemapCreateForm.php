<?php

namespace Drupal\google_image_sitemap\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

/**
 * Provides a form to create new sitemap.
 */
class GoogleImageSitemapCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_image_sitemap_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $edit = NULL) {
    $form = [];
    // Get all node types, and add an All option.
    $node_types = array_merge(['all' => $this->t('--All--')], node_type_get_names());
    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Content Type'),
      '#description' => $this->t('Select the content type for which you want to generate image sitemap.'),
      '#options' => $node_types,
      '#default_value' => !empty($edit->node_type) ? $edit->node_type : '',
      '#required' => TRUE,
    ];
    $form['license'] = [
      '#type' => 'textfield',
      '#title' => $this->t('License url'),
      '#default_value' => !empty($edit->license) ? $edit->license : '',
      '#description' => $this->t('An absolute url to the license agreement of the image.'),
    ];
    $form['buttons']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('google_image_sitemap.list'),
    ];
    if ($edit) {
      $del = 'admin/config/search/google_image_sitemap/delete/' . $edit->sid;
      $form['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#attributes' => ['class' => 'button button--danger'],
        '#url' => Url::fromUri('internal:/' . $del),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for valid url.
    if ($form_state->getValue(['license']) && !UrlHelper::isValid($form_state->getValue(['license']), TRUE)) {
      $form_state->setErrorByName('license', $this->t('License should be a valid url.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sitemap_obj = (object) $form_state->getValues();
    $sitemap_id = \Drupal::routeMatch()->getRawParameter('sitemap_id');
    // Set created key if new.
    if (empty($sitemap_id)) {
      $sitemap['created'] = REQUEST_TIME;
    }
    $sitemap['node_type'] = $sitemap_obj->node_type;
    $sitemap['license'] = $sitemap_obj->license;
    $sitemap['last_updated'] = REQUEST_TIME;
    $sitemap_obj->sid = $form_state->getStorage();
    \Drupal::database()->merge('google_image_sitemap')->fields($sitemap)->condition('sid', $sitemap_id)->execute();
    // Redirect to main page of sitemap.
    $form_state->setRedirect('google_image_sitemap.list');
  }

}
