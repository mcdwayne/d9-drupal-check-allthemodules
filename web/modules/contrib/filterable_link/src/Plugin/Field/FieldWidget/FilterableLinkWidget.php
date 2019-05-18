<?php

namespace Drupal\filterable_link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'filterable_link_widget' widget.
 *
 * @FieldWidget(
 *   id = "filterable_link_widget",
 *   label = @Translation("Filterable Link Widget"),
 *   description = @Translation("Filterable Link Widget"),
 *   field_types = {
 *     "filterable_link",
 *   },
 * )
 */

class FilterableLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $bundle_types = $this->getFieldSetting('bundle_types');
    $target_bundles = [];

    // Construct target bundles array.
    foreach ($bundle_types as $key => $target_bundle) {
      if ($key != 'all' && $target_bundle != FALSE) {
        $target_bundles[] = $target_bundle;
      }
    }

    // Override the link uri field settings with target bundles.
    if (!empty($target_bundles)) {
      $widget['uri']['#selection_settings'] = [
        'target_bundles' => $target_bundles
      ];
    }

    return $widget;
  }

}
