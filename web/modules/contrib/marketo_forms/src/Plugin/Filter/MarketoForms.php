<?php
/**
 * @file
 * Contains Drupal\marketo_forms\Plugin\Filter\MarketoForms
 */

namespace Drupal\marketo_forms\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Render Marketo Forms.
 *
 * @Filter(
 *   id = "marketo_forms",
 *   title = @Translation("Marketo Forms"),
 *   description = @Translation("Substitutes [marketo-forms:FORM_ID] with embedded marketo forms."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class MarketoForms extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (preg_match_all('/\[marketo\-form(\:(.+))?( .+)?\]/isU', $text, $matches_code)) {
      foreach ($matches_code[0] as $ci => $code) {
        $form = [
          'form_id'   => $matches_code[2][$ci],
        ];
        // Override default attributes.
        if (!empty($matches_code[3][$ci]) && preg_match_all('/\s+([a-zA-Z_]+)\:(\s+)?([0-9a-zA-Z\/]+)/i', $matches_code[3][$ci], $matches_attributes)) {
          foreach ($matches_attributes[0] as $ai => $attribute) {
            $form[$matches_attributes[1][$ai]] = $matches_attributes[3][$ai];
          }
        }
        $config = \Drupal::config('marketo_forms.settings');
        $host = $config->get('marketo_host_key');
        $api_key = $config->get('marketo_api_key');
        $element = [
          '#theme' => 'marketo_form',
          '#host' => $host,
          '#api_key' => $api_key,
          '#form_id' => $form['form_id'],
        ];
        $replacement = drupal_render($element);
        $text = str_replace($code, $replacement, $text);
      }
    }
    return new FilterProcessResult( $text );
  }

}
