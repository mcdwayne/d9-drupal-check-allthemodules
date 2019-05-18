<?php

namespace Drupal\breadcrumb_manager_context\Plugin\ContextReaction;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content reaction that sets a breadcrumb title.
 *
 * @ContextReaction(
 *   id = "breadcrumb",
 *   label = @Translation("Breadcrumb")
 * )
 */
class Breadcrumb extends ContextReactionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'breadcrumb' => '',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->getConfiguration()['breadcrumb'];
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$vars = []) {
    return $this->getConfiguration()['breadcrumb'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['breadcrumb'] = [
      '#title' => $this->t('Breadcrumb title'),
      '#type' => 'textfield',
      '#description' => $this->t('Provides this text as breadcrumb title for the given page.'),
      '#default_value' => $this->getConfiguration()['breadcrumb'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'breadcrumb' => $form_state->getValue('breadcrumb'),
    ]);
  }

}
