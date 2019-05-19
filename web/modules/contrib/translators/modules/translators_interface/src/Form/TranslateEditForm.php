<?php

namespace Drupal\translators_interface\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\translators_interface\TranslatorsInterfaceLanguagesTrait;
use Drupal\locale\Form\TranslateEditForm as TranslateEditFormOrigin;

/**
 * Class TranslateEditForm.
 *
 * @package Drupal\translators_interface\Form
 */
class TranslateEditForm extends TranslateEditFormOrigin {
  use TranslatorsInterfaceLanguagesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'translators_interface_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $this->handleMiddleTranslation($form);
    return $form;
  }

  /**
   * Update table with a new column if needed.
   *
   * @param array &$form
   *   Form array.
   */
  protected function handleMiddleTranslation(array &$form) {
    $filter_values = $this->translateFilterValues();
    if ($this->middleColumnIsAllowed($filter_values)) {
      // Add an additional table header.
      $header = &$form['strings']['#header'];
      $last   = array_pop($header);
      if ($filter_values['langcode_from'] === 'en') {
        $language = 'English';
      }
      else {
        $language = $this->languageManager
          ->getLanguages()[$filter_values['langcode_from']]
          ->getName();
      }
      $header[] = $this->t('@language translation', ['@language' => $language]);
      $header[] = $last;
      // Add an additional table column.
      foreach (Element::children($form['strings']) as $id) {
        $translations = array_pop($form['strings'][$id]);
        $middle = $this->getMiddleTranslation($id, $filter_values['langcode_from']);
        $form['strings'][$id]['middle'] = [
          '#type'       => 'item',
          '#plain_text' => !empty($middle) ? $middle : "",
        ];
        $form['strings'][$id]['translations'] = $translations;
      }
    }
  }

  /**
   * Check if we do need to render middle column.
   *
   * @param array $filter_values
   *   Filter values array.
   *
   * @return bool
   *   Conditions checking result.
   */
  protected function middleColumnIsAllowed(array $filter_values) {
    $from    = $filter_values['langcode_from'];
    $default = $this->languageManager->getDefaultLanguage()->getId();
    return !empty($from) && $from
      && !$this->isDefaultToDefault($filter_values)
      && ($this->isTranslateToEnglishEnabled() || $from !== $default);
  }

  /**
   * Check whether both filters are equal and default languages are selected.
   *
   * @param array $values
   *   Filter values array.
   *
   * @return bool
   *   Checking result.
   */
  protected function isDefaultToDefault(array $values) {
    $default = $this->languageManager->getDefaultLanguage()->getId();
    return $values['langcode'] === $values['langcode_from']
      && $values['langcode'] === $default;
  }

  /**
   * Get middle translation.
   *
   * @param string|int $lid
   *   String ID.
   * @param string $language
   *   Language ID.
   *
   * @return mixed
   *   Middle translation value.
   */
  protected function getMiddleTranslation($lid, $language) {
    $select = \Drupal::database()->select('locales_source', 'ls');
    $select->leftJoin('locales_target', 'lt', 'lt.lid = ls.lid');
    $select->fields('lt', ['translation']);
    $select->condition('ls.lid', $lid);
    $select->condition('lt.language', $language);
    return $select->execute()->fetchField();
  }

}
