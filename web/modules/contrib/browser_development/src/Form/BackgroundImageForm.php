<?php
/**
 *
 */

namespace Drupal\browser_development\Form;


use Drupal\file\Entity\File;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Config\ConfigFactoryInterface;


/**
 * Class BrowserForm.
 *
 * @package Drupal\browser_development\Form
 */
class BackgroundImageForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configDBSettings;


  /**
   * @var \Drupal\browser_development\Form\DeleteCssFromDisk
   */
  protected $deleteCssFromDisk;

  /**
   * @var \Drupal\browser_development\Form\SavingCssToDisk
   */
  protected $saveCssToDisk;

  /**
   * @var string
   */
  protected $globalFilePath = 'public://browser-development/';

  /**
   * BrowserCssForm constructor.
   * @param ConfigFactoryInterface $config_factory
   */
  function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->configDBSettings = \Drupal::config('browser_development.settings');
    $this->deleteCssFromDisk = new DeleteCssFromDisk();
    $this->saveCssToDisk = new SavingCssToDisk();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'browser_development.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'browser_development_image_form';
  }

  /**
   * {@inheritdoc}
   * @todo these buttons in method will need to be added for future functionality
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $fields['file'] = BaseFieldDefinition::create('file');
    $form['background_image'] = array(
      '#type' => 'managed_file',
      '#title' => t('Choose Image File'),
      '#upload_location' => 'public://browser-development/images/',
      '#default_value' => '',
      '#description' => t('Specify an image(s) to display.'),
      '#states' => array(
        'visible' => array(
          ':input[name="image_type"]' => array('value' => t('Upload New Image(s)')),
        ),
      ),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save image'),
    );

    $form['background_image_field'] = [
      '#title' => '',
      '#type' => 'textarea',
      '#attributes' => array(
        'placeholder' => t('Add background image css here......'),
      ),
    ];

    return $form;

  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
   File::load($form_state->getValue('background_image'));
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {



    //-- Fetch the array of the file stored temporarily in database
    $image = $form_state->getValue('background_image');


    //print_r($image); exit;
    //-- Load the object of the file by it's fid
    $file = File::load($image[0]);


    //-- Set the status flag permanent of the file object
    $file->setPermanent();

    //-- Save the file in database
    $file->save();


    $id = $form_state->getValue('background_image_field');
    $name = $form_state->getValue('background_image')[0];


    $imageCss = '#'. $id . '{background-image:url("sites/default/files/browser-development/images/' . $name . '") no-repeat scroll center top; background-size: cover;}';

    $this->saveCssToDisk->setBackgroundImage($imageCss);
  }

}
