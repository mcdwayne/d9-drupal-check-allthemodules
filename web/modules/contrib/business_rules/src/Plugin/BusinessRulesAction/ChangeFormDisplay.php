<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ChangeFormDisplay.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "change_form_display",
 *   label = @Translation("Change form display"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Display form using a custom form mode."),
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class ChangeFormDisplay extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    if ($item->isNew()) {
      return [];
    }

    $form_modes = \Drupal::service('entity_display.repository')
      ->getFormModes($item->getTargetEntityType());

    $options = [];
    foreach ($form_modes as $key => $value) {
      $options[$key] = $value['label'];
    }
    uasort($options, function ($a, $b) {
      return $a['label'] < $b['label'] ? -1 : 1;
    });

    $settings['selection_mode'] = [
      '#type' => 'radios',
      '#title' => t('Selection mode'),
      '#description' => t('How do you want to set the form display mode?'),
      '#default_value' => $item->getSettings('selection_mode'),
      '#required' => TRUE,
      '#options' => [
        'fixed' => t('Fixed'),
        'variable' => t('Using variables or token'),
      ],
    ];

    $settings['fixed'] = [
      '#type' => 'select',
      '#title' => t('Form Mode'),
      '#default_value' => $item->getSettings('fixed'),
      '#options' => $options,
      '#empty_option' => t('- Select -'),
      '#states' => [
        'visible' => [
          ':input[name="selection_mode"]' => ['value' => 'fixed'],
        ],
      ],
    ];

    $settings['variable'] = [
      '#type' => 'textfield',
      '#title' => t('Variable or token'),
      '#description' => t('The variable/token must hold the value of the view mode id without the entity type.'),
      '#default_value' => $item->getSettings('variable'),
      '#states' => [
        'visible' => [
          ':input[name="selection_mode"]' => ['value' => 'variable'],
        ],
      ],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\business_rules\ItemInterface $item */
    $item = $form_state->get('business_rules_item');

    // We only can validate the form if the item is not new.
    if (!empty($item) && !$item->isNew()) {
      if ($form_state->getValue('selection_mode') == 'fixed') {
        if (!$form_state->getValue('fixed')) {
          $form_state->setErrorByName('fixed', t('Select the form mode'));
        }
      }
      elseif (!$form_state->getValue('variable')) {
        $form_state->setErrorByName('variable', t('Fill the variable/token'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $variables = $event->getArgument('variables');
    $selection_mode = $action->getSettings('selection_mode');
    $fixed = $action->getSettings('fixed');
    $variable = $action->getSettings('variable');
    $variable = $this->processVariables($variable, $variables);

    // Remove the first part of the machine name id it's on the variable value.
    $arr = explode('.', $variable);
    $variable = isset($arr[1]) ? $arr[1] : $arr[0];

    $entity_type = $event->getArgument('entity_type_id');
    $bundle = $event->getArgument('bundle');

    $form_display_mode = ($selection_mode == 'fixed') ? $fixed : $variable;

    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . $form_display_mode);

    $event->setArgument('form_display', $form_display);

    $result = [
      '#type' => 'markup',
      '#markup' => t('Form display changed to: %form_display, on entity type: %entity_type, bundle: %bundle.', [
        '%form_display' => $form_display_mode,
        '%entity_type' => $entity_type,
        '%bundle' => $bundle,
      ]),
    ];

    return $result;
  }

}
