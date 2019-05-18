<?php

namespace Drupal\br_address_field\Plugin\Field\FieldWidget;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\InitialValuesEvent;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use GuzzleHttp\Client;

/**
 * Plugin implementation of the 'br_address_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "br_address_widget_type",
 *   label = @Translation("Brazilian address"),
 *   field_types = {
 *     "br_address_field_type"
 *   }
 * )
 */
class BrAddressWidgetType extends WidgetBase {

  use MessengerTrait;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'consult_postal_code' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['consult_postal_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fill address'),
      '#description' => $this->t('Auto fill address by postal code field.'),
      '#default_value' => $this->getSetting('consult_postal_code'),
      '#required' => FALSE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Auto fill address: @consult_postal_code', ['@consult_postal_code' => $this->getSetting('consult_postal_code') == 1 ? $this->t('True') : $this->t('False')]);

    return $summary;
  }

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @see address_form_field_config_edit_form_alter()
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues() {
    $initial_values = [
      'postal_code' => '',
      'thoroughfare' => '',
      'number' => '',
      'street_complement' => '',
      'neighborhood' => '',
      'city' => '',
      'state' => '',
    ];

    return $initial_values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $value = $item->getEntity()->isNew() ? $this->getInitialValues() : $item->toArray();

    $states = [
      'AC' => 'Acre',
      'AL' => 'Alagoas',
      'AP' => 'Amapá',
      'AM' => 'Amazonas',
      'BA' => 'Bahia',
      'CE' => 'Ceará',
      'DF' => 'Distrito Federal',
      'ES' => 'Espírito Santo',
      'GO' => 'Goiás',
      'MA' => 'Maranhão',
      'MT' => 'Mato Grosso',
      'MS' => 'Mato Grosso do Sul',
      'MG' => 'Minas Gerais',
      'PA' => 'Pará',
      'PB' => 'Paraíba',
      'PR' => 'Paraná',
      'PE' => 'Pernambuco',
      'PI' => 'Piauí',
      'RJ' => 'Rio de Janeiro',
      'RN' => 'Rio Grande do Norte',
      'RS' => 'Rio Grande do Sul',
      'RO' => 'Rondônia',
      'RR' => 'Roraima',
      'SC' => 'Santa Catarina',
      'SP' => 'São Paulo',
      'SE' => 'Sergipe',
      'TO' => 'Tocantins',
    ];

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#attributes' => [
        'id' => 'address-container',
      ],
    ];

    $element['#attached']['library'][] = 'br_address_field/theme';

    $element['postal_code'] = [
      '#type' => 'textfield',
      '#default_value' => $value['postal_code'],
      '#required' => $this->fieldDefinition->isRequired(),
      '#consult_postal_code' => $this->getFieldSetting('consult_postal_code'),
      '#title' => $this->t('Postal code'),
      '#ajax' => [
        'callback' => [$this, 'ajaxConsultZip'],
        'event' => 'change',
        'wrapper' => 'address-container',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
    ];

    $element['thoroughfare'] = [
      '#type' => 'textfield',
      '#default_value' => $value['thoroughfare'],
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $this->t('Thoroughfare'),
      '#attributes' => [
        'id' => 'thoroughfare',
      ],
    ];

    $element['number'] = [
      '#type' => 'textfield',
      '#default_value' => $value['number'],
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $this->t('Number'),
      '#attributes' => [
        'id' => 'street-number',
      ],
    ];

    $element['street_complement'] = [
      '#type' => 'textfield',
      '#default_value' => $value['street_complement'],
      '#required' => FALSE,
      '#title' => $this->t('Complement'),
      '#attributes' => [
        'id' => 'street-complement',
      ],
    ];

    $element['neighborhood_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'neighborhood-wrapper',
      ],
    ];

    $element['neighborhood'] = [
      '#type' => 'textfield',
      '#default_value' => $value['neighborhood'],
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $this->t('Neighborhood'),
      '#attributes' => [
        'id' => 'neighborhood',
      ],
    ];

    $element['city_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'city-wrapper',
      ],
    ];

    $element['city'] = [
      '#type' => 'textfield',
      '#default_value' => $value['city'],
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $this->t('City'),
      '#attributes' => [
        'id' => 'city',
      ],
    ];

    $element['state_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'state-wrapper',
      ],
    ];

    $element['state'] = [
      '#type' => 'select',
      '#options' => $states,
      '#default_value' => $value['state'],
      '#required' => $this->fieldDefinition->isRequired(),
      '#title' => $this->t('State'),
      '#attributes' => [
        'id' => 'state',
      ],
    ];

    return $element;
  }

  /**
   * Call the function that consume the webservice.
   *
   * @param array $form
   *   A form that be modified.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The values of the form.
   *
   * @return array
   *   The form modified
   */
  public function ajaxConsultZip(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    $address = reset($form_state->getValue($field_name));
    $result = $this->consultZip($address['postal_code']);
    $form = $form[$field_name];

    if (!isset($result->erro)) {
      $form['widget'][0]['postal_code']['#value'] = $result->cep;
      $form['widget'][0]['thoroughfare']['#value'] = $result->logradouro;
      $form['widget'][0]['number']['#value'] = '';
      $form['widget'][0]['street_complement']['#value'] = '';
      $form['widget'][0]['neighborhood']['#value'] = $result->bairro;
      $form['widget'][0]['city']['#value'] = $result->localidade;
      $form['widget'][0]['state']['#value'] = $result->uf;
    }
    else {
      $this->messenger()->addError($this->t('Postal code not found.'));

      $form['widget'][0]['postal_code']['#value'] = '';
      $form['widget'][0]['thoroughfare']['#value'] = '';
      $form['widget'][0]['number']['#value'] = '';
      $form['widget'][0]['street_complement']['#value'] = '';
      $form['widget'][0]['neighborhood']['#value'] = '';
      $form['widget'][0]['city']['#value'] = '';
      $form['widget'][0]['state']['#value'] = '';
    }

    return $form;
  }

  /**
   * Consume viacep webservice.
   *
   * @param int $zip
   *   The postal code to consult.
   *
   * @return mixed
   *   Street, Neighborhood, City and state of the postal code.
   */
  public function consultZip($zip) {
    $client = new Client(["http://viacep.com.br/ws/" . $zip . "/json/"]);
    $result = $client->request('get', "http://viacep.com.br/ws/" . $zip . "/json/", ['Accept' => 'application/json']);
    $output = $result->getBody()->getContents();
    $address = json_decode($output);

    return $address;
  }

}
