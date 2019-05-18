<?php

namespace Drupal\auto_alter_translate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\auto_alter_translate\AzureTranslate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AutoAlterTranslateSettingsForm.
 *
 * @package Drupal\auto_alter_translate\Form
 */
class AutoAlterTranslateSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_alter_translate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'auto_alter_translate.settings',
    ];
  }

  /**
   * The file AzureVision service.
   *
   * @var Drupal\auto_alter_translate\AzureTranslate
   */
  protected $azuretranslate;

  /**
   * Class constructor.
   */
  public function __construct(AzureTranslate $azure_translate) {
    $this->azuretranslate = $azure_translate;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('auto_alter_translate.get_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auto_alter_translate.settings');

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Automatic Alternative Text Translation settings'),
      '#open' => TRUE,
      '#description' => $this->t('The Azure Cognitive Service returns Image description in english only. Use this submodule to translate description with <a href="@url" target="_blank">Microsoft Azure translation API</a> to your current language.', [
        '@url' => 'https://azure.microsoft.com/de-de/services/cognitive-services/translator-text-api/',
      ]),
    ];

    $form['settings']['active'] = [
      '#type' => 'checkbox',
      '#required' => FALSE,
      '#title' => $this->t('Enable translation'),
      '#default_value' => $config->get('active'),
      '#description' => $this->t('Only if checked, configuration of api_key and endpoint is required.'),
    ];

    $form['settings']['api_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Copy the API key here'),
    ];

    $form['settings']['endpoint'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('URL of Endpoint'),
      '#default_value' => $config->get('endpoint'),
      '#description' => $this->t('Enter the URL of your Endpoint here. fe. https://api.cognitive.microsofttranslator.com/languages?api-version=3.0'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $active = $values['active'];
    $api_key = $values['api_key'];
    $endpoint = $values['endpoint'];

    if ($active) {
      $request = $this->azuretranslate->gettranslation('Please translate this text', $endpoint, $api_key, "en", "de");
      if (isset($request) && $request !== FALSE && $request->getStatusCode() == 200) {
        drupal_set_message($this->t('Your settings have been successfully validated'), 'status');
      }
      else {
        if (isset($request) && $request !== FALSE && ($request->getStatusCode() == 400 || $request->getStatusCode() == 401)) {
          $form_state->setErrorByName('api_key', $this->t('The API Key seems to be wrong. Please check in your Azure Console.'));
        }
        elseif (isset($request)) {
          $form_state->setErrorByName('endpoint', $this->t('The URL for the endpoint seems to be wrong. Please check in your Azure Console.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('auto_alter_translate.settings')
      ->set('api_key', $values['api_key'])
      ->set('endpoint', $values['endpoint'])
      ->set('active', $values['active'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
