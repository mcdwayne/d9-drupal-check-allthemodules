<?php

namespace Drupal\algolia_search_custom\Form;

use Drupal\Core\Form\FormBase;
use  Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchSettingsForm.
 */
class SearchSettingsForm extends FormBase
{

  /**
   * Drupal\Core\State\State definition.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;
  /**
   * Constructs a new SearchSettingsForm object.
   */
  public function __construct(StateInterface $state)
  {
    $this->state = $state;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('state')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'search_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $settingsName = 'algolia_search_custom_settings_';

    $form['app_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('App Id'),
      '#maxlength'     => 64,
      '#size'          => 64,
      '#default_value' => $this->state->get($settingsName . 'app_id'),
    ];

    $form['api_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('API Key'),
      '#maxlength'     => 64,
      '#size'          => 64,
      '#default_value' => $this->state->get($settingsName . 'api_key'),
    ];

    $form['api_admin_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('API Admin Key'),
      '#description'   => '',
      '#maxlength'     => 64,
      '#size'          => 64,
      '#default_value' => $this->state->get($settingsName . 'api_admin_key'),
    ];

    $form['index_name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Index Name'),
      '#maxlength'     => 64,
      '#size'          => 64,
      '#default_value' => $this->state->get($settingsName . 'index_name'),
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $settingsName = 'algolia_search_custom_settings_';

    $fields = [
      'app_id',
      'api_key',
      'api_admin_key',
      'index_name',
    ];

    // Save result.
    foreach ($fields as $field) {
      $this->state->set($settingsName . $field, $form_state->getValue($field));
    }

    drupal_set_message('Paramètres mise à jour.');
  }

}
