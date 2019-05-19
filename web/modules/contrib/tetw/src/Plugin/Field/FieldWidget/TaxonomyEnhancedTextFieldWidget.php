<?php

namespace Drupal\tetw\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'taxonomy_enhanced_text_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "taxonomy_enhanced_text_field_widget",
 *   label = @Translation("Taxonomy enhanced text field widget"),
 *   field_types = {
 *     "string",
 *     "text",
 *   }
 * )
 */
class TaxonomyEnhancedTextFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 60,
      'placeholder' => '',
      'vid' => '',
      'parent_term' => '',
      'has_other' => TRUE,
      'suffix' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = array(
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    );

    $elements['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );

    $elements['vid'] = array(
      '#type' => 'select',
      '#title' => t('Vocabulary'),
      '#default_value' => $this->getSetting('vid'),
      '#description' => t('Vocabulary that is to be used as reference for generating select list.'),
      '#options' => $this->getVocabularyOptions(),
    );

    $elements['parent_term'] = array(
      '#type' => 'select',
      '#title' => t('Parent Term'),
      '#default_value' => $this->getSetting('parent_term'),
      '#description' => t('Optionally limit to children of selected parent term.'),
      '#options' => $this->getTermsOptions($this->getSetting('vid')),
    );

    $elements['suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $this->getSetting('suffix'),
      '#description' => t('Suffix for the terms if present, for example mL, Kg, m, nos etc...'),
    );

    $elements['has_other'] = array(
      '#type' => 'checkbox',
      '#title' => t('Has Other?'),
      '#default_value' => $this->getSetting('has_other'),
      '#description' => t('Show a custom text input field.'),
    );

    return $elements;
  }

  /**
   * Get list of vocabulary.
   */
  private function getVocabularyOptions() {
    $options = [];
    $vocabs = taxonomy_vocabulary_get_names();
    if ($vocabs) {
      foreach ($vocabs as $vid) {
        $options[$vid] = \Drupal\taxonomy\Entity\Vocabulary::load($vid)->label();
      }
    }
    return $options;
  }

  /**
   * Get list of terms in a vocabulary that can be used as parent.
   */
  private function getTermsOptions($vid) {
    $options = [];
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid);
    foreach($terms as $term) {
      if ($term->depth == 0) {
       $options[$term->name] = $term->name;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if (!empty($this->getSetting('size'))) {
      $summary[] = t('Size: @placeholder', array('@placeholder' => $this->getSetting('size')));
    }

    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $this->getSetting('placeholder')));
    }

    if (!empty($this->getSetting('vid'))) {
      $summary[] = t('Vocabulary: @placeholder', array('@placeholder' => $this->getSetting('vid')));
    }

    if (!empty($this->getSetting('parent_term'))) {
      $summary[] = t('Parent Term: @placeholder', array('@placeholder' => $this->getSetting('parent_term')));
    }

    if (!empty($this->getSetting('has_other'))) {
      $summary[] = t('Has other: @placeholder', array('@placeholder' => $this->getSetting('has_other') ? t('Yes') : t('No')));
    }

    if (!empty($this->getSetting('suffix'))) {
      $summary[] = t('Suffix: @placeholder', array('@placeholder' => $this->getSetting('suffix')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];
    $parent_term = $this->getSetting('parent_term');
    $terms = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $parent_term)
      ->execute();
    $pid = reset($terms);

    $vid = $this->getSetting('vid');
    $options = [];

    $children = \Drupal::entityManager()->getStorage('taxonomy_term')->loadChildren($pid, $vid);

    foreach ($children as $child) {
      $options[$child->label()] = $child->label() . $this->getSetting('suffix');
    }

    $id = Html::getUniqueId('package-list-select-field');

    $default_other = '';
    if (isset($items[$delta]->value)) {
      $value = $items[$delta]->value;
      if (!in_array($value, array_keys($options))) {
        $default_other = $items[$delta]->value;
      }
    }

    $default_value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    $default_value = ($default_other) ? 'other' : $default_value;

    $element['value'] = array(
      '#type' => 'select',
      '#default_value' => $default_value,
      '#options' => $options + ['other' => t('Other')],
      '#id' => $id,
    );

    $element['other'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_other,
      '#size' => $this->getSetting('size'),
      '#states' => array(
        'visible' => array(
          ':input[id="' . $id . '"]' => array('value' => 'other'),
        ),
      ),
    );

    if ($this->getSetting('suffix')) {
      $element['other']['#description'] = $this->t('Enter custom value without "%suffix" suffix.',array('%suffix' => $this->getSetting('suffix')));
    }
    return $element;
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Field\WidgetBase::massageFormValues()
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $k = $values;
    foreach($values as $key => $value) {
      if ($value['value'] == 'other') {
        $value['value'] = $value['other'];
      }
      $values[$key] = $value;
    }
    return $values;
  }

}
