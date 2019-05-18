<?php

/**
 * @file
 * Contains \Drupal\google_adwords\Plugin\Field\FieldFormatter\GoogleAdWordsInlineJS.
 */

namespace Drupal\google_adwords\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'google_adwords_inlinejs' formatter.
 *
 * @FieldFormatter(
 *   id = "google_adwords_inlinejs",
 *   label = @Translation("Google AdWords inline"),
 *   field_types = {
 *     "google_adwords_tracking"
 *   }
 * )
 */
class GoogleAdWordsInlineJS extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      // Implement settings form.
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @returns [] Empty array
   *   We don't actually want any output, just tracking
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    /**
     * @var \Drupal\google_adwords\GoogleAdwordsTracker $tracker
     */
    $tracker = \Drupal::getContainer()->get('google_adwords.tracker');

    /**
     * @var \Drupal\Core\Config\ImmutableConfig $config
     *   saved settings for google_adwords
     */
    $config = \Drupal::config('google_adwords.settings');

    /**
     * @var \Drupal\Core\Session\AccountProxyInterface $user
     *   Current User
     */
    $user = \Drupal::currentUser();

    /**
     * @var array(string) $roles
     *  array of role ids for the current user
     */
    $roles = $user->getRoles();

    if (is_array($roles)) {
      /**
       * @var string $role_id
       */
      foreach ($roles as $role_id) {

        // look for the first trackeable role
        /**
         * @TODO make this a single array in the $config
         *   There is no need for separate vars here (no efficieny, no memory)
         */
        if (TRUE || $config->get('google_adwords_track_' . $role_id)) {
          // add each item as a themed conversion item
          foreach ($items as $delta => $item) {

            /**
             * @var \Drupal\Core\TypedData\Plugin\DataType\StringData $words
             */
            $words = $item->get('words');

            $tracker->addTracking(// $id, $label = NULL, $value = NULL, $language = NULL, $color = NULL, $format = NULL
              $config->get('conversion_id'),
              $config->get('label'),
              (string) $words->getCastedValue(),
              $langcode,
              $config->get('color'),
              $config->get('format')
            );
          }

          // break out of our role-checker (as we already passed at least 1 role)
          break;

        }
      }
    }

    /**
     * @return [] an empty array, we don't want output, we just want tracking
     */
    return [];
  }

}