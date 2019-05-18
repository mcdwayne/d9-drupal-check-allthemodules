<?php

namespace Drupal\merci_line_item\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\TranslationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;

/**
 * Complex inline widget.
 *
 * @FieldWidget(
 *   id = "merci_line_item_form",
 *   label = @Translation("Merci Line Item form"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class MerciLineItemWidget extends InlineEntityFormComplex {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Build a parents array for this element's values in the form.
    $parents = array_merge($element['#field_parents'], [
      $items->getName(),
      'form',
      ]);
    // Assign a unique identifier to each IEF widget.
    // Since $parents can get quite long, sha1() ensures that every id has
    // a consistent and relatively short length while maintaining uniqueness.
    $this->setIefId(sha1(implode('-', $parents)));

    $this->prepareFormState($form_state, $items, $this->isTranslating($form_state));

    $form_state->set(['inline_entity_form', $this->getIefId(), 'form'], 'add');
    // If no form is open, show buttons that open one.
    $open_form = $form_state->get(['inline_entity_form', $this->getIefId(), 'form']);
    $target_bundles = $this->getTargetBundles();
    $bundle = reset($target_bundles);
    $form_state->set(['inline_entity_form', $this->getIefId(), 'form settings'], [
      'bundle' => $bundle,
      ]);
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['form']['#weight'] = -1;

    return $element;
  }

}
