<?php

namespace Drupal\webform_epetition\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'webform_example_composite' element.
 *
 * @WebformElement(
 *   id = "webform_epetition_search",
 *   label = @Translation("E-petition Postcode Search"),
 *   description = @Translation("Provides a webform element to search for local representatives."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 */
class WebformEpetitionSearch extends WebformCompositeBase {

  /**
   * @return array
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
        'data_type' => '',
      ];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['data_type'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Parliment/Assembly'),
      '#options' => [
        'getMP' => $this
          ->t('UK'),
        'getMSP' => $this->t('Scotland'),
        'getMLA' => $this
          ->t('Northern Ireland'),
      ],
      '#help' => $this->t('Select which representative database.'),
      '#group' => 'tab_general',
      '#weight' => 60,
      '#default_value' => (!empty($this->configuration['data_type']) ? $this->configuration['data_type'] : ''),
    ];

    return parent::buildConfigurationForm($form, $form_state);

  }


}
