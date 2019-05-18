<?php

namespace Drupal\mobile_number\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\mobile_number\MobileNumberUtilInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'mobile_number' element.
 *
 * @WebformElement(
 *   id = "mobile_number",
 *   label = @Translation("Mobile Number"),
 *   description = @Translation("Provides a form element for input of a mobile number."),
 *   category = @Translation("Advanced elements"),
 *   composite = TRUE,
 * )
 */
class MobileNumber extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'multiple' => FALSE,
      'multiple__header_label' => '',
      'default_country' => 'US',
      'countries' => [],
      'mn_placeholder' => 'Phone number',
      'as_link' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');

    $form['mobile_number'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mobile Number Settings'),
    ];

    $form['mobile_number']['default_country'] = [
      '#type' => 'select',
      '#title' => t('Default Country'),
      '#options' => $util->getCountryOptions([], TRUE),
      '#description' => t('Default country for mobile number input.'),
      '#required' => TRUE,
      '#element_validate' => [[
        $this,
        'settingsFormValidate',
      ],
      ],
    ];

    $form['mobile_number']['countries'] = [
      '#type' => 'select',
      '#title' => t('Allowed Countries'),
      '#options' => $util->getCountryOptions([], TRUE),
      '#description' => t('Allowed counties for the mobile number. If none selected, then all are allowed.'),
      '#multiple' => TRUE,
      '#attached' => ['library' => ['mobile_number/element']],
    ];

    $form['mobile_number']['mn_placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Number Placeholder'),
      '#description' => t('Number field placeholder.'),
    ];

    $form['display']['as_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Show as TEL link'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $default_country = $form_state->getValue('default_country');
    $allowed_countries = $form_state->getValue('countries');
    if (!empty($allowed_countries) && !in_array($default_country, $allowed_countries)) {
      $form_state->setErrorByName('mobile_number][default_country', t('Default country is not in one of the allowed countries.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    parent::setDefaultValue($element);

    $settings = [
      'default_country' => !empty($element['#default_country']) ? $element['#default_country'] : 'US',
    ];

    $element += [
      '#default_value' => [
        'country' => $settings['default_country'],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $settings = [
      'countries' => !empty($element['#countries']) ? $element['#countries'] : [],
      'placeholder' => isset($element['#mn_placeholder']) ? $element['#mn_placeholder'] : 'Phone number',
    ];

    $element += [
      '#mobile_number' => [
        'allowed_countries' => array_combine($settings['countries'], $settings['countries']),
        'verify' => MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE,
        'placeholder' => isset($settings['placeholder']) ? $settings['placeholder'] : NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value['value'])) {
      return '';
    }

    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $format = $this->getItemFormat($element);
    $phoneDisplayFormat = NULL;
    switch ($format) {
      case 'mobile_number_international':
        $phoneDisplayFormat = 1;
        break;

      case 'mobile_number_local':
        $phoneDisplayFormat = 2;
        break;
    }
    $as_link = !empty($element['#as_link']);

    if ($mobile_number = $util->getMobileNumber($value['value'], NULL, [])) {
      if (!empty($as_link)) {
        $element = [
          '#type' => 'link',
          '#title' => $util->libUtil()->format($mobile_number, $phoneDisplayFormat),
          '#url' => Url::fromUri("tel:" . $util->getCallableNumber($mobile_number)),
        ];
      }
      else {
        $element = [
          '#plain_text' => $util->libUtil()->format($mobile_number, $phoneDisplayFormat),
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'mobile_number_international';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'mobile_number_international' => $this->t('International'),
      'mobile_number_local' => $this->t('Local Number'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $data = $webform_submission->getData($element['#webform_key']);
    return !empty($data['value']) ? $webform_submission->getData($element['#webform_key'])['value'] : '';
  }

}
