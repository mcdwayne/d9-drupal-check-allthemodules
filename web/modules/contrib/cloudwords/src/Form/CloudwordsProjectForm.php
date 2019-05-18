<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
/**
 * Form controller for Cloudwords project edit forms.
 *
 * @ingroup cloudwords
 */
class CloudwordsProjectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, \Drupal\cloudwords\CloudwordsDrupalProject $cloudwords_project = null) {
    if (is_null($cloudwords_project->getId())) {
      drupal_set_message($this->t('Project not found.'), 'warning');
      return [];
    }
    $form_state->set(['cloudwords_project'], $cloudwords_project);

    $action_statuses = array(
      'configured_project_name',
      'configured_project_details',
      'uploaded_source_materials',
      'configured_bid_options',
      'waiting_for_bid_selection',
      'bid_selection_expired',
    );

    if (in_array($cloudwords_project->getStatus()->getCode(), $action_statuses)) {
      drupal_set_message($this->t('Please finish <a href="@href" target="_blank">creating your project in Cloudwords</a>.', ['@href' => _cloudwords_ui_url() . '/cust.htm#project/' . $cloudwords_project->getId()]), 'warning');
    }

    // Update project info.
    $project_info = [
      'name' => $cloudwords_project->getName(),
      'status' => $cloudwords_project->getStatus()->getCode(),
    ];
    \Drupal::database()->merge('cloudwords_project')->fields($project_info)->key(['id' => $cloudwords_project->getId()])->execute();

    $client = cloudwords_get_api_client();


    // Project details.
    $form['project_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Project Details'),
      '#tree' => TRUE,
    ];
    $form['project_details']['metadata']['source_language'] = [
      '#markup' => $this->t('Source language') . ': ' . $cloudwords_project->getSourceLanguage()->getDisplay() . '<br />',
    ];
    $form['project_details']['metadata']['status'] = [
      '#markup' => $this->t('Status') . ': ' . $cloudwords_project->getStatus()->getCode() . '<br />',
    ];
    $form['project_details']['metadata']['description'] = [
      '#markup' => $this->t('Description') . ': ' . $cloudwords_project->getDescription() . '<br />',
    ];
    $form['project_details']['metadata']['notes'] = [
      '#markup' => $this->t('Notes') . ': ' . $cloudwords_project->getNotes() . '<br />',
    ];
    $form['project_details']['metadata']['delivery_due_date'] = [
      '#markup' => $this->t('Delivery due date') . ': ' . \_cloudwords_format_display_date($cloudwords_project->getDeliveryDueDate()) . '<br />',
    ];

    $options = ['attributes' => ['target' => '_blank']];

    $url = Url::fromUri(_cloudwords_ui_url() . '/cust.htm#project/' . $cloudwords_project->getId());

    $form['project_details']['metadata']['view'] = [
      '#markup' =>  Link::fromTextAndUrl($this->t('View Project in Cloudwords'), $url, $options)->toString(),
    ];

    if ($cloudwords_project->isActive()) {
      $form['project_details']['metadata']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#submit' => array('\Drupal\cloudwords\form\CloudwordsProjectForm::cloudwords_cancel_project_redirect'),
      ];
    }

    // Build language table.
    $files = [];
    try {
      $files = $client->get_project_translated_files($cloudwords_project->getId());
    }
    catch (CloudwordsApiException $e) {}

    $rows = [];
    foreach ($files as $file) {
      $lang_path = '/admin/cloudwords/projects/' . $cloudwords_project->getId() . '/' . $file->getLang()->getLanguageCode();
      $status_code = $file->getStatus()->getCode();

      $operations = [];

      if ($status_code != 'not_delivered') {
        $operations[] = Link::fromTextAndUrl($this->t('Import'), Url::fromUri('internal:' . $lang_path . '/import'))->toString();
        $operations[] = \Drupal::l($this->t('Review in Cloudwords'), Url::fromUri(_cloudwords_ui_url() . '/cust.htm#project/' . $cloudwords_project->getId() . '/language/' . $file->getLang()->getLanguageCode(), ['attributes' => ['target' => '_BLANK']]));
      }

      //$language_status = '';
      $language_status = $cloudwords_project->getLanguageImportStatus($file->getLang());


      $query = \Drupal::database()->select('cloudwords_content', 'cc');
      $query->addJoin('INNER', 'cloudwords_translatable', 'ct', 'ct.id = cc.ctid');
      $query->condition('ct.language', cloudwords_map_cloudwords_drupal($file->getLang()->getLanguageCode()));
      $query->addExpression('COUNT(cc.ctid)', 'ncount');
      $total = $query->execute()->fetchField();
      $failed = $query->condition('cc.status', 3)->execute()->fetchField();

      if ($status_code == 'approved' && $language_status != CLOUDWORDS_LANGUAGE_FAILED) {
        $language_status = $this->t('Imported');
      }
      elseif ($language_status == 1 && ($status_code == 'delivered' || $status_code == 'in_review')) {
        $language_status = $this->t('Imported');
        $operations[] = \Drupal::l($this->t('Approve'), Url::fromUri('internal:/admin/cloudwords/projects/' . $cloudwords_project->getId() . '/' . $file->getLang()->getLanguageCode() . '/approve'));
      }
      elseif ($language_status == CLOUDWORDS_LANGUAGE_APPROVED) {
        $language_status = $this->t('Imported');
      }
      elseif ($language_status == CLOUDWORDS_LANGUAGE_FAILED) {
        $language_status = '<span class="marker">' . $this->t('Failed') . '</span>';
      }
      else {
        $language_status = $this->t('Not imported');
      }

      if ($failed) {
        $language_status = '<span class="marker">' . "$failed/$total failed" . '</span>';
      }

//      if ($project->isDrupalCancelled()) {
//        $operations = array();
//      }

     $operations_render = [
       '#type' => 'markup',
       '#markup' =>implode(' | ', $operations),
     ];

      $row = [
          Link::fromTextAndUrl($file->getLang()->getDisplay(), Url::fromUri('internal:' . $lang_path)),
          $file->getStatus()->getDisplay(),
          $language_status,
          \Drupal::service('renderer')->render($operations_render),
        ];

      $rows[] = $row;
    }


    $header = [
      $this->t('Name'),
      $this->t('Status'),
      $this->t('Import status'),
      $this->t('Operations'),
    ];

    $form['language_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Languages'),
      '#tree' => FALSE,
    ];

    $form['language_wrapper']['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'cloudwords-project-table'],
      '#empty' => $this->t('No languages available.'),
    ];

    // Build reference material table.
    $references = [];
    // @todo what to do when
