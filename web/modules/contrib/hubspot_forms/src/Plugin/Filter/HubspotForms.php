<?php
/**
 * @file
 * Contains Drupal\hubspot_forms\Plugin\Filter\HubspotForms
 */

namespace Drupal\hubspot_forms\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Render Hubspot Forms.
 *
 * @Filter(
 *   id = "hubspot_forms",
 *   title = @Translation("Hubspot Forms"),
 *   description = @Translation("Substitutes [hubspot-forms:FORM_ID portal_id:PORTAL_ID] with embedded hubspot forms."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class HubspotForms extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (preg_match_all('/\[hubspot\-form(\:(.+))?( .+)?\]/isU', $text, $matches_code)) {
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
        $element = [
          '#theme' => 'hubspot_form',
          '#portal_id' => $form['portal_id'],
          '#form_id' => $form['form_id'],
        ];
        $replacement = \Drupal::service('renderer')->render($element);
        $text = str_replace($code, $replacement, $text);
      }
    }
    return new FilterProcessResult( $text );
  }

}
