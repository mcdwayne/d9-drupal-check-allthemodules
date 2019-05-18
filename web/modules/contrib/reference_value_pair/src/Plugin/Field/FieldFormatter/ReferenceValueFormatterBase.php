<?php

namespace Drupal\reference_value_pair\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Parent plugin for reference value pair formatters.
 */
abstract class ReferenceValueFormatterBase extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'display_invalid_reference' => TRUE,
      'invalid_reference_label' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['display_invalid_reference'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display invalid reference'),
      '#default_value' => $this->getSetting('display_invalid_reference'),
      '#min' => 1,
      '#description' => $this->t('Display the value even if the referenced entity is invalid (e.g. has been deleted).'),
    );
    $elements['invalid_reference_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Invalid reference label'),
      '#default_value' => $this->getSetting('invalid_reference_label'),
      '#description' => $this->t('Label to display when the reference is invalid. Leave empty to not display only the value without a label.'),
      '#states' => array(
        'visible' => array(
          ':input[name*="display_invalid_reference"]' => array('checked' => TRUE),
        ),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();

    if (!empty($settings['display_invalid_reference'])) {
      if (empty($settings['invalid_reference_label'])) {
        $summary[] = $this->t('For invalid references display the value without label');
      }
      else {
        $summary[] = $this->t('For invalid references use this label: <em>@label</em>',
          ['@label' => $settings['invalid_reference_label']]);
      }
    }
    else {
      $summary[] = $this->t('Show only pairs where reference is valid');
    }

    return $summary;
  }



  /**
   * Returns the referenced entities for display.
   * @TODO update documentation
   *
   * The method takes care of:
   * - checking entity access,
   * - placing the entities in the language expected for display.
   * It is thus strongly recommended that formatters use it in their
   * implementation of viewElements($items) rather than dealing with $items
   * directly.
   *
   * For each entity, the EntityReferenceItem by which the entity is referenced
   * is available in $entity->_referringItem. This is useful for field types
   * that store additional values next to the reference itself.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   *
   * @see ::prepareView()
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = array();

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $entity = $item->entity;
        if ($entity === NULL) {
          $entities[$delta] = NULL;
          continue;
        }
        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface) {
          $entity = \Drupal::entityManager()->getTranslationFromContext($entity, $langcode);
        }

        $access = $this->checkAccess($entity);
        // Add the access result's cacheability, ::view() needs it.
        $item->_accessCacheability = CacheableMetadata::createFromObject($access);
        if ($access->isAllowed()) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
          $entities[$delta] = $entity;
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   */
  public function prepareView(array $entities_items) {
    parent::prepareView($entities_items);
    $settings = $this->getSettings();

    if (empty($settings['display_invalid_reference'])) {
      return;
    }

    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if ($item->_loaded) {
          continue;
        }
        $item->_loaded = TRUE;
        $item->entity = NULL;
        $item->_label = $settings['invalid_reference_label'];
      }
    }
  }

}
