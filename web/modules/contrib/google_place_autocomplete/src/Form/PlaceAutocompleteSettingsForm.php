<?php

namespace Drupal\google_place_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Utility\LinkGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class PlaceAutocompleteSettingsForm.
 *
 * @package Drupal\google_place_autocomplete\Form
 */
class PlaceAutocompleteSettingsForm extends ConfigFormBase {
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;
  /**
   * The link generator service variable.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Constructs LinkGenerator object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State Service Object.
   * @param \Drupal\store_locator\Services\CountryManagerInterface $country_manager
   *   Country Manager Service.
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   Link Generator Service.
   */
  public function __construct(StateInterface $state, CountryManagerInterface $country_manager, LinkGenerator $link_generator) {
    $this->state = $state;
    $this->countryManager = $country_manager;
    $this->linkGenerator = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('country_manager'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'place_autocomplete.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'place_autocomplete_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $countries = $this->countryManager->getStandardList();
    foreach ($countries as $key => $value) {
      $countries[$key] = $value->__toString();
    }
    $google_api = Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
      'attributes' => ['target' => '_blank'],
    ]);
    $api_link = $this->linkGenerator->generate($this->t('Click here'), $google_api);
    $state = $this->state;
    $form['place_autocomplete'] = [
      '#type' => 'details',
      '#title' => $this->t('Gogle Place Autocomplete API settings'),
      '#open' => TRUE,
    ];
    $form['place_autocomplete']['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => ['' => 'All'] + $countries,
      '#default_value' => !empty($state->get('place_country')) ? $state->get('place_country') : '',
      '#description' => $this->t('Restrict the results based on country.'),
    ];
    $form['place_autocomplete']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#size' => 60,
      '#default_value' => !empty($state->get('place_api_key')) ? $state->get('place_api_key') : '',
      '#description' => $this->t('A free API key is needed to use the Google Maps. @click here to generate the API key', [
        '@click' => $api_link,
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = [
      'place_country' => $form_state->getValue('country'),
      'place_api_key' => $form_state->getValue('api_key'),
    ];
    $this->state->setMultiple($values);
  }

}
