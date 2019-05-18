<?php

namespace Drupal\rocketship_paragraphs\Plugin\DsField;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Plugin that renders a image inside a link field if exist.
 *
 * @DsField(
 *   id = "name_rule_field",
 *   title = @Translation("Name and rule field"),
 *   entity_type = "paragraph",
 *   provider = "rocketship_paragraphs",
 *   ui_limit = {"body_testimonial|*"}
 * )
 */
class NameRuleField extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'separator' => '-',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();
    $summary = '';
    if (isset($config['separator'])) {
      $summary = 'Separator: ' . $config['separator'];
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $settings['separator'] = [
      '#title' => t('Separator'),
      '#type' => 'textfield',
      '#default_value' => $config['separator'],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->entity();
    $build = [];
    $cache_tags = $entity->getCacheTags();

    // Build render array values.
    if ($entity->hasField('field_p_name')) {
      $name_field = $entity->get('field_p_name')->getValue();
      if (isset($name_field[0]['value'])) {
        $name = $name_field[0]['value'];
        $build = [
          '#theme' => 'name_rule_field',
          '#name' => [
            '#markup' => $name,
          ],
        ];
      }

      // Try to fetch the extra rule field.
      if ($entity->hasField('field_p_extra_rule')) {
        $extra_rule_field = $entity->get('field_p_extra_rule')->getValue();
        if (isset($extra_rule_field[0]['value'])) {
          $extra_rule = $extra_rule_field[0]['value'];
          $build['#separator'] = [
            '#markup' => $this->configuration['separator'],
          ];
          $build['#extra_rule'] = [
            '#markup' => $extra_rule,
          ];
        }
      }
    }

    // Add cacheable dependencies.
    $build['#cache']['tags'] = $cache_tags;

    return $build;
  }

}
