<?php

namespace Drupal\wordfilter\Plugin\WordfilterProcess;

use \Drupal\Core\Language\LanguageInterface;
use \Drupal\Component\Utility\Xss;
use \Drupal\wordfilter\Entity\WordfilterConfigurationInterface;
use \Drupal\wordfilter\Plugin\WordfilterProcessBase;

/**
 * @WordfilterProcess(
 *   id = "default",
 *   label = @Translation("Direct substitution (default)"),
 *   description = @Translation("All specified filter words will be directly replaced with the specified substitution text by using a simple and automatically generated regular expression."),
 * )
 */
class DefaultWordfilterProcess extends WordfilterProcessBase {
  /**
   * {@inheritdoc}
   */
  public function filterWords($text, WordfilterConfigurationInterface $wordfilter_config, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    foreach ($wordfilter_config->getItems() as $item) {
      $filter_words = $item->getFilterWords();
      $filter_words = $this->prepareWordsForRegex($filter_words);

      $filter_pattern = '/' . implode('|', $filter_words) . '/i';
      $substitute = Xss::filterAdmin($item->getSubstitute());

      $text = preg_replace($filter_pattern, $substitute, $text);
    }
    return $text;
  }
}