//    Array
//    (
//      [http_status_code] => 404
//    [request_type] => GET
//    [request_url] => https://api-stage.cloudwords.com/1.16/project/14471/file/reference.json
//    [error_message] => The specified resource was not found.
//)
    try {
      $references = $client->get_project_references($cloudwords_project->getId());
    }
    catch (CloudwordsApiException $e) {
      $references = [];
    }

    $reference_rows = [];
    foreach ($references as $reference) {
      $ops = [
        Link::createFromRoute($this->t('Replace'),  'cloudwords.cloudwords_file_replace_form', ['cloudwords_project' => $cloudwords_project->getId(),'cloudwords_file' => $reference->getId()])->toString(),
        Link::createFromRoute($this->t('Download'),  'cloudwords.cloudwords_file_download', ['cloudwords_project' => $cloudwords_project->getId(),'cloudwords_file' => $reference->getId()])->toString(),
      ];
      $ops_render = [
        '#type' => 'markup',
        '#markup' =>implode(' | ', $ops),
      ];
      $row = [
        $reference->getFileName(),
        \_cloudwords_format_display_date($reference->getCreatedDate()),
        \Drupal::service('renderer')->render($ops_render),
      ];
      $reference_rows[] = $row;
    }

    $form['reference_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Project Reference Materials (Optional)'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => empty($reference_rows),
    ];

    $form['reference_wrapper']['reference_table'] = [
      '#theme' => 'table',
      '#header' => [t('File name'), $this->t('Date'), $this->t('Operations')],
      '#rows' => $reference_rows,
      '#attributes' => ['id' => 'cloudwords-project-table'],
      '#empty' => $this->t('No reference material available.'),
    ];

    $form['reference_wrapper']['upload'] = [
      '#type' => 'container',
      '#title' => $this->t('Project reference material'),
      '#attributes' => ['class' => ['container-inline']],
      '#tree' => FALSE,
    ];

    $form['reference_wrapper']['upload']['reference'] = [
      '#type' => 'file',
      // '#title' => $this->t('Project reference materials'),
      // '#description' => $this->t('Upload additional reference materials.'),

      '#file_info' => NULL,
      '#size' => 10,
    ];

    $form['reference_wrapper']['upload']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    ];

   // $form['#attached']['css'][] = drupal_get_path('module', 'cloudwords') . '/cloudwords.css';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $upload_dir = 'private://cloudwords/reference_material';

    if (!file_prepare_directory($upload_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $form_state->setErrorByName('upload][reference', $this->t('Unable to create the upload directory.'));
    }

    if (!($file = file_save_upload('reference', [
      'file_validate_extensions' => [
        'zip'
      ]
    ], $upload_dir))) {
      // form_set_error('upload][reference', $this->t('Please upload a zip file.'));
    }
    else {
      $form_state->set(['reference_material'], $file);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cloudwords_project = $form_state->get(['cloudwords_project']);
//    $cloudwords_file = $form_state->get(['cloudwords_file']);
    if ($form_state->get('reference_material') !== null) {
      $error_message = 'There was a problem uploading the reference material. Please try again.';
      $reference_material = $form_state->get('reference_material');

      foreach($reference_material as $file){
        cloudwords_get_api_client()->upload_project_reference($cloudwords_project->getId(), \Drupal::service("file_system")->realpath($file->getFileUri()));
      }
    }
  }

  /**
   * Submit callback that redirects to project cancel page.
   */
  public static function cloudwords_cancel_project_redirect(&$form, FormStateInterface $form_state) {
    $cloudwords_project = $form_state->get(['cloudwords_project']);
    $form_state->setRedirect('cloudwords.cloudwords_project_cancel_form', ['cloudwords_project' => $cloudwords_project->getId()]);
  }
}
