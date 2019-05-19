<?php

namespace Drupal\smart_content\VariationSetType;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\smart_content\Form\SmartVariationSetForm;

/**
 * Base class for Smart variation plugins.
 */
abstract class VariationSetTypeBase extends PluginBase implements VariationSetTypeInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $defaults = [
      'default_variation' => '',
      'plugin_id' => $this->getPluginId(),
    ];
    return $defaults;
  }

  /**
   * @inheritdoc
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  public function writeChangesToConfiguration() {
    $configuration = $this->getConfiguration();
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateReactionRequest($variation_id, $context = []) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $wrapper_id = Html::getUniqueId('variation-set-wrapper');
    $wrapper_items_id = Html::getUniqueId('variation-set-items-wrapper');

    $form['variations_config'] = [
      '#type' => 'container',
      '#title' => 'Variations',
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '" class="variations-container">',
      '#suffix' => '</div>',
    ];
    $form['variations_config']['variation_items'] = [
      '#type' => 'table',
      '#header' => [t('Variations'), t('Weight'), t('')],
      '#prefix' => '<div id="' . $wrapper_items_id . '" class="variations-container-items">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $wrapper_items_id . '-order-weight',
        ],
      ],
    ];

    $i = 0;
    foreach ($this->entity->getVariations() as $variation_id => $variation) {
      $i++;

      SmartVariationSetForm::pluginForm($variation, $form, $form_state, [
        'variations_config',
        'variation_items',
        $variation_id,
        'plugin_form',
      ]);

      $form['variations_config']['variation_items'][$variation_id]['plugin_form']['#type'] = 'fieldset';
      $form['variations_config']['variation_items'][$variation_id]['plugin_form']['#title'] = 'Variation ' . $i;
      $form['variations_config']['variation_items'][$variation_id]['plugin_form']['#attributes']['class'][] = 'variation-container';
      $form['variations_config']['variation_items'][$variation_id]['#attributes']['class'][] = 'draggable';
      $form['variations_config']['variation_items'][$variation_id]['#weight'] = $variation->getWeight();

      $form['variations_config']['variation_items'][$variation_id]['weight'] = [
        '#type' => 'weight',
        '#title' => 'Weight',
        '#title_display' => 'invisible',
        '#attributes' => ['class' => [$wrapper_items_id . '-order-weight']],
      ];

      $form['variations_config']['variation_items'][$variation_id]['remove_variation'] = [
        '#type' => 'submit',
        '#value' => t('Remove Variation'),
        '#name' => 'remove_variation__' . $variation_id,
        '#submit' => [[$this, 'removeElementVariation']],
        '#attributes' => ['class' => ['align-right', 'remove-variation', 'remove-button']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$this, 'removeElementVariationAjax'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }

    $form['variations_config']['add_variation'] = [
      '#type' => 'submit',
      '#value' => t('Add Variation'),
      '#submit' => [[$this, 'addElementVariation']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'addElementVariationAjax'],
        'wrapper' => $wrapper_id,
      ],
    ];


    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->entity->getVariations() as $variation_id => $variation) {
      SmartVariationSetForm::pluginFormValidate($variation, $form, $form_state, [
        'variations_config',
        'variation_items',
        $variation_id,
        'plugin_form',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->attachTableVariationWeight($form_state->getValues()['variations_config']['variation_items']);
    foreach ($this->entity->getVariations() as $variation_id => $variation) {
      SmartVariationSetForm::pluginFormSubmit($variation, $form, $form_state, [
        'variations_config',
        'variation_items',
        $variation_id,
        'plugin_form',
      ]);
    }
  }

  public function attachTableVariationWeight($values) {
    foreach ($this->entity->getVariations() as $variation) {
      if (isset($values[$variation->id()]['weight'])) {
        $variation->setWeight($values[$variation->id()]['weight']);
      }
    }
    $this->entity->sortVariations();
  }

  public function addElementVariation(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $items = array_slice($button['#parents'], 0, -1);
    $items[] = 'variation_items';
    $values = NestedArray::getValue($form_state->getUserInput(), $items);
    $this->entity->addVariation(\Drupal::service('plugin.manager.smart_content.variation')
      ->createInstance($this->getVariationPluginId(), [], $this->entity));
    $form_state->setRebuild();
  }

  public function addElementVariationAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
  }

  public function removeElementVariation(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $items = array_slice($button['#parents'], 0, -3);
    $items[] = 'variation_items';
    $values = NestedArray::getValue($form_state->getUserInput(), $items);
    $this->attachTableVariationWeight($values);
    list($action, $name) = explode('__', $form_state->getTriggeringElement()['#name']);
    $this->entity->removeVariation($name);
    $form_state->setRebuild();
  }

  public function removeElementVariationAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }

}
