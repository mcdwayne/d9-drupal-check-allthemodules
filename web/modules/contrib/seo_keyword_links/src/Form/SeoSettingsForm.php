<?php

namespace Drupal\seo_keyword_links\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SeoSettingsForm.
 *
 * @package Drupal\seo_keyword_links\Form
 *
 * Functional tests for the Seo Keywords Links module.
 */
class SeoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'seo_keyword_links.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'seo_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('seo_keyword_links.settings');
    $form['links'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Links'),
      '#default_value' => $this->walkLinks($config->get('links')),
      '#description' => $this->t('Please, enter each line with the following format
          "Word -- http://mylink.com"'),
    ];

    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#options' => $contentTypesList,
      '#title' => $this->t('Node types'),
      '#default_value' => $this->config('seo_keyword_links.settings')->get("node_types"),
      '#description' => $this->t('Select the content type to apply the desired behaviour. Clear the cache after changing this.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $_ = [];
    foreach ($form_state->getValue('node_types') as $content_type) {
      if ($content_type != "0") {
        $_[] = $content_type;
      }
    }

    $this->config('seo_keyword_links.settings')

      ->set(
            'links',
            $this->codeLinks(
                explode(
                    "\n",
                    $form_state->getValue('links')
                )
            )
        )

      ->set('node_types', $_)

      ->save();
  }

  /**
   * Inner function.
   */
  private function walkLinks($arr) {
    $_ = "";
    foreach ($arr as $key => $val) {
      $_ .= $key . " -- " . $val . "\n";
    }
    return $_;
  }

  /**
   * Inner function.
   */
  private function codeLinks($arr) {
    $_ = [];
    foreach ($arr as $val) {
      $a = @explode(" -- ", $val);
      if (@$a[0] && @$a[1]) {
        $_[$a[0]] = $a[1];
      }
    }
    return $_;
  }

}
