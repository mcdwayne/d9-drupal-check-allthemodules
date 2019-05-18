<?php

namespace Drupal\detailsfilter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\filter\Plugin\FilterBase;


/**
 * @Filter(
 *  id = "detailsfilter",
 *  title = @Translation("Details Filter."),
 *  description = @Translation("Provides a filter [details open: Name] Text...[/details]."),
 *  type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class DetailsFilter extends FilterBase implements  FilterInterface {
      /**
      * {@inheritdoc}
      */
      public function process($text, $langcode) {
        $pattern = '#[[]details(?<open> +open)? *(: *(?<subject>.*?))?](?<text>.*?)[[]/details]#usm';
        $result = new FilterProcessResult('');
        $callback = function ($matches) use ($result) {
          $render = [
            '#type' => 'details',
            'text' => ['#markup' => $matches['text']]
          ];
          if (!empty($matches['open'])) {
            $render['#open'] = TRUE;
          }
          if (!empty($matches['title'])) {
            $render['#title'] = $matches['title'];
          }

          $return = render($render);
          // Rendering will always populate this and all attachments will bubble
          // up to the top level.
          $result->addAttachments($render['#attached']);
          return $return;
        };
        $new_text = preg_replace_callback($pattern, $callback, $text);
        $result->setProcessedText($new_text);
        return $result;
      }
}
