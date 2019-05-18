<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 5/9/16
 * Time: 8:18 AM
 */

namespace Drupal\forena_pdf\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PDFGenerationConfigForm extends ConfigFormBase{

  /**
   * [@inheritdoc}
   */
  public function getFormId() {
    return 'forena_pdf_generation_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array('forena_pdf.settings');
  }

  /**
   * [@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('forena_pdf.settings'); 
    $path = $config->get('prince_path');
    $disable_links = $config->get('disable_links');

    $prince_path = t('Prince XML library not found.  Please install so sites/all/libraries/prince/prince.php exists.');
    if (forena_library_file('prince')) {
      $prince_path = 'libraries/prince';
    }


    $form['disable_links'] = array(
      '#type' => 'checkbox',
      '#title' => ('Disable links in PDF Documents'),
      '#description' => t('When checked links in reports will not appear as links in PDF documents.'),
      '#default_value' => $disable_links,
    );

    /*
    $mpdf_path = t('MDPF Libarary not found. Please install so sites/all/libraries/mpdf/mpdf.php exists.');

    if (forena_library_file('mpdf')) {
      $mpdf_path = 'libraries/mpdf';
    }

    $form['mpdf'] = array('#type' => 'fieldset', '#title' => t('MPDF library'));

    $form['mpdf']['library'] = array(
      '#type' => 'item',
      '#title' => 'Installation path',
      '#markup' => $mpdf_path,
    );
    */

    $form['prince'] = array('#type' => 'fieldset', '#title' => t('Prince XML'));

    $form['prince']['library'] = array(
      '#type' => 'item',
      '#title' => 'PHP Library path',
      '#markup' => $prince_path,
    );

    $form['prince']['prince_path'] = array(
      '#type' => 'textfield',
      '#title' => 'Path to binary',
      '#description' => t('Specify the location of the prince executable (e.g. /usr/local/bin/prince'),
      '#required' => TRUE,
      '#default_value' => $path,
    );

    $docraptor_url = $config->get('docraptor_url');
    $docraptor_key = $config->get('docraptor_key');
    $docraptor_test = $config->get('docraptor_test');

    $form['config']['docraptor'] = array('#type' => 'fieldset', '#title' => t("DocRaptor PDF Generation Service"));

    $form['config']['docraptor']['docraptor_url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL to Docraptor Service'),
      '#description' => t('Specify the URL to the PDF Document Generation Service'),
      '#default_value' => $docraptor_url,
    );

    $form['config']['docraptor']['docraptor_key'] = array(
      '#type' => 'textfield',
      '#title' => t('DocRaptor API Key'),
      '#description' => t('Enter the API key for your DocRaptor account here.'),
      '#default_value' => $docraptor_key,
    );

    $form['config']['docraptor']['docraptor_test'] = array(
      '#type' => 'checkbox',
      '#title' => t('Test Mode Document Generation'),
      '#desciption' => t('Generating Documents in Test mode generally does not count towards document counts, but places
      a TEST DOCUMENT header at the top of every page of the document'),
      '#default_value' => $docraptor_test,
    );


    $form['submit'] = array('#type' => 'submit', '#value' => 'Save');
    return parent::buildForm($form, $form_state);
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('forena_pdf.settings')
      ->set('prince_path', $values['prince_path'])
      ->set('disable_links', $values['disable_links'])
      ->set('docraptor_url', $values['docraptor_url'])
      ->set('docraptor_test', $values['docraptor_test'])
      ->set('docraptor_key', $values['docraptor_key'])
      ->save();

    // Call Configuration form to save changes.
    parent::submitForm($form, $form_state);

  }


}