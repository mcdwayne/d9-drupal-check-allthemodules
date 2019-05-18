<?php

namespace Drupal\entity_reference_quantity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_quantity_label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_quantity_label",
 *   label = @Translation("Label with quantity"),
 *   description = @Translation("Display the label of the referenced entities with quantity."),
 *   field_types = {
 *     "entity_reference_quantity"
 *   }
 * )
 */
class EntityReferenceQuantityLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
        'location' => 'suffix',
        'template' => ' ({{ quantity }})',
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['location'] = array(
      '#type' => 'radios',
      '#options' => [
        'pre-title' => t('Before the title'),
        'post-title' => t('After the title'),
        'suffix' => t('As part of the suffix'),
        'attribute' => t('In a data attribute'),
      ],
      '#title' => t('Output location'),
      '#default_value' => $this->getSetting('location'),
      '#required' => TRUE,
    );
    $elements['template'] = array(
      '#type' => 'textfield',
      '#title' => t('Output template'),
      '#default_value' => $this->getSetting('template'),
      '#description' => t('A simple Twig snippet that outputs the "quantity" variable.'),
      '#required' => TRUE,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    switch ($this->getSetting('location')) {
      case 'pre-title':
        $location = t('before the title');
        break;
      case 'post-title':
        $location = t('after the title');
        break;
      case 'suffix':
        $location = t('as part of the suffix');
        break;
      case 'attribute':
        $location = t('in a data-* attribute');
        break;
      default:
        $location = t('as part of the suffix');
        break;
    }
    $summary[] = t('Display @action', array('@action' => $location));
    $summary[] = t('Display as: @template', array('@template' => $this->getSetting('template')));

    return $summary;
  }

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();
    $twig = new \Twig_Environment();

    foreach ($elements as $delta => $entity) {
      if (!empty($values[$delta]['quantity'])) {
        /** @var \Drupal\Core\Template\TwigEnvironment $environment */
        $environment = \Drupal::service('twig');
        $output = $environment->renderInline($this->getSetting('template'), ['quantity' => $values[$delta]['quantity']]);

        switch ($this->getSetting('location')) {
          case 'attribute':
            $elements[$delta]['#attributes']['data-quantity'] = $output;
            break;
          case 'pre-title':
            $elements[$delta]['#title'] .= $output;
            break;
          case 'post-title':
            $elements[$delta]['#title'] = $output . $elements[$delta]['#title'];
            break;
          case 'suffix':
            if (!isset($elements[$delta]['#suffix'])) {
              $elements[$delta]['#suffix'] = '';
            }
            $elements[$delta]['#suffix'] = $output;
            break;
        }
      }
    }

    return $elements;
  }
}
