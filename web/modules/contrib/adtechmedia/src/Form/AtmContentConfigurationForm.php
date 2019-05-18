<?php

namespace Drupal\atm\Form;

use Drupal\atm\AtmHttpClient;
use Drupal\atm\Helper\AtmApiHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BaseCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class AtmContentConfigurationForm.
 */
class AtmContentConfigurationForm extends AtmAbstractForm {

  /**
   * AtmAbstractForm constructor.
   *
   * @param \Drupal\atm\Helper\AtmApiHelper $atmApiHelper
   *   Provides helper for ATM.
   * @param \Drupal\atm\AtmHttpClient $atmHttpClient
   *   Client for API.
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   *   Default theme handler.
   */
  public function __construct(AtmApiHelper $atmApiHelper, AtmHttpClient $atmHttpClient, ThemeHandler $themeHandler) {
    $this->atmApiHelper = $atmApiHelper;
    $this->atmHttpClient = $atmHttpClient;
    $this->themeHandler = $themeHandler;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'atm-content-configuration';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['content-type'] = $this->getContentTypeSelectSection();
    $form['content-pricing'] = $this->getContentPricingSection();
    $form['content-paywall'] = $this->getContentPaywallSection();
    $form['content-preview'] = $this->getContentPreviewSection();
    $form['content-unlocking-algorithm'] = $this->getContentUnlockAlg();
    $form['video-ad'] = $this->getVideoAd();

    $form['save-content-config'] = [
      '#type' => 'button',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'event' => 'click',
        'callback' => [$this, 'saveParams'],
      ],
    ];

