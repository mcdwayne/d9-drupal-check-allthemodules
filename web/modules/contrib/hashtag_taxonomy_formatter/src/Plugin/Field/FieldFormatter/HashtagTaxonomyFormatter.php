<?php

/**
 * @file
 * Contains \Drupal\hashtag_taxonomy_formatter\Plugin\field\formatter\HashtagTaxonomyFormatter.
 */

namespace Drupal\hashtag_taxonomy_formatter\Plugin\Field\FieldFormatter;

use Drupal\taxonomy\Plugin\Field\FieldFormatter\TaxonomyFormatterBase;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Component\Utility\String;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Plugin implementation of the 'hashtag_taxonomy_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "hashtag_taxonomy_formatter",
 *   label = @Translation("Hashtag"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   },
 *   settings  = {
 *     "only_latin" = FALSE,
 *     "transliterate" = FALSE,
 *     "link" = FALSE,
 *     "twitter_link" = FALSE,
 *     "twitter_link_blank" = FALSE,
 *   },
 * )
 */
class HashtagTaxonomyFormatter extends TaxonomyFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      "only_latin" => FALSE,
      "transliterate" => FALSE,
      "link" => FALSE,
      "twitter_link" => FALSE,
      "twitter_link_blank" => FALSE,
        ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Remove non latin characters.
    $elements['only_latin'] = array(
      '#title' => t('Remove non latin characters'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('only_latin'),
    );

    // Check if transliteration module is installed and enabled.
    $transliteration_available = \Drupal::moduleHandler()->moduleExists('transliteration');

    // Transliterate term name.
    $elements['transliterate'] = array(
      '#title' => t('Transliterate non latin characters(Transliteration module required.)'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('transliterate') && $transliteration_available,
      '#disabled' => TRUE,
      '#states' => array(
        // Disable option when only_latin is checked.
        'disabled' => array(
          ':input[name="fields[' . $this->fieldDefinition->field_name . '][settings_edit_form][settings][only_latin]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Show the hashtags as links to twitter search.
    $elements['link'] = array(
      '#title' => t('Show hashtags as links'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    );

    // Show the hashtags as links to twitter search.
    $elements['twitter_link'] = array(
      '#title' => t('Show hashtags as links to twitter search'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('twitter_link'),
      '#states' => array(
        // Disable option when twitter_link is unchecked.
        'disabled' => array(
          ':input[name="fields[' . $this->fieldDefinition->field_name . '][settings_edit_form][settings][link]"]' => array('checked' => FALSE),
        ),
      ),
    );

    // Twitter link target(_blank or self).
    $elements['twitter_link_blank'] = array(
      '#title' => t('Open twitter links in new tab'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('twitter_link_blank'),
      '#states' => array(
        // Disable option when twitter_link is unchecked.
        'disabled' => array(
          ':input[name="fields[' . $this->fieldDefinition->field_name . '][settings_edit_form][settings][twitter_link]"]' => array('checked' => FALSE),
        ),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    // Latin characters option.
    if ($this->getSetting('only_latin')) {
      $summary[] = t('Remove non latin characters.');
    }
    else {
      $summary[] = t('Keep non latin characters.');
    }

    // Check if transliteration module is installed and enabled.
    $transliteration_available = \Drupal::moduleHandler()->moduleExists('transliteration');

    // Transliteration option.
    if ($this->getSetting('transliterate') && $transliteration_available) {
      $summary[] = t('Transliterate non latin characters.');
    }

    // Show as link.
    if ($this->getSetting('link')) {
      $summary[] = t('Show hashtags as links.');
      // Twitter links option.
      if ($this->getSetting('twitter_link')) {
        $summary[] = t('Show hashtags as links to twitter search.');

        // If twitter links are enabled, check for twitter link target option.
        if ($this->getSetting('twitter_link_blank')) {
          $summary[] = t('Open twitter links in new tab.');
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $full_term = $item->entity;
      //dpm($full_term);

      if (isset($full_term)) {
        // Based on settings, convert term name to a social-safe-hashtag value.
        $hashtagged_string = $this->hashtagTaxonomyFormatterConvertHashtag($full_term);

        // Check if hashtag actually exists.
        if (!empty($hashtagged_string)) {
          $elements[$delta] = array(
            '#markup' => $hashtagged_string,
          );
        }
      }
    }

    return $elements;
  }

  /**
   * Make the string hashtag safe.
   */
  private function hashtagTaxonomyFormatterConvertHashtag($term) {

    $tname = $term->getName();
    $tid = $term->id();

    // Capitalize first letter of each word.
    $hashtag = mb_convert_case($tname, MB_CASE_TITLE, 'UTF-8');

    // Keep only letters and numbers.
    if (!$this->getSetting('only_latin')) {
      // Keep latin, non latin characters and numbers.
      $hashtag = preg_replace('/[\p{P}+\s]/u', '', $hashtag);
    }
    else {
      // Keep only latin characters and numbers.
      $hashtag = preg_replace('/[^A-Za-z0-9]/', '', $hashtag);
    }

    // For all the remaining actions, we check if there are available characters.
    if (!empty($hashtag)) {

      // Check if transliteration module is installed and enabled.
      $transliteration_available = \Drupal::moduleHandler()->moduleExists('transliteration');

      // Transliterate text if enabled.
      if ($this->getSetting('transliterate') && $transliteration_available) {
        $hashtag = transliteration_get($hashtag, '');
      }

      // Add hash.
      $hashtag = '#' . $hashtag;

      // Get link settings.
      if ($this->getSetting('link')) {
        // Show hashtag as link to twitter search.
        if ($this->getSetting('link') && $this->getSetting('twitter_link')) {
          // Get target.
          $this->getSetting('twitter_link_blank') ? $target = '_blank' : $target = '_self';

          // Create link.
          $hashtag_url = Url::fromUri('https://twitter.com/search', ['query' => ['q' => $hashtag], 'attributes' => ['target' => $target]]);
          $hashtag = Link::fromTextAndUrl($hashtag, $hashtag_url);
        }
        else if ($this->getSetting('link') && !$this->getSetting('twitter_link')) {
          // Link inside drupal site.
          $hashtag_url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tid]);
          $hashtag = Link::fromTextAndUrl($hashtag, $hashtag_url);
        }
      }
    }

    // Sanitize string, just in case.
    //$hashtag = filter_xss($hashtag, array('a'));

    return $hashtag;
  }
}
