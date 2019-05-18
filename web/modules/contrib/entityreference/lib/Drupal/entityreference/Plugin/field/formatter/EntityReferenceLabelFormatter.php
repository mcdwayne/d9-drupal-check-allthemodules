<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\field\formatter\EntityReferenceLabelFormatter.
 */

namespace Drupal\entityreference\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;

use Drupal\entityreference\Plugin\field\formatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity-reference label' formatter.
 *
 * @Plugin(
 *   id = "entityreference_label",
 *   module = "entityreference",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entityreference"
 *   },
 *   settings = {
 *     "link" = "FALSE"
 *   }
 * )
 */
class EntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements['link'] = array(
      '#title' => t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    );

    return $elements;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::settingsForm().
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');

    return implode('<br />', $summary);
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    // Remove un-accessible items.
    parent::viewElements($entity, $langcode, $items);

    $instance = $this->instance;
    $field = $this->field;

    $elements = array();

    foreach ($items as $delta => $item) {
      $entity = $item['entity'];
      $label = $entity->label();
      // If the link is to be displayed and the entity has a uri,
      // display a link.
      if ($this->getSetting('link') && $uri = $entity->uri()) {
        $elements[$delta] = array('#markup' => l($label, $uri['path'], $uri['options']));
      }
      else {
        $elements[$delta] = array('#markup' => check_plain($label));
      }
    }

    return $elements;
  }

}
