<?php

namespace Drupal\cacheexclude\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cacheexclude_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cacheexclude.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cacheexclude.settings');

    $form['cacheexclude_list'] = [
      '#type' => 'textarea',
      '#title' => t('Pages to exclude from caching'),
      '#default_value' => $config->get('cacheexclude_list'),
      '#width' => 40,
      '#height' => 10,
      '#description' => $this->t("Enter one page per line as Drupal paths. Begin link with trailing slash. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", ['%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>']),
    ];

    $form['cacheexclude_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Content types to exclude from caching'),
      '#default_value' => $config->get('cacheexclude_node_types') ? $config->get('cacheexclude_node_types') : [],
      '#options' => node_type_get_names(),
      '#description' => $this->t("Check all content types that you do not want to be cached."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear the page cache when new settings are added.
    drupal_flush_all_caches();

    $config = \Drupal::service('config.factory')->getEditable('cacheexclude.settings');
    $config->set('cacheexclude_list', $form_state->getValue('cacheexclude_list'))->save();
    $config->set('cacheexclude_node_types', $form_state->getValue('cacheexclude_node_types'))->save();
    parent::submitForm($form, $form_state);
  }

}
