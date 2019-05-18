<?php

/**
 * @file
 * Contains \Drupal\quick_pages\Plugin\QuickPages\MainContent\Text.
 */

namespace Drupal\quick_pages\Plugin\QuickPages\MainContent;

use Drupal\quick_pages\MainContentBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows to use static text as main content.
 *
 * @MainContent(
 *   id = "text",
 *   title = @Translation("Text"),
 * )
 */
class Text extends MainContentBase {

  /**
   * {@inheritdoc}
   */
  public function getMainContent() {
    return [
      '#type' => 'processed_text',
      '#text' => $this->configuration['content']['value'],
      '#format' => $this->getFormat(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['content'] = [
      '#title' => t('Content'),
      '#type' => 'text_format',
      '#default_value' => $this->configuration['content']['value'],
      '#rows' => 10,
      '#format' => $this->getFormat(),
    ];

    return $form;
  }

  /**
   * Returns content format.
   */
  protected function getFormat() {
    return isset($this->configuration['content']['format']) ?
      $this->configuration['content']['format'] : filter_default_format();
  }

}
