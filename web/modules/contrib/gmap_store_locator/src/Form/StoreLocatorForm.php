<?php

namespace Drupal\store_locator\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\store_locator\Services\GeocoderConsumerService;
use Drupal\store_locator\Helper\GoogleApiKeyHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AlertCommand;

/**
 * Store locator Add/Edit forms.
 *
 * @ingroup store_locator
 */
class StoreLocatorForm extends ContentEntityForm {
  /**
   * The Geocoder service variable.
   *
   * @var \Drupal\store_locator\Services\GeocoderConsumerService
   */
  protected $geoCoder;

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * Constructs Storage & prepare data object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity Manager Interface.
   * @param \Drupal\store_locator\Services\GeocoderConsumerService $geoCoder
   *   Google Geocode Consumer Service.
   * @param \Drupal\store_locator\Services\CountryManagerInterface $country_manager
   *   Country Manager Service.
   */
  public function __construct(EntityManagerInterface $entity_manager, GeocoderConsumerService $geoCoder, CountryManagerInterface $country_manager) {
    parent::__construct($entity_manager);
    $this->geoCoder = $geoCoder;
    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.manager'),
        $container->get('store_locator.geocodes'),
        $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $country_code = $this->config('system.date')->get('country.default');
    $form = parent::buildForm($form, $form_state);
    $form['finder'] = [
      '#type' => 'button',
      '#value' => $this->t('Find Lat/Long'),
      '#weight' => 90,
      '#ajax' => [
        'callback' => '::getGeocodes',
        'event' => 'click',
      ],
    ];
    $form['gmap'] = [
      '#type' => 'container',
      '#weight' => 90,
      '#prefix' => '<div id="map">',
      '#suffix' => '</div>',
    ];
    if (isset($country_code) && !empty($country_code)) {
      $data = $this->geoCoder->geoLatLong($country_code);
      $form['default_map'] = [
        '#type' => 'hidden',
        '#default_value' => implode(',', $data),
      ];
    }
    $form['#attached']['library'][] = 'store_locator/store_locator.page';
    $googleMapKey = GoogleApiKeyHelper::getGoogleApiKey();
    $form['#attached']['html_head'][] = [$googleMapKey, 'googleMapKey'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $lat = is_numeric($entity->get('latitude')->value) ? $entity->get('latitude')->value : NULL;
    $lng = is_numeric($entity->get('longitude')->value) ? $entity->get('longitude')->value : NULL;

    if (empty($lat) || empty($lng)) {
      $address = $entity->get('city')->value . ' ' . $entity->get('address_one')->value . ' ' . $entity->get('postcode')->value;
      $data = $this->geoCoder->geoLatLong($address);
      $entity->latitude->setValue($data['latitude']);
      $entity->longitude->setValue($data['longitude']);
    }
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Store locator.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Store locator.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.store_locator.canonical', [
      'store_locator' => $entity->id(),
    ]);
  }

  /**
   * Get the Latitude & Longitude of the entered location.
   *
   * @see buildForm()
   */
  public function getGeocodes(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $values = $form_state->getValues();

    $city = $values['city'][0]['value'];
    $address_one = $values['address_one'][0]['value'];
    $address_two = $values['address_two'][0]['value'];
    $postcode = $values['postcode'][0]['value'];

    if (empty($city) && empty($address_one) && empty($postcode)) {
      $response->addCommand(new AlertCommand($this->t('Enter the city or address one or postcode.')));
      return $response;
    }
    $address = "$city $address_one $address_two $postcode";
    $data = $this->geoCoder->geoLatLong($address);
    $response->addCommand(new InvokeCommand("input[name='latitude[0][value]']", 'val', [
      $data['latitude'],
    ]));
    $response->addCommand(new InvokeCommand("input[name='longitude[0][value]']", 'val', [
      $data['longitude'],
    ]));
    $response->addCommand(new InvokeCommand('', 'init_map', [
      $data['latitude'],
      $data['longitude'],
    ]));
    $form_state->setRebuild();
    return $response;
  }

}
