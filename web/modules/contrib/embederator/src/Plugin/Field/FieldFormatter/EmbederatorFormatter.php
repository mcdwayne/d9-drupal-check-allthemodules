<?php

namespace Drupal\embederator\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the default embederator (token replacing) formatter.
 *
 * @FieldFormatter(
 *   id = "embederator_default",
 *   module = "embederator",
 *   label = @Translation("Embederator"),
 *   field_types = {
 *     "string"
 *   }
 * );
 */
class EmbederatorFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /* @todo: inject these services */
    $token = \Drupal::service('token');
    $entity_manager = \Drupal::service('entity_type.manager');
    $client = \Drupal::service('http_client');

    // Get the embed type markup.
    $entity = $items->getEntity();
    $embederator_type = $entity_manager->getStorage('embederator_type')->load($entity->getType());

    if ($embederator_type->getUseSsi()) {
      $url_pattern = $embederator_type->getEmbedUrl();
      $elements = [];
      foreach ($items as $delta => $item) {
        $url = $token->replace($url_pattern, ['embederator' => $entity]);
        // hook_embederator_url_alter(&$url, $embederator_type, $entity).
        \Drupal::moduleHandler()->alter('embederator_url', $url, $embederator_type, $entity);
        try {
          $response = $client->request('GET', $url);
          $markup = (string) $response->getBody();
          $markup = $this->uniquify($markup);
        }
        catch (Exception $e) {
          $markup = '<p>Unable to load ' . $url . '</p>';
        }
        // hook_embederator_embed_alter(&$html, $embederator_type, $entity).
        \Drupal::moduleHandler()->alter('embederator_embed', $markup, $embederator_type, $entity);
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $markup,
          '#format' => 'full_html',
        ];
      }
    }
    else {
      $embed_pattern_field = $embederator_type->getMarkup();

      $elements = [];
      foreach ($items as $delta => $item) {
        $markup = $token->replace($embed_pattern_field['value'], ['embederator' => $entity]);
        $markup = $this->uniquify($markup);
        // hook_embederator_embed_alter(&$html, $embederator_type, $entity).
        \Drupal::moduleHandler()->alter('embederator_embed', $markup, $embederator_type, $entity);
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $markup,
          '#format' => $embed_pattern_field['format'],
        ];
      }
    }

    if ($this->getSetting('nullify_cache')) {
      $elements['#cache']['max-age'] = 0;
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Limit to embederator.
    return (method_exists($field_definition, 'getProvider')
            && ($field_definition->getProvider() == 'embederator')
            && method_exists($field_definition, 'getName')
            && ($field_definition->getName() == 'embed_id'));
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('append_unique_id')) {
      $summary[] = $this->t('Append unique hash to form DOM IDs.');
    }
    if ($this->getSetting('nullify_cache')) {
      $summary[] = $this->t('Zero cache.');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'append_unique_id' => FALSE,
      'nullify_cache' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['append_unique_id'] = [
      '#title' => $this->t('Append unique hash to form input DOM IDs'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('append_unique_id'),
    ];

    $element['nullify_cache'] = [
      '#title' => $this->t('Force 0 max-age cache'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('nullify_cache'),
    ];

    return $element;
  }

  /**
   * Add a random suffix to ID attributes in DOM markup.
   *
   * See e.g., https://api.drupal.org/api/drupal/core%21modules%21filter%21src%21Plugin%21Filter%21FilterHtml.php/function/FilterHtml%3A%3AfilterAttributes/8.2.x.
   */
  protected function uniquify($html) {
    if ($this->getSetting('append_unique_id')) {
      $suffix = uniqid();

      // Collect all the IDs and make their replacements.
      $html_dom = Html::load($html);
      $xpath = new \DOMXPath($html_dom);
      foreach ($xpath->query('//*[@id]') as $element) {
        // Only form inputs.
        if (!$element->hasAttribute('name')) {
          continue;
        }
        $orig_id = $element->getAttribute('id');
        $new_id = $orig_id . "-" . $suffix;
        foreach ($xpath->query('//*[@for="' . $orig_id . '"]') as $for_element) {
          $for_element->setAttribute('for', $new_id);
        }
        $element->setAttribute('id', $new_id);
      }

      $text = Html::serialize($html_dom);
      $html = trim($text);
    }

    // Run the replacements on the markup.
    return $html;
  }

}
