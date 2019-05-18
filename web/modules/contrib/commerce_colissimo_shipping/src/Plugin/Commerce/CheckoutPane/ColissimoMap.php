<?php

namespace Drupal\commerce_colissimo_shipping\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use \SoapClient;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\Node;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * @CommerceCheckoutPane(
 *  id = "commerce_checkout_pane",
 *  label = @Translation("Colissimo map"),
 *  display_label = @Translation("Colissimo map"),
 *  default_step = "string",
 *  wrapper_element = "string",
 * )
 */
class ColissimoMap extends CheckoutPaneBase implements  CheckoutPaneInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The parent checkout flow.
   *
   * @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesInterface
   */
  protected $checkoutFlow;

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The endpoint.
   */
  protected $endpoint;

  /**
   * Constructs a new CheckoutPaneBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->checkoutFlow = $checkout_flow;
    $this->order = $checkout_flow->getOrder();
    $this->setConfiguration($configuration);
    $this->entityTypeManager = $entity_type_manager;
    $this->endpoint = $this->configuration['endpoint'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default['endpoint'] = '';
    $default['username'] = '';
    $default['pass'] = '';
    return $default + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['endpoint'])) {
      $summary = $this->t('api configured: Yes');
    }
    else {
      $summary = $this->t('api configured: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api url'),
      '#default_value' => $this->configuration['endpoint'],
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
    ];
    $form['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['pass'],
    ];
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['endpoint'] = $values['endpoint'];
      $this->configuration['username'] = $values['username'];
      $this->configuration['pass'] = $values['pass'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    global $base_root;
    $current_path = \Drupal::service('path.current')->getPath();
    $alias_path = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
    $current_url = $base_root . $alias_path;
    $module_path = base_path() . drupal_get_path('module', 'commerce_colissimo_shipping');
    $icon_url = $base_root . $module_path . '/image/icons/domicile.png';

    // arguments to get points from colissimo Api.
    $order_date = date('d/m/Y', $this->order->getCreatedTime());
    $arguments = [
      'zipCode' => isset($_GET['postal_code']) ? $_GET['postal_code'] : '75001',
      'city' => isset($_GET['city']) ? $_GET['city'] : 'Paris',
      'countryCode' => isset($_GET['country_code']) ? $_GET['country_code'] : 'FR',
      'address' => isset($_GET['address']) ? $_GET['address'] : '',
      'shippingDate' => $order_date,
      'weight' => (float) 1000,
      'optionInter' => 1, // Permet de filtrer ou non les points situés à l’étranger
    ];

    $geofield = $this->getApiColissimo('findRDVPointRetraitAcheminement',$arguments);

    $pane_form['container'] = array(
      '#type' => 'details',
      '#title' => $this->t('Maps container'),
      '#attributes' => array(
        'class' => array(
          'map-container',
        ),
      ),
      '#open' => TRUE,
    );
    $pane_form['container']['current_url'] = array(
      '#type' => 'value',
      '#value' => $current_url,
    );
    $pane_form['container']['msj'] = [
      '#markup' => $this->t('This is a custom completion message.'),
    ];
    $pane_form['container']['postal_code'] = [
      '#type' => 'textfield',
      '#title' => 'Postal Code',
      '#default_value' => isset($_GET['postal_code']) ? $_GET['postal_code'] : '75001',
    ];
    $pane_form['container']['city'] = [
      '#type' => 'textfield',
      '#title' => 'City',
      '#default_value' => isset($_GET['city']) ? $_GET['city'] : 'Paris',
    ];
    $pane_form['container']['country'] = [
      '#type' => 'select',
      '#title' => 'City',
      '#empty_option' => '- Empty -',
      '#options' => [
        'FR' => 'France',
        'BE' => 'Belgique'
      ],
      '#default_value' => isset($_GET['country_code']) ? $_GET['country_code'] : 'FR',
    ];
    $pane_form['container']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
      '#ajax' => array(
        'callback' => [get_class($this), 'submitPaneAjaxForm'],
        'method' => 'click',
        'effect' => 'fade',
      ),
    );
    $pane_form['container']['maps'] = [
      '#theme' => 'colissimo_shipping_maps',
      '#description' => 'Maps description',
      '#width' => '100%',
      '#height' => '500px',
      '#attached' => array(
        'drupalSettings' => array(
          // Return the values of the offices.
          'offices' => $geofield,
          'icon_marker' => $icon_url,
        ),
      ),
    ];

    $pane_form['#attached']['library'][] = 'commerce_colissimo_shipping/colissimo_map';
    $pane_form['#cache']['max-age'] = 0;

    return $pane_form;

  }
  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Validates the pane form.
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    if (!$form_state->getErrors()) {
    }
  }

  public function submitPaneAjaxForm(array &$pane_form, FormStateInterface $form_state) {

      $values = $form_state->getValue($pane_form['#parents']);

      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new InvokeCommand(NULL, 'redirectCheckoutForm', [$values]));

      return $ajax_response;

  }

  /**
   * {@inheritdoc}
   */
  public function getApiColissimo($method, $arguments, &$error = NULL) {
    // Handles the submission of an pane form.
    $arguments += array(
      'accountNumber' => $this->configuration['username'],
      'password' => $this->configuration['pass'],
    );

    try {
      $arrContextOptions = array(
        'ssl' => array(
          'verify_peer' => false,
          'verify_peer_name' => false,
          'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
        ),
      );

      $options = array(
        'soap_version'=>SOAP_1_1,
        'exceptions'=>true,
        'trace'=>1,
        'cache_wsdl'=>WSDL_CACHE_NONE,
        'connection_timeout' => 30,
        'stream_context' => stream_context_create($arrContextOptions)
      );

      $client = new SoapClient($this->endpoint, $options);
      $result = $client->$method($arguments);

      // return $result;

      $offices = array();
      $offices[] = [
        'name' => 'PARIS ITALIE',
        'lat' => 48.8287,
        'lon' => 2.35678,
        'adress' => '23 AVENUE D ITALIE',
        'city' => 'PARIS',
        'country' => 'France',
        'codePostal' => 75013,
      ];
      $offices[] = [
        'name' => 'PARIS OLYMPIADES',
        'lat' => 48.824,
        'lon' => 2.36348,
        'adress' => '19 RUE SIMONE WEIL',
        'city' => 'PARIS',
        'country' => 'France',
        'codePostal' => 75013,
      ];
      return $offices;

    } catch (Exception $e) {
      return $e->getMessage();
    }

  }

}
