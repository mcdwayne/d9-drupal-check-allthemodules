<?php

namespace Drupal\drupal8breaks\Plugin\Filter;

use Drupal\filter\Plugin\Filter\FilterHtml;

class FilterHtmlDrupal8Breaks extends FilterHtml {

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    $restrictions = parent::getHTMLRestrictions();
    $restrictions['allowed']['drupal8break'] = false;
    return $restrictions;
  }
    
  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Replace <!--break--> with a custom HTML tag.
    $text = str_replace('<!--break-->', '<drupal8break>', $text);
    $result = parent::process($text, $langcode);
    $text = $result->getProcessedText();
    $text = str_replace('<drupal8break>', '<!--break-->', $text);
    $result->setProcessedText($text);
    return $result;
  }

}