    return $form;
  }

  /**
   * Generate content pricing section.
   */
  private function getContentPricingSection() {
    $contentPricing = [
      '#type' => 'fieldset',
      '#title' => t('Content pricing'),
      '#description' => t('Specify the price and the currency to collect for each article, in case readers decide to use the micropayments choice'),
    ];

    $contentPricing['container'] = [
      '#type' => 'container',
      '#suffix' => '<div class="layout-container"></div>',
    ];

    $contentPricing['container']['price'] = [
      '#type' => 'number',
      '#title' => $this->t('Price'),
      '#title_display' => 'invisible',
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>',
      '#default_value' => $this->getHelper()->get('price'),
      '#required' => TRUE,
      '#attributes' => [
        'min' => 1,
      ],
    ];

    $contentPricing['container']['price_currency'] = [
      '#type' => 'select',
      '#options' => [],
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>',
      '#default_value' => $this->getHelper()->get('price_currency'),
    ];

    foreach ($this->getHelper()->getCurrencyList() as $code => $currency) {
      $contentPricing['container']['price_currency']['#options'][$code] = $currency;
    }

    return $contentPricing;
  }

  /**
   * Generate content paywall section.
   */
  private function getContentPaywallSection() {
    $contentPricing = [
      '#type' => 'fieldset',
      '#title' => t('Content paywall'),
      '#description' => t('Provide the threshold (number of transactions or total amount of pledged currency) that should be used before displaying pay view'),
    ];

    $contentPricing['container'] = [
      '#type' => 'container',
      '#suffix' => '<div class="layout-container"></div>',
    ];

    $contentPricing['container']['payment_pledged'] = [
      '#type' => 'number',
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>',
      '#default_value' => $this->getHelper()->get('payment_pledged'),
    ];

    $contentPricing['container']['content_paywall'] = [
      '#type' => 'select',
      '#options' => [
        'count' => 'transactions',
        'amount' => 'pledged currency',
      ],
      '#prefix'  => '<div class="layout-column layout-column--half">',
      '#suffix'  => '</div>',
      '#default_value' => $this->getHelper()->get('pledged_type'),
    ];

    return $contentPricing;
  }

  /**
   * Generate content preview section.
   */
  private function getContentPreviewSection() {
    $contentPricing = [
      '#type' => 'fieldset',
      '#title' => t('Content preview'),
      '#description' => t('Specify how many paragraphs or words will be shown for free, before displaying unlock view (also known as unlock button)'),
    ];

    $contentPricing['container'] = [
      '#type' => 'container',
      '#suffix' => '<div class="layout-container"></div>',
    ];

    $contentPricing['container']['content_offset'] = [
      '#type' => 'number',
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>',
      '#default_value' => $this->getHelper()->get('content_offset'),
    ];

    $contentPricing['container']['content_offset_type'] = [
      '#type' => 'select',
      '#options' => [
        'elements' => 'paragraphs',
        'words' => 'words',
      ],
      '#prefix' => '<div class="layout-column layout-column--half">',
      '#suffix' => '</div>',
      '#default_value' => $this->getHelper()->get('content_offset_type'),
    ];

    return $contentPricing;
  }

  /**
   * Generate content preview section.
   */
  private function getContentUnlockAlg() {
    $contentPricing = [
      '#type' => 'fieldset',
      '#title' => t('Content unlocking algorithm'),
      '#description' => t('Provide which unlocking algorithm will be used to hide premium content'),
    ];

    $contentPricing['content_lock'] = [
      '#type' => 'select',
      '#options' => [
        'blur+scramble' => 'blur+scramble',
        'blur' => 'blur',
        'scramble' => 'scramble',
        'keywords' => 'keywords',
      ],
      '#default_value' => $this->getHelper()->get('content_lock'),
    ];

    return $contentPricing;
  }

  /**
   * Generate content preview section.
   */
  private function getVideoAd() {
    $contentPricing = [
      '#type' => 'fieldset',
      '#title' => t('Link to video ad'),
      '#description' => t('Speficy the link to video ad that will be used for demo purposes'),
    ];

    $contentPricing['ads_video'] = [
      '#type'    => 'textfield',
      '#default_value' => $this->getHelper()->get('ads_video'),
      '#placeholder' => 'e.g. https://youtu.be/DiBh8r3lPpM',
    ];

    return $contentPricing;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function saveParams(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $errors = $form_state->getErrors();
    if ($errors) {
      $response->addCommand(
        new BaseCommand('showNoty', [
          'options' => [
            'type' => 'error',
            'text' => implode('<br>', $errors),
            'maxVisible' => 1,
            'timeout' => 2000,
          ],
        ])
      );

      $response->addCommand(new ReplaceCommand('.atm-content-configuration', $form));
      return $response;
    }

    $values = $form_state->getValues();

    $this->getHelper()->set('price', $values['price']);
    $this->getHelper()->set('price_currency', $values['price_currency']);

    $this->getHelper()->set('payment_pledged', $values['payment_pledged']);
    $this->getHelper()->set('pledged_type', $values['content_paywall']);

    $this->getHelper()->set('content_offset', $values['content_offset']);
    $this->getHelper()->set('content_offset_type', $values['content_offset_type']);

    $this->getHelper()->set('content_lock', $values['content_lock']);

    $this->getHelper()->set('ads_video', $values['ads_video']);

    $selectedCT = [];
    $cTypes = $form_state->getValue('content-types');
    foreach ($cTypes as $value) {
      if ($value) {
        $selectedCT[] = $value;
      }
    }

    $this->getHelper()->set('selected-ct', $selectedCT);
    $this->getAtmHttpClient()->propertyUpdateConfig();

    $response->addCommand(
      new BaseCommand('showNoty', [
        'options' => [
          'type' => 'information',
          'text' => $this->t('Form data saved successfully'),
          'maxVisible' => 1,
          'timeout' => 2000,
        ],
      ])
    );

    $response->addCommand(new ReplaceCommand('.atm-content-configuration', $form));
    return $response;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $price = (int) $form_state->getValue('price');
    if ($price <= 0) {
      $form_state->setErrorByName('price', $this->t('The `price` field must be greater than zero'));
    }
  }

  /**
   * Generate CT selection section.
   */
  private function getContentTypeSelectSection() {
    $fieldset = [
      '#type' => 'fieldset',
      '#title' => t('Content type'),
      '#description' => t('Select the content type that will work atm module'),
      'container' => [
        '#type' => 'container',
        '#suffix' => '<div class="layout-container"></div>',
      ],
    ];

    $contentTypes = &$fieldset['container']['content-types'];
    $contentTypes = [
      '#type' => 'checkboxes',
      '#options' => [],
      '#default_value' => $this->getHelper()->getSelectedContentTypes(),
    ];

    /** @var \Drupal\node\Entity\NodeType $nodeType */
    foreach (NodeType::loadMultiple() as $nodeType) {
      $contentTypes['#options'][$nodeType->id()] = $nodeType->get('name');
    }

    return $fieldset;
  }

}
