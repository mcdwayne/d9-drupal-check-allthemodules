<?php

namespace Drupal\bulk_alias_checker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\FileUsageInterface;

/**
 * Implements a form.
 */
class AliascheckerForm extends FormBase {
   /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, \Drupal\file\FileUsage\FileUsageInterface $file_usage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
  }
/**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file.usage')
    );
  }
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'bulk_alias_checker_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['bulk_alias_checkerholder'] = array(
            '#type' => 'fieldset',
            '#description' => $this->t('Upload a CSV file.'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
        );

        $form['bulk_alias_checkerholder']['file_upload'] = array(
            '#type' => 'managed_file',
            '#title' => $this->t('CSV File'),
            '#size' => 40,
            '#upload_location' => 'public://bulk_alias_checkerholder/',
            '#description' => $this->t('Select the CSV file to check whether Alias are exist or not.'),
            '#upload_validators' => array('file_validate_extensions' => array('csv')),
        );

        $form['bulk_alias_checkerholder']['sample_file'] = array(
            '#markup' => '<p>' . Link::fromTextAndUrl($this->t('Click here to download sample file'), Url::fromRoute('sample_bulk_alias_checker'))->toString() . '</p>',
        );

        $form['bulk_alias_checkerholder']['submit_button'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
       
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $line_max = 2000;
        $formstate = $form_state->getValues();
        $fid = $formstate['file_upload'][0];
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        $file->setPermanent();
        $file->save();
        $url = $file->getFileUri();
        $filepath = file_create_url($url);
        $handle = fopen($filepath, 'r');


        $send_counter = 0;
        $data = array();
        while ($row = fgetcsv($handle, $line_max, ',')) {
            if ($send_counter != 0) {
                $data[] = $row;
            }
            $send_counter++;
        }

//   $operations = array();
//
  foreach ($data as $csvdata) {
    $first_string = trim($csvdata[0]);
    if (!empty($first_string) && isset($first_string)) {
      $operations[] = array('bulk_alias_checker_op', array($csvdata));
    }
  }


        $batch = array(
            'title' => $this->t('Checking the node alias finally....please wait....!'),
            'operations' => $operations,
            'init_message' => $this->t('Node alias checking started...please wait....!'),
            'finished' => 'bulk_alias_checker_op_batch_finished',
            'file' => drupal_get_path('module', 'bulk_alias_checker') . '/includes/bulk_alias_checker.inc',
            'progress_message' => $this->t('Processed @current out of @total.'),
            'error_message' => $this->t('Node alias checking process has encountered an error.'),
        );

        batch_set($batch);
    }

}
