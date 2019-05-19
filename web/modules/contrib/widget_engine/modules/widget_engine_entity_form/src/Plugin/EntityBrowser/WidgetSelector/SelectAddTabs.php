<?php

namespace Drupal\widget_engine_entity_form\Plugin\EntityBrowser\WidgetSelector;

use Drupal\entity_browser\WidgetSelectorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays entity browser widgets as tabs.
 *
 * @EntityBrowserWidgetSelector(
 *   id = "select_add_tabs",
 *   label = @Translation("Select&Add tabs"),
 *   description = @Translation("Creates 2 horizontal tabs on the top of the entity browser for select existing entities and creating new ones.")
 * )
 */
class SelectAddTabs extends WidgetSelectorBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form = array(), FormStateInterface &$form_state = NULL) {
    $element = [];
    $element_index = 0;
    $browser_widgets = $this->getBrowserWidgets();
    $query = \Drupal::request()
      ->query;

    // Check if add new button was pressed.
    $add_tab = FALSE;
    if ($query->get('add_tab')) {
      $add_tab = TRUE;
    }

    foreach ($this->widget_ids as $id => $label) {
      $name = 'tab_selector_' . $id;
      $element[$name] = [
        '#type' => 'button',
        '#attributes' => [
          'class' => ['tab'],
          'widget_type' => 'bar',
        ],
        '#value' => $label,
        '#executes_submit_callback' => TRUE,
        '#limit_validation_errors' => [[$id]],
        '#submit' => [],
        '#name' => $name,
        '#widget_id' => $id,
      ];
      if ($element_index === 0) {
        $element[$name]['#attributes']['class'] = ['tab', 'view-option-tab'];
        if ($add_tab && $id == $this->getDefaultWidget()) {
          $element[$name]['#attributes']['class'][] = 'add-tab-open';
        }
        $element[$name]['#disabled'] = $id == $this->getDefaultWidget();
      }
      if (isset($browser_widgets[$id])) {
        $element[$name]['#attributes']['tab-class'] = str_replace('_', '-', $browser_widgets[$id]);
      }
      $element_index++;
    }

    if ($element_index == 2) {
      $element[$name]['#attributes']['class'] = ['tab', 'add-option-tab'];
      if ($add_tab) {
        $element[$name]['#attributes']['class'][] = 'add-option-tab-single';
      }
      $element[$name]['#disabled'] = $id == $this->getDefaultWidget();
    }

    $element['#attached']['library'][] = 'widget_engine_entity_form/entity_browser_widget_preview';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$form, FormStateInterface $form_state) {
    if (($trigger = $form_state->getTriggeringElement()) &&
      strpos($trigger['#name'], 'tab_selector_') === 0 &&
      !empty($this->widget_ids[$trigger['#widget_id']])) {
      return $trigger['#widget_id'];
    }
  }

  /**
   * Helper function for getting all available widgets from all entity browsers.
   *
   * @return array
   *   Array of widget bundles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getBrowserWidgets() {
    $browser_widgets = [];
    $entity_browsers = \Drupal::entityTypeManager()->getStorage('entity_browser')->loadMultiple();
    foreach ($entity_browsers as $entity_browser) {
      $widgets = $entity_browser->getWidgets();
      $configurations = $widgets->getConfiguration();
      foreach ($configurations as $id => $configuration) {
        if ($configuration['id'] == 'entity_form') {
          $browser_widgets[$id] = $configuration['settings']['bundle'];
        }
      }
    }

    return $browser_widgets;
  }

}
