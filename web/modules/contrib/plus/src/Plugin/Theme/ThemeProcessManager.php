<?php

namespace Drupal\plus;

use Drupal\plus\Plugin\Theme\ThemeInterface;
use Drupal\plus\Utility\Element;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Manages discovery and instantiation of Bootstrap form process callbacks.
 *
 * @ingroup plugins_process
 */
class ProcessManagerProvider extends ProviderPluginManager {

  /**
   * Constructs a new \Drupal\plus\Plugin\ProcessManagerProvider object.
   *
   * @param \Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The theme to use for discovery.
   */
  public function __construct(ThemeInterface $theme) {
    parent::__construct('Plugin/Process', 'Drupal\plus\Plugin\Process\ProcessInterface', 'Drupal\plus\Annotation\PlusProcess', $theme->getExtension());
    $this->alterInfo('plus_process_plugins');
  }

  /**
   * Global #process callback for form elements.
   *
   * @param array $element
   *   The element render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The altered element array.
   *
   * @see \Drupal\plus\Plugin\Alter\ElementInfo::alter
   */
  public static function process(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#bootstrap_ignore_process'])) {
      return $element;
    }

    static $theme;
    if (!isset($theme)) {
      $theme = Plus::getActiveTheme();
    }

    $e = Element::reference($element, $form_state);

    // Process AJAX.
    if (($e->getProperty('ajax') && !$e->isButton()) || $e->getProperty('autocomplete_route_name')) {
      static::processAjax($e, $form_state, $complete_form);
    }

    // Add "form-inline" class.
    if ($e->hasClass('container-inline')) {
      $e->replaceClass('container-inline', 'form-inline');
    }
    if ($e->isType(['color', 'date', 'number', 'range', 'tel', 'weight'])) {
      $e->addClass('form-inline', 'wrapper_attributes');
    }

    // Process input groups.
    if ($e->getProperty('input') && ($e->getProperty('input_group') || $e->getProperty('input_group_button'))) {
      static::processInputGroups($e, $form_state, $complete_form);
    }

    return $element;
  }

  /**
   * Processes elements with AJAX properties.
   *
   * @param \Drupal\plus\Utility\Element $element
   *   The element object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function processAjax(Element $element, FormStateInterface $form_state, array &$complete_form) {
    $ajax = $element->getProperty('ajax');

    // Show throbber AJAX requests in an input button group.
    if (!$element->isType('hidden') && (!isset($ajax['progress']['type']) || $ajax['progress']['type'] === 'throbber')) {
      // Use an icon for autocomplete "throbber".
      $icon = Plus::glyphicon('refresh');
      $element->appendProperty('field_suffix', Element::reference($icon)->addClass(['ajax-progress', 'ajax-progress-throbber']));
      $element->setProperty('input_group', TRUE);
    }
  }

  /**
   * Processes elements that have input groups.
   *
   * @param \Drupal\plus\Utility\Element $element
   *   The element object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  protected static function processInputGroups(Element $element, FormStateInterface $form_state, array &$complete_form) {
    // Automatically inject the nearest button found after this element if
    // #input_group_button exists.
    if ($element->getProperty('input_group_button')) {
      // Obtain the parent array to limit search.
      $array_parents = $element->getProperty('array_parents', []);

      // Remove the current element from the array.
      array_pop($array_parents);

      // Retrieve the parent element.
      $parent = Element::reference(NestedArray::getValue($complete_form, $array_parents), $form_state);

      // Find the closest button.
      if ($button = self::findButton($parent)) {
        // Since this button is technically being "moved", it needs to be
        // rendered now, so it doesn't get printed twice (in the original spot).
        $element->appendProperty('field_suffix', $button->setIcon()->render());
      }
    }

    $input_group_attributes = ['class' => ['input-group-' . ($element->getProperty('input_group_button') ? 'btn' : 'addon')]];
    if ($prefix = $element->getProperty('field_prefix')) {
      $element->setProperty('field_prefix', [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => $input_group_attributes,
        '#value' => Element::reference($prefix)->renderPlain(),
        '#weight' => -1,
      ]);
    }
    if ($suffix = $element->getProperty('field_suffix')) {
      $element->setProperty('field_suffix', [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => $input_group_attributes,
        '#value' => Element::reference($suffix)->renderPlain(),
        '#weight' => 1,
      ]);
    }
  }

  /**
   * Traverses an element to find the closest button.
   *
   * @param \Drupal\plus\Utility\Element $element
   *   The element to iterate over.
   *
   * @return \Drupal\plus\Utility\Element|FALSE
   *   The first button element or FALSE if no button could be found.
   */
  protected static function &findButton(Element $element) {
    $button = FALSE;
    foreach ($element->children() as $child) {
      if ($child->isButton()) {
        $button = $child;
      }
      if ($result = &self::findButton($child)) {
        $button = $result;
      }
    }
    return $button;
  }

}
