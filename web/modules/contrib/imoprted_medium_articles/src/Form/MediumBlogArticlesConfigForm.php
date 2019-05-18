<?php

namespace Drupal\medium_blog_articles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form.
 */
class MediumBlogArticlesConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'medium_blog_articles_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get values from config file.
    $config = $this->config('medium_blog_articles.settings');

    $form = parent::buildForm($form, $form_state);
    $form['publication_name'] = [
      '#type' => 'url',
      '#title' => $this->t("Publication's Name"),
      '#size' => 50,
      '#default_value' => $config->get('medium_blog_articles.publication_name'),
      '#description' => t("The Medium Publication's URL."),
      '#required' => TRUE,
    ];
    $form['articles_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Articles Count'),
      '#size' => 5,
      '#default_value' => $config->get('medium_blog_articles.articles_count'),
      '#description' => t('The number of articles you want to display.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get values from config file.
    $this->config('medium_blog_articles.settings')
      ->set('medium_blog_articles.publication_name', $form_state->getValue('publication_name'))
      ->set('medium_blog_articles.articles_count', $form_state->getValue('articles_count'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'medium_blog_articles.settings',
    ];
  }

}
