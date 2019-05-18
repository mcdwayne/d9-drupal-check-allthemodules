<?php

namespace Drupal\drulma_companion\Plugin\Block;

use Drupal\Core\Menu\Plugin\Block\LocalTasksBlock;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Tabs" block to display the local tasks.
 *
 * @Block(
 *   id = "drulma_companion_local_tasks_block",
 *   admin_label = @Translation("Bulma Tabs"),
 *   category = @Translation("Bulma")
 * )
 */
class Tabs extends LocalTasksBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'horizontally_centered' => TRUE,
      'fullwidth' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['horizontally_centered'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Center tabs horizontally'),
      '#default_value' => $this->configuration['horizontally_centered'],
      '#description' => $this->t('Use a .container for the tabs content. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/layout/container/',
      ]),
    ];

    $form['fullwidth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make tabs occupy the available width'),
      '#default_value' => $this->configuration['fullwidth'],
      '#description' => $this->t('Make the tabs use the full width. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/components/tabs/',
      ]),
    ];

    $form += parent::blockForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['horizontally_centered'] = $form_state->getValue('horizontally_centered');
    $this->configuration['fullwidth'] = $form_state->getValue('fullwidth');
  }

}
