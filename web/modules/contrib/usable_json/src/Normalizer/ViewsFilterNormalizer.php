<?php

namespace Drupal\usable_json\Normalizer;

use Drupal\Core\Form\FormState;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Defines a class for normalizing PagerNormalizer.
 */
class ViewsFilterNormalizer extends ComplexDataNormalizer {

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = ['usable_json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FilterPluginBase::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($filter, $format = NULL, array $context = []) {
    $values = [];
    /* @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $values['type'] = $filter->getBaseId();
    $values['identifier'] = $filter->options['expose']['identifier'];
    $form = [];
    $formState = new FormState();
    $filter->buildExposedForm($form, $formState);
    $filterForm = $form[$values['identifier']];
    $values['form'] = [
      'type' => $filterForm['#type'],
      'options' => $filterForm['#options'],
    ];

    return $values;
  }

}
