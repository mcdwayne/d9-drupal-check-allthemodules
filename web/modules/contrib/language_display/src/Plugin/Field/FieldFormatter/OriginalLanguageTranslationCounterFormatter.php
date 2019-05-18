<?php

namespace Drupal\language_display\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Plugin for the 'original_language_translation_counter' formatter.
 *
 * @FieldFormatter(
 *   id = "original_language_translation_counter",
 *   label = @Translation("Original language with translation counter"),
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class OriginalLanguageTranslationCounterFormatter extends LanguageFormatter {


  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {

    $entity = $item->getEntity();
    $translation_count = count($entity->getTranslationLanguages());
    if(!$this->getSetting('include_original_language')){
      $translation_count--;
    }

    return [
      '#markup' => $this->buildMarkup($item->getEntity(), $translation_count),
      '#attached' => ['library' => ['language_display/language_display']]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['include_original_language'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['include_original_language'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include original language in count'),
      '#default_value' => $this->getSetting('include_original_language')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('include_original_language')) {
      $summary[] = $this->t('Include original language in count');
    }
    return $summary;
  }

  /**
   * Build markup for the field formatter.
   *
   * @param FieldableEntityInterface $entity
   *   Fieldable entity.
   *
   * @return string
   *   Markup string.
   */
  protected function buildMarkup($language_name, $translation_count) {
    $markup = '<span class="language-name">';
    $markup .= $this->languageManager->getDefaultLanguage()->getName();
    $markup .= '</span>';
    if($translation_count > 0){
      $markup .= '<span class="translation-counter">';
      $markup .= $this->getStringTranslation()
        ->formatPlural(
          $translation_count,
          '1 translation',
          '@count translations'
        );
      $markup .= '</span>';
    }
    return $markup;
  }
}
