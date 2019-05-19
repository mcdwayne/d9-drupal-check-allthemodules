<?php

namespace Drupal\views_link_attributes;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * The views link attributes service.
 */
class ViewsUiFormService implements ViewsUiFormServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface &$form_state) {
    $options = $form_state->getStorage()['handler']->options;
    $link_attributes = [];
    // Field is editing.
    if (!empty($options['alter']['views_link_attributes']['items'])) {
      $link_attributes = $options['alter']['views_link_attributes']['items'];
    }
    // Get the values from a storage.
    elseif (!empty($options['alter']['link_attributes'])) {
      foreach ($options['alter']['link_attributes'] as $attribute => $value) {
        $link_attributes[] = ['attribute' => $attribute, 'value' => $value];
      }
    }

    // The default values.
    if (empty($link_attributes)) {
      $link_attributes[] = ['attribute' => '', 'value' => ''];
    }

    $items = [];
    foreach ($link_attributes as $item_value) {
      $item = [];
      $item['#type'] = 'item';
      $item['attribute'] = [
        '#title' => t('Attribute'),
        '#type' => 'textfield',
        '#default_value' => (string) $item_value['attribute'],
      ];
      $item['value'] = [
        '#title' => t('Value'),
        '#type' => 'textfield',
        '#default_value' => (string) $item_value['value'],
      ];
      $items[] = $item;
    }

    $elements = [
      '#title' => t('Views Link Attributes'),
      '#type' => 'details',
      '#states' => [
        'visible' => [
          ':input[name="options[alter][make_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['items'] = $items;

    $wrapper_id = Html::getUniqueId('views-link-attributes-add-more-wrapper');
    $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
    $elements['#suffix'] = '</div>';

    $elements['add_more'] = [
      '#type' => 'submit',
      '#value' => t('Add more'),
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];
    $form['options']['alter']['views_link_attributes'] = $elements;
    $form['actions']['submit']['#submit'][] = [get_class($this), 'submit'];
  }

  /**
   * Submission handler for the "Add more" button.
   */
  public static function addMoreSubmit($form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $id = $form_state->get('id');
    $type = $form_state->get('type');
    $executable = $view->getExecutable();
    $handler = $executable->getHandler($display_id, $type, $id);

    // Set values.
    $state_options = $form_state->getValue('options', []);
    $state_options['alter']['views_link_attributes']['items'][] = ['attribute' => '', 'value' => ''];
    $handler['alter']['views_link_attributes'] = $state_options['alter']['views_link_attributes'];
    $executable->setHandler($display_id, $type, $id, $handler);

    // Write to cache
    $view->cacheSet();
    $form_state->set('rerender', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add more" button.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $element['#open'] = TRUE;
    return $element;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submit(array &$form, FormStateInterface &$form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $id = $form_state->get('id');
    $type = $form_state->get('type');
    $executable = $view->getExecutable();
    $handler = $executable->getHandler($display_id, $type, $id);

    // Set values.
    $state_options = $form_state->getValue('options', []);
    $link_attributes = [];
    foreach ($state_options['alter']['views_link_attributes']['items'] as $item) {
      $link_attributes[$item['attribute']] = $item['value'];
    }
    $link_attributes = array_filter($link_attributes);
    if (!empty($link_attributes)) {
      $handler['alter']['link_attributes'] = $link_attributes;
    }
    else {
      unset($handler['alter']['link_attributes']);
    }
    // Removing the form values of the views link attributes module.
    unset($handler['alter']['views_link_attributes']);
    $executable->setHandler($display_id, $type, $id, $handler);

    // Write to cache
    $view->cacheSet();
  }

}
