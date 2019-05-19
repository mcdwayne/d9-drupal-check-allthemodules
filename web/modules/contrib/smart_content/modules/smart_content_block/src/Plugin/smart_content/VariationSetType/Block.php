<?php

namespace Drupal\smart_content_block\Plugin\smart_content\VariationSetType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\VariationSetType\VariationSetTypeBase;

/**
 * @SmartVariationSetType(
 *   id = "block",
 *   label = @Translation("Block"),
 * )
 */
class Block extends VariationSetTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getVariationPluginId() {
    return 'variation_block';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    foreach ($this->entity->getVariations() as $variation_id => $variation) {
      $disabled = ($this->entity->getDefaultVariation() && $this->entity->getDefaultVariation() != $variation_id) ? 'disabled' : '';
      $form['variations_config']['variation_items'][$variation_id]['plugin_form']['additional_settings'] = [
        '#type' => 'container',
        '#weight' => 10,
        '#attributes' => [
          'class' => ['variation-additional-settings-container'],
          'disabled' => [$disabled],
        ],
      ];
      $form['variations_config']['variation_items'][$variation_id]['plugin_form']['additional_settings']['default_variation'] = [
        '#type' => 'checkbox',
        '#attributes' => [
          'class' => ['smart-variations-default-' . $variation_id],
          'disabled' => [$disabled],
        ],
        '#title' => 'Set as default variation',
        '#default_value' => $this->entity->getDefaultVariation() == $variation_id,
      ];
    }
    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->attachFormValues($form_state->getValues()['variations_config']['variation_items']);
  }

  public function attachFormValues($values) {
    foreach ($this->entity->getVariations() as $variation) {
      // Attaching default_variation value to variation config.
      if (isset($values[$variation->id()]['plugin_form']['additional_settings']['default_variation'])) {
        if ($values[$variation->id()]['plugin_form']['additional_settings']['default_variation']) {
          $this->entity->setDefaultVariation($variation->id());
        }
        else {
          if ($variation->id() == $this->entity->getDefaultVariation()) {
            $this->entity->setDefaultVariation('');
          }
        }
      }
    }
  }

}
