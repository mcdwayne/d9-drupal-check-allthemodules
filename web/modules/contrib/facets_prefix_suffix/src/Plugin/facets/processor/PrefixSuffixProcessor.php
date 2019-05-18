<?php

namespace Drupal\facets_prefix_suffix\Plugin\facets\processor;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Result\Result;

/**
 * Provides a processor that adds prefix and suffix.
 *
 * @FacetsProcessor(
 *   id = "prefix_suffix",
 *   label = @Translation("Prefix / suffix"),
 *   description = @Translation("Add prefix/suffix to result items."),
 *   stages = {
 *     "build" = 35
 *   }
 * )
 */
class PrefixSuffixProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'prefix' => FALSE,
      'suffix' => FALSE,
      'custom_prefix' => '',
      'custom_suffix' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $label = $facet->getDataDefinition()->getFieldDefinition()->getLabel();
    $prefix = $facet->getDataDefinition()->getFieldDefinition()->getSetting('prefix');
    $suffix = $facet->getDataDefinition()->getFieldDefinition()->getSetting('suffix');

    $build['prefix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show prefix from source field %field: @prefix', [
        '%field' => $label,
        '@prefix' => $prefix,
      ]),
      '#default_value' => $config['prefix'],
    ];
    $build['suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show suffix from source field %field: @suffix', [
        '%field' => $label,
        '@suffix' => $suffix,
      ]),
      '#default_value' => $config['suffix'],
    ];
    $build['custom_prefix'] = [
      '#title' => $this->t('Custom prefix value'),
      '#type' => 'textfield',
      '#default_value' => $config['custom_prefix'],
      '#description' => $this->t('Use this as a prefix.'),
      '#states' => [
        'visible' => [
          'input[name="facet_settings[prefix_suffix][settings][prefix]"' => ['checked' => FALSE]
        ],
      ],
    ];
    $build['custom_suffix'] = [
      '#title' => $this->t('Custom suffix value'),
      '#type' => 'textfield',
      '#default_value' => $config['custom_suffix'],
      '#description' => $this->t('Use this as a suffix.'),
      '#states' => [
        'visible' => [
          'input[name="facet_settings[prefix_suffix][settings][suffix]"' => ['checked' => FALSE]
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {}

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $prefix = '';
    $suffix = '';
    $config = $this->getConfiguration();

    // Prefix.
    if ($config['prefix']) {
      $prefix = Xss::filter($facet->getDataDefinition()->getFieldDefinition()->getSetting('prefix'));
    }
    elseif ($config['custom_prefix'] !== '') {
      $prefix = Xss::filter($config['custom_prefix']);
    }

    // Suffix.
    if ($config['suffix']) {
      $suffix = Xss::filter($facet->getDataDefinition()->getFieldDefinition()->getSetting('suffix'));
    }
    elseif ($config['custom_suffix'] !== '') {
      $suffix = Xss::filter($config['custom_suffix']);
    }

    /** @var \Drupal\facets\Result\Result $result */
    foreach ($results as $result) {
      if ($prefix !== '') {
        $value = $prefix . $result->getDisplayValue();
        $result->setDisplayValue($value);
      }
      if ($suffix !== '') {
        $value = $result->getDisplayValue() . $suffix;
        $result->setDisplayValue($value);
      }
    }

    return $results;
  }

}
