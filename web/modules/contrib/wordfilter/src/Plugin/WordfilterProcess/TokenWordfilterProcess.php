<?php

namespace Drupal\wordfilter\Plugin\WordfilterProcess;

use \Drupal\Core\Language\LanguageInterface;
use \Drupal\Component\Utility\Xss;
use \Drupal\wordfilter\Entity\WordfilterConfigurationInterface;
use \Drupal\wordfilter\Plugin\WordfilterProcessBase;

/**
 * @WordfilterProcess(
 *   id = "token",
 *   label = @Translation("Token substitution"),
 *   description = @Translation("Similar to the default process, all specified filter words will be directly replaced with the substitution text. You may additionally use <a href=':url' target='_blank'>tokens</a> inside of the substitution text.", arguments = {":url" = "https://www.drupal.org/node/390482"}),
 * )
 */
class TokenWordfilterProcess extends WordfilterProcessBase {
  /**
   * {@inheritdoc}
   */
  public function filterWords($text, WordfilterConfigurationInterface $wordfilter_config, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    foreach ($wordfilter_config->getItems() as $item) {
      $filter_words = $item->getFilterWords();
      $filter_words = $this->prepareWordsForRegex($filter_words);

      $filter_pattern = '/' . implode('|', $filter_words) . '/i';
      $substitute = Xss::filterAdmin($item->getSubstitute());
      $substitute = \Drupal::token()->replace($substitute);

      $text = preg_replace($filter_pattern, $substitute, $text);
    }
    return $text;
  }
}
