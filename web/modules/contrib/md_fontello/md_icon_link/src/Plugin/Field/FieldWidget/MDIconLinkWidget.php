<?php

namespace Drupal\md_icon_link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\micon\Entity\Micon;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "md_icon_link",
 *   label = @Translation("Link (with fontello icon)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class MDIconLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
        'placeholder_url' => '',
        'placeholder_title' => '',
        'packages' => [],
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['packages'] = [
      '#type' => 'checkboxes',
      '#attributes' => ['checked' => 'checked'],
      '#title' => t('Icon Packages'),
      '#default_value' => $this->getSetting('packages'),
      '#description' => t('The icon packages that should be made available in this field. If no packages are selected, all will be made available.'),
      '#options' => $this->getFonts(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $item = $items[$delta];
    $options = $item->get('options')->getValue();
    $attributes = isset($options['attributes']) ? $options['attributes'] : [];
    $element['options']['attributes']['data-icon'] = [
      '#type' => 'mdicon',
      '#title' => $this->t('Fontello Icon'),
      '#default_value' => isset($attributes['data-icon']) ? $attributes['data-icon'] : NULL,
      '#packages' => $this->getPackages(),
      '#element_validate' => array(
        array(
          get_called_class(),
          'validateIconElement'
        )
      ),
    ];

    return $element;
  }

  /**
   * Get packages available to this field.
   */
  protected function getPackages() {
    $packages = $this->getSetting('packages');
    $options = [];

    foreach ($packages as $index => $package) {
      if ($package !== FALSE) {
        $options[] = $package;
      }
    }

    return $options;
  }

  /**
   * @return array
   */
  protected function getFonts() {
    $fonts = \Drupal::service('md_fontello')->getListFonts();

    $options = [];
    foreach ($fonts as $index => $font) {
      $options[$font['name']] = $font['title'];
    }

    return $options;
  }

  /**
   * Recursively clean up options array if no data-icon is set.
   */
  public static function validateIconElement($element, FormStateInterface $form_state, $form) {
    if ($values = $form_state->getValue('link')) {
      foreach ($values as &$value) {
        if (empty($value['options']['attributes']['data-icon'])) {
          unset($value['options']['attributes']['data-icon']);
        }
        if (empty($value['options']['attributes'])) {
          unset($value['options']['attributes']);
        }
        if (empty($value['options'])) {
          unset($value['options']);
        }
      }
      $form_state->setValue('link', $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $enabled_packages = array_filter($this->getSetting('packages'));
    if ($enabled_packages) {
      $enabled_packages = array_intersect_key($this->getFonts(), $enabled_packages);
      $summary[] = $this->t('With icon packages: @packages', array('@packages' => implode(', ', $enabled_packages)));
    }
    else {
      $summary[] = $this->t('With icon packages: @packages', array('@packages' => 'All'));
    }
    return $summary;
  }

}
