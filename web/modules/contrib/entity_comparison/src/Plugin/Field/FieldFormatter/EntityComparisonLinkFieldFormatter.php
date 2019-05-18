<?php

namespace Drupal\entity_comparison\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_comparison\Entity\EntityComparison;

/**
 * Plugin implementation of the 'entity_comparison_link' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_comparison_link",
 *   label = @Translation("Entity comparison link"),
 *   field_types = {
 *     "entity_comparison_link"
 *   }
 * )
 */
class EntityComparisonLinkFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'enitity_comparison' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    return [
      'enitity_comparison' => array(
        '#type' => 'value',
        '#value' => $this->getSetting('enitity_comparison') ?: '',
      ),
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    $enitity_comparison = $this->getSetting('enitity_comparison');

    $summary[] = $this->t('Entity comparison: @enitity_comparison', array('@enitity_comparison' => $enitity_comparison));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = array();

    $account = \Drupal::currentUser();

    if ( $account->hasPermission("use " . $this->getSetting('enitity_comparison') . " entity comparison") ) {
      $elements[0] = array(
        '#theme' => 'entity_comparison_link',
        '#id' => $items->getEntity()->id(),
        '#entity_comparison' => $this->getSetting('enitity_comparison'),
        '#cache' => array(
          'max-age' => 0,
        ),
      );
    }

    return $elements;
  }

}
