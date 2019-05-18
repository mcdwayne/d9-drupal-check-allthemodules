<?php

namespace Drupal\image_moderate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image_moderate\AzureImageModerate;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImageModerateSettingsForm.
 *
 * @package Drupal\image_moderate\Form
 */
class ImageModerateSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_moderate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'image_moderate.settings',
    ];
  }

  /**
   * The file AzureImageModerate service.
   *
   * @var Drupal\image_moderate\AzureImageModerate
   */
  protected $azureimagemoderate;

  /**
   * The Module Handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $modulehandler;

  /**
   * Class constructor.
   */
  public function __construct(AzureImageModerate $azure_imagemoderate, ModuleHandlerInterface $module_handler) {
    $this->azureimagemoderate = $azure_imagemoderate;
    $this->modulehandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('image_moderate.get_data'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('image_moderate.settings');

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Automatic image moderation settings'),
      '#open' => TRUE,
      '#description' => $this->t('Thanks for installing Automatic image moderate! To start working, enter your API key. Don\'t have one yet? Sign up <a href="@url" target="_blank">@url</a>.', [
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
      '#description' => $this->t('Enter the URL of your Endpoint here. fe. https://westeurope.api.cognitive.microsoft.com/contentmoderator/moderate/v1.0/ProcessImage/Evaluate for West Europe'),
    ];

    $form['racist'] = [
      '#type' => 'number',
      '#title' => $this->t('Treshhold in % for racist content'),
      '#description' => 'Images with a higher value will be flagged as containing racist content. 40% should be a good point to start.',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $config->get('racist') ? $config->get('racist') : 40,
    ];

    $form['adult'] = [
      '#type' => 'number',
      '#title' => $this->t('Treshhold in % for adult content'),
      '#description' => 'Images with a higher value will be flagged as containing adult content. 40% should be a good point to start.',
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $config->get('adult') ? $config->get('adult') : 40,
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
    $path = $this->modulehandler->getModule('image_moderate')->getPath();

    $request = $this->azureimagemoderate->getdata($path . '/image/test.jpg', $endpoint, $api_key);

    if ($request !== FALSE && $request->getStatusCode() == 200) {
      drupal_set_message($this->t('Your settings have been successfully validated'), 'status');
    }
    else {
      if ($request !== FALSE && $request->getStatusCode() == 401) {
        $form_state->setErrorByName('api_key', $this->t('The API Key seems to be wrong. Please check in your Azure Console.'));
      }
      else {
        $form_state->setErrorByName('endpoint', $this->t('The URL for the endpoint seems to be wrong. Please check in your Azure Console. %error', ['%error' => $request->getStatusCode()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('image_moderate.settings')
      ->set('api_key', $values['api_key'])
      ->set('endpoint', $values['endpoint'])
      ->set('racist', $values['racist'])
      ->set('adult', $values['adult'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
