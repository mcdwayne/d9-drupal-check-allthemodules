<?php

namespace Drupal\paragraphs_wizard\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paragraphs_wizard_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paragraphs_wizard_field_formatter",
 *   label = @Translation("Paragraphs wizard"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ParagraphsWizardFieldFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'view_mode' => 'default'
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    /** @var \Drupal\field_ui\Form\EntityViewDisplayEditForm  $callback_object */
    $callback_object = $form_state->getBuildInfo()['callback_object'];
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay  $entity_view_display */
    $entity_view_display = $callback_object->getEntity();
    $entity_type_id = $entity_view_display->getTargetEntityTypeId();
    $bundle = $entity_view_display->getTargetBundle();
    $available_view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle($entity_type_id,$bundle);
    $form['view_mode'] = array(
      '#type' => 'select',
      '#title' => t('View Mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => $available_view_modes,
      '#description' => t('Choose the view mode for rendering content in this paragraphs wizard'),
    );
    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Chosen view mode : ') . $this->getSetting('view_mode');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if(!empty($items)) {
      foreach ($items as $delta => $item) {
        $markup = $this->viewValue($item);
        $elements[$delta] = ['#markup' => $markup];
      }
    }
    $render_array = [];
    $render_array['#theme'] = 'paragraphswizard';
    $render_array['#data'] = $elements;
    return $render_array;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $values = $item->getValue();
    $entity_id = $values['target_id'];
    /** @var \Drupal\paragraphs\Entity\Paragraph $entity */
    $entity_type = 'paragraph';
    $view_mode = $this->getSetting('view_mode');
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $pre_render = $view_builder->view($entity, $view_mode);
    $render_output = render($pre_render);
    return $render_output;
  }

}
