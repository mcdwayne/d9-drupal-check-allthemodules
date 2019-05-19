<?php

namespace Drupal\wordfilter\Plugin;

use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Component\Utility\Xss;
use \Drupal\Component\Plugin\PluginBase;
use \Drupal\wordfilter\Entity\WordfilterConfigurationInterface;

/**
 * Base class for Wordfilter Process plugins.
 */
abstract class WordfilterProcessBase extends PluginBase implements WordfilterProcessInterface {
  /**
   * Prepares the filter words for being used inside a regular expression.
   * 
   * @param array $words
   *   The (not yet) prepared filter words.
   * 
   * @return array
   *   The filter words, ready to be used inside a regular expression.
   */
  protected function prepareWordsForRegex(array $words) {
    $prepared = [];
    foreach ($words as $delta => $word) {
      $prepared[$delta] = preg_quote(Xss::filterAdmin($word), '/');
      if (strlen($prepared[$delta]) === strlen($word)) {
        // Use word boundaries for 'naturally spoken' words.
        $prepared[$delta] = '\b' . $prepared[$delta] . '\b';
      }
    }
    return $prepared;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, WordfilterConfigurationInterface $wordfilter_config) {
    $definition = $this->getPluginDefinition();
    return [
      'description' => [
        '#markup' => '<em>' . t('Process description') . '</em>: ' . $definition['description'],
        '#weight' => 0,
      ]];
  }
}
