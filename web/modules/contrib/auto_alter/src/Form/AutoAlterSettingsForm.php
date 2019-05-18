<?php

namespace Drupal\auto_alter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\auto_alter\AzureVision;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AutoAlterSettingsForm.
 *
 * @package Drupal\auto_alter\Form
 */
class AutoAlterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_alter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'auto_alter.settings',
    ];
  }

  /**
   * The file AzureVision service.
   *
   * @var Drupal\auto_alter\AzureVision
   */
  protected $azurevision;

  /**
   * The Module Handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $modulehandler;

  /**
   * Class constructor.
   */
  public function __construct(AzureVision $azure_vision, ModuleHandlerInterface $module_handler) {
    $this->azurevision = $azure_vision;
    $this->modulehandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('auto_alter.get_description'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auto_alter.settings');

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Automatic Alternative Text settings'),
      '#open' => TRUE,
      '#description' => $this->t('Thanks for installing Automatic Alternative Text! To start receiving alt text, enter your API key. Don\'t have one yet? Sign up <a href="@url" target="_blank">@url</a>.', [
        '@url' => 'https://www.microsoft.com/cognitive-services',
      ]),
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
      '#description' => $this->t('Enter the URL of your Endpoint here. fe. https://westeurope.api.cognitive.microsoft.com/vision/v1.0/describe?maxCandidates=1 for West Europe'),
    ];

    $form['settings']['status'] = [
      '#type' => 'checkbox',
      '#required' => FALSE,
      '#title' => $this->t('Show status message to user'),
      '#default_value' => $config->get('status'),
      '#description' => $this->t('If checked, a status message is generated after saving: "Alternate text has been changed to: "%text" by a confidence of %confidence"'),
    ];

    $form['settings']['suggestion'] = [
      '#type' => 'checkbox',
      '#required' => FALSE,
      '#title' => $this->t('Make suggestion for alternative text'),
      '#default_value' => $config->get('suggestion'),
      '#description' => $this->t('If checked and alternative text is enabled for the field, a suggestion for the alternative text is created, when image is uploaded to the system.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $api_key = $values['api_key'];
    $endpoint = $values['endpoint'];
    $path = $this->modulehandler->getModule('auto_alter')->getPath();

    $request = $this->azurevision->getdescription($path . '/image/test.jpg', $endpoint, $api_key);

    if ($request !== FALSE && $request->getStatusCode() == 200) {
      drupal_set_message($this->t('Your settings have been successfully validated'), 'status');
    }
    else {
      if ($request !== FALSE && $request->getStatusCode() == 401) {
        $form_state->setErrorByName('api_key', $this->t('The API Key seems to be wrong. Please check in your Azure Console.'));
      }
      else {
        $form_state->setErrorByName('endpoint', $this->t('The URL for the endpoint seems to be wrong. Please check in your Azure Console.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('auto_alter.settings')
      ->set('api_key', $values['api_key'])
      ->set('endpoint', $values['endpoint'])
      ->set('status', $values['status'])
      ->set('suggestion', $values['suggestion'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
