<?php

namespace Drupal\library_select_context\Plugin\ContextReaction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\context\ContextReactionPluginBase;

/**
 * Provides a content reaction that will let you add custom library website.
 *
 * @ContextReaction(
 *   id = "library_select",
 *   label = @Translation("Library Select")
 * )
 */
class LibrarySelect extends ContextReactionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'library_select' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->getConfiguration()['library_select'];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->getConfiguration()['library_select'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['library_select'] = [
      '#title' => $this->t('Library'),
      '#type' => 'select',
      '#options' => library_select_options_callback(),
      '#description' => $this->t('Select library attach to page with context.'),
      '#default_value' => $this->getConfiguration()['library_select'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'library_select' => $form_state->getValue('library_select'),
    ]);
  }

}
