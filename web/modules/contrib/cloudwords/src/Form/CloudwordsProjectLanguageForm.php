<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\cloudwords\CloudwordsDrupalProject;
use Drupal\cloudwords\CloudwordsLanguage;
use Drupal\Core\Url;
use Drupal\Core\Link;

class CloudwordsProjectLanguageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_language_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, CloudwordsDrupalProject $cloudwords_project = NULL, CloudwordsLanguage $cloudwords_language = NULL) {
    $rows = [];

    $header_row = [
      ['data' => 'Source name', 'field' => 'ct.label'],
      [
        'data' => 'Group',
        'field' => 'ct.textgroup',
      ],
      ['data' => 'type', 'field' => 'ct.type'],
      [
        'data' => 'Translation status',
        'field' => 'ct.translation_status',
      ],
      ['data' => 'Import status', 'field' => 'cc.status'],
      ['data' => 'Last Import', 'field' => 'ct.last_import'],
      ['data' => 'Operations'],
    ];

    $query = \Drupal::database()->select('cloudwords_translatable', 'ct')
      ->fields('ct')
      ->condition('ct.language', cloudwords_map_cloudwords_drupal($cloudwords_language->getLanguageCode()));

    $query->addJoin('INNER', 'cloudwords_content', 'cc', 'ct.id = cc.ctid');
    $query->fields('cc', ['status']);
    $query->condition('cc.pid', $cloudwords_project->getId());

    $results = $query->execute();

    $client = cloudwords_get_api_client();
    // Build language table.
    $files = [];
    try {
      $files = $client->get_translated_bundles($cloudwords_project->getId(), $cloudwords_language->getLanguageCode());
    }
    catch (CloudwordsApiException $e) {}

    foreach ($results as $result) {
      $translatable = cloudwords_translatable_load($result->id);
      $row = [];

      $row['label'] = $translatable->getEditLink();//$translatable->editLink();
      $row['group'] = $translatable->bundleLabel();
      $row['type'] = $translatable->typeLabel();
      $row['translation_status'] = $translatable->getTranslationStatusLabel();

      $operations = [];

      foreach($files as $file){
        if($file['id'] == $translatable->getTranslatedDocumentId()){
          if(isset($file['status']['code']) && isset($file['xliff']['id'])){
            //$xliff_id = $file['xliff']['id'];
            $operations[] = \Drupal::l($this->t('Review in Cloudwords'), Url::fromUri(_cloudwords_ui_url() . '/cust.htm#project/' . $cloudwords_project->getId() . '/language/' . $cloudwords_language->getLanguageCode(), ['attributes' => ['target' => '_BLANK']]));
          }
        }
      }

      switch ($result->status) {
        case CLOUDWORDS_LANGUAGE_NOT_IMPORTED:
          $status = $this->t('Not imported');
          break;

        case CLOUDWORDS_LANGUAGE_IMPORTED:
          $status = $this->t('Imported');
          break;

        case CLOUDWORDS_LANGUAGE_FAILED:
          $status = '<span class="marker">' . $this->t('Import failed') . '</span>';
          break;
      }

      $operations_render = [
        '#type' => 'markup',
        '#markup' =>implode(' | ', $operations),
      ];

      $row['status'] = $status;

      $row['last_import'] = date('F j Y h:i:s',$translatable->getLastImport());
      $row['operations'] = \Drupal::service('renderer')->render($operations_render);
      $rows[$translatable->ctid] = $row;
    }

    $form['table'] = [
      '#theme' => 'table',
      '#header' => $header_row,
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'cloudwords-project-table'
        ],
      '#empty' => $this->t('No content available.'),
    ];

    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
  }

}
