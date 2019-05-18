<?php

namespace Drupal\country_state_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\country_state_field\Entity\Country;
use Drupal\country_state_field\Entity\State;
use Drupal\country_state_field\Entity\City;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Constructs a MyClass object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, TranslationInterface $string_translation) {
    parent::__construct($config_factory, $messenger, $string_translation);
    // You can skip injecting this service, the trait will fallback to \Drupal::translation()
    // but it is recommended to do so, for easier testability,
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'country_state_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'country_state_field_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $config = $this->config('country_state_field.settings');.
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#title' => $this->t('Import'),
      '#description' => $this->t('Import country, state and cities data.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $batch = [
      'title' => $this->t('Exporting'),
      'operations' => [
        ['Drupal\country_state_field\Form\SettingsForm::importCountry', []],
        // ['Drupal\country_state_field\Form\SettingsForm::importState', []],
        // ['Drupal\country_state_field\Form\SettingsForm::importCity', []],
      ],
      'finished' => 'importDataFinish',
    ];

    batch_set($batch);

    $this->config('country_state_field.settings')
      ->set('import', $form_state->getValue('import'))
      ->save();
  }

  /**
   * Undocumented function.
   *
   * @param array $context
   *   The context param.
   */
  public static function importCountry(array &$context) {

    $module_path = drupal_get_path('module', 'country_state_field');

    // Importando os dados dos paises.
    $countries = json_decode(file_get_contents($module_path . '/data/country_en.json'));

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = count($countries);
    }

    // Criando um registro na entidade country para cada pais importado.
    foreach ($countries as $country) {
      $new_country = Country::create([
        'id' => $country->id,
        'name' => $country->name,
      ]);
      $new_country->save();

      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $country->id;

    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }

  }

  /**
   * Undocumented function.
   *
   * @param array $context
   *   The context param.
   */
  public static function importState(array &$context) {

    $module_path = drupal_get_path('module', 'country_state_field');

    // Importando os dados dos estados.
    $states = json_decode(file_get_contents($module_path . '/data/state_en.json'));

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = count($states);
    }

    // Criando um registro na entidade country para cada pais importado.
    foreach ($states as $state) {
      $new_state = State::create([
        'id' => $state->id,
        'name' => $state->name,
        'country_id' => $state->country_id,
      ]);
      $new_state->save();

      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $state->id;
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Undocumented function.
   *
   * @param array $context
   *   The context param.
   */
  public static function importCity(array &$context) {

    $module_path = drupal_get_path('module', 'country_state_field');

    // Importando os dados das cidade.
    $cities = json_decode(file_get_contents($module_path . '/data/city_en.json'));

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = count($cities);
    }

    // Criando um registro na entidade state para cada pais importado.
    foreach ($cities as $city) {
      $new_city = City::create([
        'id' => $city->id,
        'name' => $city->name,
        'state_id' => $city->state_id,
      ]);
      $new_city->save();

      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $city->id;
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Undocumented function.
   *
   * @param bool $success
   *   The success param.
   * @param array $results
   *   The results param.
   * @param array $operations
   *   The operations param.
   */
  public function importDataFinish(bool $success, array $results, array $operations) {

    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = $this->translation()
        ->formatPlural(count($results), 'One post processed.', '@count posts processed.');
    }
    else {
      $message = $this->t('Finished with an error.');
    }
    $this->messenger()->addMessage($message);
  }

}
