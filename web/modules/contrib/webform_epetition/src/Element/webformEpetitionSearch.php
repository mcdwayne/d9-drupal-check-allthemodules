<?php

namespace Drupal\webform_epetition\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'webform_epetition_search'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * @FormElement("webform_epetition_search")
 *
 */
class WebformEpetitionSearch extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_epetition_Search'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $element = parent::getCompositeElements($element);

    $element['ep_postcode'] = [
      '#type' => 'textfield',
      '#title' => t('Postcode'),
    ];

    $element['ep_email_to'] = [
      '#type' => 'hidden',
      '#title' => t('Email'),
    ];

    $element['ep_names_list'] = [
      '#type' => 'hidden',
      '#title' => t('Names list'),
    ];
    return $element;
  }


  /**
   * @param $element
   *
   * @return mixed
   */
  public static function preRenderWebformCompositeFormElement($element) {
    $element = parent::preRenderWebformCompositeFormElement($element);

    $element['#attached']['library'][] = 'webform_epetition/webform_epetition';

    $element['ep_postcode_lookup'] = [
      '#type' => 'inline_template',
      '#title' => t('Postcode lookup template'),
      '#template' => "<button type='button' id='lookup_rep'>{{ label }}</button>\n",
      '#context' => [
        'label' => t('Find Representative'),
      ]
    ];

    $element['ep_data_type'] = [
      '#type' => 'inline_template',
      '#title' => t('Postcode lookup template'),
      '#template' => "<input type='hidden' id='data_type' value='{{ label }}' />\n",
      '#context' => [
        'label' => $element['#data_type']
      ]
    ];

    $element['result'] = [
      '#type' => 'markup',
      '#title' => t('Postcode lookup representatives'),
      '#prefix' => '<div id="results_rep">',
      '#markup' => '<!-- Lookup Result Placeholder -->',
      '#suffix' => '</div>',
    ];

    return $element;

  }

}
