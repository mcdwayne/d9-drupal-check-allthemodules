<?php

namespace Drupal\wordfilter\Plugin;

use \Drupal\Core\Language\LanguageInterface;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Component\Plugin\PluginInspectionInterface;
use \Drupal\wordfilter\Entity\WordfilterConfigurationInterface;

/**
 * Defines an interface for Wordfilter Process plugins.
 */
interface WordfilterProcessInterface extends PluginInspectionInterface {

  /**
   * Provides filtering of words by the given Wordfilter configuration.
   *
   * @param string $text
   *  The text to filter.
   * @param \Drupal\wordfilter\Entity\WordfilterConfigurationInterface $wordfilter_config
   *  The Wordfilter configuration to use during the filtering process.
   * @param string $langcode
   *  (Optional) The language code.
   *
   * @return string
   *   The filtered text.
   */
  public function filterWords($text, WordfilterConfigurationInterface $wordfilter_config, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED);
  
  /**
   * Additional settings form according to the given Wordfilter configuration.
   * 
   * @param array $form
   *   The corresponding form builder array.
   * @param FormStateInterface $form_state
   *   The form state.
   * @param WordfilterConfigurationInterface $wordfilter_config
   *   The corresponding Wordfilter configuration object.
   * 
   * @return
   *   A renderable form array.
   */
  public function settingsForm(array $form, FormStateInterface $form_state, WordfilterConfigurationInterface $wordfilter_config);
}
