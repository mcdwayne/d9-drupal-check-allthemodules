<?php
namespace Drupal\cincopa\Plugin\Filter;


use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "filter_cincopa",
 *   title = @Translation("Parse Cincopa Tags"),
 *   description = @Translation("Parse the Cincopa tag and replace it with the gallery code."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterCincopa extends FilterBase {
  public $arr_unique = array();

  public function process($text, $langcode) {
    $text = preg_replace_callback("/\[cincopa ([[:print:]]+?)\]/", function($m) {
                                          $unique = uniqid('');
                                          array_push($m, $unique);
                                          array_push($this->arr_unique, $m);
                                          return '<div id="_cp_widget_' . $unique . '">...</div>';
                                      },
                                  $text);

    $result = new FilterProcessResult($text);
    $result->setProcessedText($text)
           ->addAttachments(['library' => ['cincopa/cincopa.filter.main']]);

    $args = array();
    foreach($this->arr_unique as $index => $match) {
      $args['cincopa'][$match[2]]['arg0'] = $match[0];
      $args['cincopa'][$match[2]]['arg1'] = $match[1];
      $args['cincopa'][$match[2]]['arg2'] = "_cp_widget_" . $match[2];
    }
    
    $result->addAttachments(['library' => ['cincopa/cincopa.filter'], 'drupalSettings' => $args]);
    return $result;
  }
}