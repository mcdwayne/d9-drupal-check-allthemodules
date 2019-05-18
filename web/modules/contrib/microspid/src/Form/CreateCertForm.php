<?php

namespace Drupal\microspid\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form builder for the microspid basic checkbox form.
 */
class CreateCertForm extends FormBase {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;
  
  protected $certman;

  /**
   * {@inheritdoc}
   *
   */
  public function __construct() {
    $this->config = \Drupal::config('microspid.settings');
    $this->certman = \Drupal::service('microspid.certs.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'microspid_cert_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
	
    $form['countryName'] = array(
      '#name' => 'countryName',
      '#type' => 'textfield',
      '#maxlength' => 2,
      '#size' => 2,
      '#title' => 'countryName:',
      '#required' => TRUE,
      '#default_value' => 'IT',
    );
    $province = array(
      'Agrigento' => 'Agrigento',
      'Alessandria' => 'Alessandria',
      'Ancona' => 'Ancona',
      'Aosta' => 'Aosta',
      'Arezzo' => 'Arezzo',
      'Ascoli Piceno' => 'Ascoli Piceno',
      'Asti' => 'Asti',
      'Avellino' => 'Avellino',
      'Bari' => 'Bari',
      'Barletta-Andria-Trani' => 'Barletta-Andria-Trani',
      'Belluno' => 'Belluno',
      'Benevento' => 'Benevento',
      'Bergamo' => 'Bergamo',
      'Biella' => 'Biella',
      'Bologna' => 'Bologna',
      'Bolzano' => 'Bolzano',
      'Brescia' => 'Brescia',
      'Brindisi' => 'Brindisi',
      'Cagliari' => 'Cagliari',
      'Caltanissetta' => 'Caltanissetta',
      'Campobasso' => 'Campobasso',
      'Carbonia-Iglesias' => 'Carbonia-Iglesias',
      'Caserta' => 'Caserta',
      'Catania' => 'Catania',
      'Catanzaro' => 'Catanzaro',
      'Chieti' => 'Chieti',
      'Como' => 'Como',
      'Cosenza' => 'Cosenza',
      'Cremona' => 'Cremona',
      'Crotone' => 'Crotone',
      'Cuneo' => 'Cuneo',
      'Enna' => 'Enna',
      'Fermo' => 'Fermo',
      'Ferrara' => 'Ferrara',
      'Firenze' => 'Firenze',
      'Foggia' => 'Foggia',
      'Forli-Cesena' => 'Forli-Cesena',
      'Frosinone' => 'Frosinone',
      'Genova' => 'Genova',
      'Gorizia' => 'Gorizia',
      'Grosseto' => 'Grosseto',
      'Imperia' => 'Imperia',
      'Isernia' => 'Isernia',
      'La Spezia' => 'La Spezia',
      'L\'Aquila' => 'L\'Aquila',
      'Latina' => 'Latina',
      'Lecce' => 'Lecce',
      'Lecco' => 'Lecco',
      'Livorno' => 'Livorno',
      'Lodi' => 'Lodi',
      'Lucca' => 'Lucca',
      'Macerata' => 'Macerata',
      'Mantova' => 'Mantova',
      'Massa-Carrara' => 'Massa-Carrara',
      'Matera' => 'Matera',
      'Messina' => 'Messina',
      'Milano' => 'Milano',
      'Modena' => 'Modena',
      'Monza e Brianza' => 'Monza e Brianza',
      'Napoli' => 'Napoli',
      'Novara' => 'Novara',
      'Nuoro' => 'Nuoro',
      'Ogliastra' => 'Ogliastra',
      'Olbia-Tempio' => 'Olbia-Tempio',
      'Oristano' => 'Oristano',
      'Padova' => 'Padova',
      'Palermo' => 'Palermo',
      'Parma' => 'Parma',
      'Pavia' => 'Pavia',
      'Perugia' => 'Perugia',
      'Pesaro e Urbino' => 'Pesaro e Urbino',
      'Pescara' => 'Pescara',
      'Piacenza' => 'Piacenza',
      'Pisa' => 'Pisa',
      'Pistoia' => 'Pistoia',
      'Pordenone' => 'Pordenone',
      'Potenza' => 'Potenza',
      'Prato' => 'Prato',
      'Ragusa' => 'Ragusa',
      'Ravenna' => 'Ravenna',
      'Reggio Calabria' => 'Reggio Calabria',
      'Reggio Emilia' => 'Reggio Emilia',
      'Rieti' => 'Rieti',
      'Rimini' => 'Rimini',
      'Roma' => 'Roma',
      'Rovigo' => 'Rovigo',
      'Salerno' => 'Salerno',
      'Medio Campidano' => 'Medio Campidano',
      'Sassari' => 'Sassari',
      'Savona' => 'Savona',
      'Siena' => 'Siena',
      'Siracusa' => 'Siracusa',
      'Sondrio' => 'Sondrio',
      'Taranto' => 'Taranto',
      'Teramo' => 'Teramo',
      'Terni' => 'Terni',
      'Torino' => 'Torino',
      'Trapani' => 'Trapani',
      'Trento' => 'Trento',
      'Treviso' => 'Treviso',
      'Trieste' => 'Trieste',
      'Udine' => 'Udine',
      'Varese' => 'Varese',
      'Venezia' => 'Venezia',
      'Verbano-Cusio-Ossola' => 'Verbano-Cusio-Ossola',
      'Vercelli' => 'Vercelli',
      'Verona' => 'Verona',
      'Vibo Valentia' => 'Vibo Valentia',
      'Vicenza' => 'Vicenza',
      'Viterbo' => 'Viterbo',
    );
    $form['stateOrProvinceName'] = array(
      '#name' => 'stateOrProvinceName',
      '#type' => 'select',
      '#title' => 'stateOrProvinceName:',
      '#options' => $province,
      '#required' => TRUE,
    );
    $form['localityName'] = array(
      '#name' => 'localityName',
      '#type' => 'textfield',
      '#title' => 'localityName:',
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => '',
    );
    $form['organizationName'] = array(
      '#name' => 'organizationName',
      '#type' => 'textfield',
      '#title' => 'organizationName:',
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => '',
    );
    $form['organizationalUnitName'] = array(
      '#name' => 'organizationalUnitName',
      '#type' => 'textfield',
      '#title' => 'organizationalUnitName:',
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => '',
    );
    $form['commonName'] = array(
      '#name' => 'commonName',
      '#type' => 'textfield',
      '#title' => 'commonName (dominio):',
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => $_SERVER['SERVER_NAME'],
    );
    $form['emailAddress'] = array(
      '#name' => 'emailAddress',
      '#type' => 'textfield',
      '#title' => 'emailAddress:',
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => '',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#disabled' => $this->certman->certExists() ? 'disabled' : '',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    global $base_url;
    $dn = array(
      "countryName" => $form_state->getValue('countryName'),
      "stateOrProvinceName" => $form_state->getValue('stateOrProvinceName'),
      "localityName" => $form_state->getValue('localityName'),
      "organizationName" => $form_state->getValue('organizationName'),
      "organizationalUnitName" => $form_state->getValue('organizationalUnitName'),
      "commonName" => $form_state->getValue('commonName'),
      "emailAddress" => $form_state->getValue('emailAddress'),
    );
    $this->certman->makeCerts($dn);
    if ($this->certman->certExists()) {
      drupal_set_message($this->t('Certificate has been created...'));
    }
    $response = new RedirectResponse($base_url . '/admin/config/people/microspid');
    $response->send();
    exit;
  }
}
