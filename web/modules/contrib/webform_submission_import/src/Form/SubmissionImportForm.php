<?php
/**
 * Created by PhpStorm.
 * User: dchaf
 * Date: 1/28/2019
 * Time: 2:50 PM
 */

namespace Drupal\webform_submission_import\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Class SubmissionImportForm
 */
class SubmissionImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_submission_import';
  }

  /**
   * The webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformResultsCustomForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler) {
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->requestHandler = $request_handler;
    list($this->webform, $this->sourceEntity) = $this->requestHandler->getWebformEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array(
      '#attributes' => array('enctype' => 'multipart/form-data'),
    );

    $form['file_upload_details'] = array(
      '#markup' => '<p>'.t('Upload a CSV file. First row should contain headers matching field names from the form. Extra columns will be ignored.').'</p>',
    );

    $fieldNames = array();
    $elements = $this->webform->getElementsDecoded();
    $elementsFlattened = \Drupal\webform\Utility\WebformElementHelper::getFlattened($elements);
    $fields = array_filter($elementsFlattened, function($e){
      return $e['#type'] != 'webform_wizard_page';
    });
    $requiredFields = array_filter($fields, function($e){
      return isset($e['#required']) && $e['#required'] == 1;
    });

    $form['help_required_fields'] = array(
      '#markup' => '<strong>Required Fields:</strong> '.implode(', ', array_keys($requiredFields)),
    );
    $form['help_additional_fields'] = array(
      '#markup' => '<br /><strong>Additional Fields:</strong> '.implode(', ', array_keys(array_diff_key($fields, $requiredFields))),
    );


    $validators = array(
      'file_validate_extensions' => array('csv'),
    );
    $form['sub_file'] = array(
      '#type' => 'managed_file',
      '#name' => 'sub_file',
      '#title' => t('Submissions File'),
      '#size' => 20,
      '#description' => t('CSV format only'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://webform_submission_imports/',
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if($form_state->getValue('sub_file') == NULL){
      $form_state->setErrorByName('sub_file', $this->t('File.'));
    }
  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($form_state->getValue('sub_file')[0]);
    $fileuri = $file->get('uri')->value;
    $handle = fopen($fileuri, 'r');
    $fields = [];
    $importCount = 0;
    ini_set('memory_limit', '8192M');
    drupal_set_time_limit(1800);
    while(($rowData = fgetcsv($handle)) !== FALSE){
      if(empty($fields)){
        $fields = $rowData;
        continue;
      }
      $fieldData = array_combine($fields, $rowData);
      if($fieldData !== FALSE) {
        $import = $this->import_row(array_combine($fields, $rowData));
        if($import){
          $importCount++;
        }
      }
    }
    fclose($handle);
    drupal_set_time_limit(120);
    dpm('Successfully imported '.$importCount.' submission(s).');
  }

  private function import_row($rowData = array()){
    $values = [
      'webform_id' => $this->webform->id(),
      'current_page' => 'webform_submission_import',
      'data' => $rowData,
    ];

    if(WebformSubmissionForm::isOpen($this->webform) === TRUE){
      $errors = WebformSubmissionForm::validateFormValues($values);
      if(!empty($errors)){
        \Drupal::logger('webform_submission_import')->error('Error importing record ::: <br /><pre>'.print_r($values, true).'</pre><hr /><pre>'.print_r($errors, true).'</pre>');
        return false;
      }
      else {
        $webform_submission = WebformSubmissionForm::submitFormValues($values);
        if(is_numeric($webform_submission->id()) && $webform_submission->id() > 0){
          return true;
        }
      }
    }
  }
}