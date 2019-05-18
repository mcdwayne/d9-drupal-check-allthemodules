<?php

namespace Drupal\filterable_link\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'filterable_link' field type.
 *
 * @FieldType(
 *   id = "filterable_link",
 *   label = @Translation("Filterable Link"),
 *   description = @Translation("Filter links by bundle type."),
 *   default_widget = "filterable_link_widget",
 *   default_formatter = "link",
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */

class FilterableLinkItem extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'bundle_types' => ['all' => 'All'],
    ] + parent::defaultFieldSettings();
  }

  /**
   * Dynamically creates the bundle options for the 'bundle_types' field.
   * 
   * @return array;
   */
  protected static function bundleTypes() {
    $bundle_types = \Drupal::entityManager()->getBundleInfo('node');
    $bundles = ['all' => 'All'];

    // Construct associative array of bundles.
    foreach ($bundle_types as $key => $bundle_name) {
      $bundles[$key] = $bundle_name['label'];
    }

    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    // Add the 'bundle_types' field to the field settings form.
    $element['bundle_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Allowed bundle types'),
      '#default_value' => $this->getSetting('bundle_types'),
      '#description' => t('Choose the bundle types to filter the URL by.'),
      '#options' => FilterableLinkItem::bundleTypes(),
      '#multiple' => TRUE,
    ];

    return $element;
  }

}
