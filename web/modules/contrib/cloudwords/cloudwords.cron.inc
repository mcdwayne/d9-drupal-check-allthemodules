<?php

/**
 * @file
 * Cron for cloudwords.module.
 */

function _cloudwords_cron(){
  _cloudwords_cron_release_closed_project_content();
  //@todo fix automatic import and auto project creation
  _cloudwords_cron_auto_import_translation_preview_bundle();
//  _cloudwords_cron_auto_project_creation_of_out_of_date_translatables();
}

function _cloudwords_cron_auto_import_translation_preview_bundle(){
  // @todo clear cache for cloudwords translatables

  $enabled = \Drupal::config('cloudwords.settings')->get('cloudwords_auto_import_translation_enabled');
  if($enabled != true){
    return false;
  }
  $cloudwords_auto_import_translation_max_process_items = \Drupal::config('cloudwords.settings')->get('cloudwords_auto_import_translation_max_process_items');
  $cloudwords_auto_import_translation_frequency = \Drupal::config('cloudwords.settings')->get('cloudwords_auto_import_translation_frequency');
  $cloudwords_auto_import_translation_frequency = (is_int($cloudwords_auto_import_translation_frequency)) ? $cloudwords_auto_import_translation_frequency : 0;
  $frequency_setting = $cloudwords_auto_import_translation_frequency  * 60;


  $cloudwords_auto_import_translation_last_cron_run = \Drupal::state()->get('cloudwords_auto_import_translation_last_cron_run');
  $cloudwords_auto_import_translation_last_cron_run = (is_int($cloudwords_auto_import_translation_last_cron_run)) ? $cloudwords_auto_import_translation_last_cron_run : REQUEST_TIME;
  $runnable_after = $cloudwords_auto_import_translation_last_cron_run + $frequency_setting;
  if($runnable_after && ($runnable_after > REQUEST_TIME)){
    return false;
  }

  module_load_include('inc', 'cloudwords', 'cloudwords.pages');

  $projects = cloudwords_get_api_client()->get_open_projects();

 //$existing_ids = \Drupal::database()->query("SELECT pid FROM {cloudwords_project} WHERE status NOT IN (:cancelled)", [':cancelled' => cloudwords_project_closed_statuses()])->fetchCol();
  $query = \Drupal::entityTypeManager()->getStorage('cloudwords_project')->getQuery();
  $query->condition('status', cloudwords_project_closed_statuses(), 'NOT IN');
  $existing_ids = $query->execute();

//  $importables = [];
  $imported = 0;
  foreach ($projects as $project) {
    if (in_array($project->getId(), $existing_ids)) {
      $params = $project->getParams();
      foreach ($params['targetLanguages'] as $delta => $lang) {
        $language = new \Drupal\cloudwords\CloudwordsLanguage($lang);
        $translated_bundles = [];
        try {
          $translated_bundles = cloudwords_get_api_client()->get_translated_bundles($project->getId(), $language->getLanguageCode());
        } catch (CloudwordsApiException $e) {}

        if($translated_bundles){
          $valid_ctids = $project->getCtids(cloudwords_map_cloudwords_drupal($language->getLanguageCode()));
          $translatables = cloudwords_translatable_load_multiple($valid_ctids);

          foreach($translatables as $translatable){
            foreach($translated_bundles as $translated_bundle){
              // \Drupal::logger('cloudwords')->notice('translatable %tid , project %pid , bundle_id %bid', ['%tid' => $translatable->getId(), '%pid' => $project->getId(), '%bid' => $translated_bundle['id']]);
              if(is_null($translatable->getLastImport()) ||
                (!is_null($translatable->getLastImport()) && $translatable->getLastImport() < strtotime($translated_bundle['xliff']['createdDate']))){
                // TODO implement queue if max_process items is a large number
                if($imported <= $cloudwords_auto_import_translation_max_process_items) {
                  _cloudwords_cron_auto_import_translation_preview_bundle_cron_import_xliff($project, $language, $translatable, $translated_bundle);
                  $imported++;
                }
              }
            }
          }
        }
      }
    }
  }
  \Drupal::state()->set('cloudwords_auto_import_translation_last_cron_run', REQUEST_TIME);
}

/*
 *  Imports single document to a translatable at a time.
 */
function _cloudwords_cron_auto_import_translation_preview_bundle_cron_import_xliff($project, $language, $translatable, $translated_bundle){
  $file = cloudwords_get_api_client()->get_translated_document_by_id($project->getId(), $language->getLanguageCode(), $translated_bundle['id']);

  $temp_dir = \Drupal::service('file_system')->realpath(cloudwords_temp_directory());
  $temp = \Drupal::service('file_system')->tempnam($temp_dir, 'cloudwords-');
  file_put_contents($temp, $file);

  module_load_include('inc', 'cloudwords', 'includes/cloudwords.serializer');
  $serializer = new \CloudwordsFileformatXLIFF();

  if (simplexml_load_string(_cloudwords_filter_xml_control_characters(file_get_contents($temp))) &&
  $serializer->validateImport($project, $language, $temp)) {
    $imported = $serializer->import($temp);

    foreach ($imported as $ctid => $data) {
      try {
        //$translatable->setSetting('node_status', (int) isset($node_statuses[$ctid]));
        $translatable->saveData($data);
        $translatable->translation_status = CLOUDWORDS_TRANSLATION_STATUS_TRANSLATION_EXISTS;
        $translatable->translated_document_id = $translated_bundle['id'];
        $translatable->last_import = time();
        $translatable->save();
        $translatable->setProjectTranslationStatus($project, CLOUDWORDS_LANGUAGE_IMPORTED);
        if(\Drupal::config('cloudwords.settings')->get('cloudwords_preview_bundle_enabled') != FALSE && $translatable->getType() == 'node') {
          $translation_preview_bundle = new \Drupal\cloudwords\CloudwordsTranslationPreviewBundle($project, $translatable, $translated_bundle);
          $translation_preview_bundle->import();
        }
      }
      catch (Exception $e) {
        drupal_set_message(t($e->getMessage()), 'error');
        $translatable->setProjectTranslationStatus($project, CLOUDWORDS_LANGUAGE_FAILED);
      }
    }
  }
}


function _cloudwords_cron_auto_project_creation_of_out_of_date_translatables(){
  module_load_include('inc', 'cloudwords', 'cloudwords.create_project');
  //auto create projects of grouped content for out of date translatables if enabled

  $auto_project_creation_enabled = \Drupal::config('cloudwords.settings')->get('cloudwords_auto_project_creation_enabled');
  if($auto_project_creation_enabled != true){
    return false;
  }

  // Get list of translatables that are flagged as out of date and are NOT currently in a project
  $properties = ['translation_status' => 2, 'status' => 0];
  $translatables = cloudwords_get_translatables_by_property($properties);

  //this must be run as a particular user to simulate the UI addition of ctids to a project
  $user = \Drupal::currentUser();
  $original_user = $user;
  $old_state = drupal_save_session();
  drupal_save_session(FALSE);
  $user = \Drupal::entityTypeManager()->getStorage('user')->load(1);

  // group by language
  $langCtids = [];
  foreach($translatables as $translatable){
    $langCtids[$translatable->language][] = $translatable->ctid;
  }

  foreach($langCtids as $langCode => $ctids){
    $form_state = [];
    $form_state['values']['project_name'] = t('auto_!langCode_!date',
      ['!langCode' => $langCode,
            '!date' => date('Y_m_d_H_i_s',time()
            )
           ]
      );

    $form_state['in_cron'] = true;
    $form_state['ctids'] = $ctids;
    $form_state['values']['op'] = 'Submit';
    $form_state['values']['submit'] = 'Submit';

    \Drupal::formBuilder()->submitForm('cloudwords_project_create_form', $form_state);
  }

  $user = $original_user;
  drupal_save_session($old_state);
}

function _cloudwords_cron_release_closed_project_content(){
  $last = REQUEST_TIME - \Drupal::config('cloudwords.settings')->get('cloudwords_last_cron_run');

  $last = $last / 3600;

  if ($last && $last < 24) {
    return;
  }

  $projects = cloudwords_get_api_client()->get_open_projects();

  $query = \Drupal::entityTypeManager()->getStorage('cloudwords_project')->getQuery();
  $query->condition('status', cloudwords_project_closed_statuses(), 'NOT IN');
  $existing_ids = $query->execute();

  foreach ($projects as $project) {
    if (in_array($project->getId(), $existing_ids)) {
      $project_info = [
        'name' => $project->getName(),
        'status' => $project->getStatus()->getCode(),
      ];
      \Drupal::database()->merge('cloudwords_project')->fields($project_info)->key(['id' => $project->getId()])->execute();
      foreach ($project->getTargetLanguages() as $language) {
        try {
          $files = cloudwords_get_api_client()->get_project_translated_files($project->getId());
          // Approve languages that were not previously approved.
          foreach ($files as $file) {
            if ($file->getStatus()->getCode() == 'approved' &&
                $project->getLanguageImportStatus($language) != CLOUDWORDS_LANGUAGE_APPROVED) {
              $project->approve($language);
            }
          }
        }
        catch (CloudwordsApiException $e) {}
      }
    }
  }

  // release the content for closed projects
  $closed_projects = [];
  try {
    $closed_projects = cloudwords_get_api_client()->get_closed_projects();
  }
  catch (CloudwordsApiException $e) {}

  if ($closed_projects && $existing_ids) {
    foreach ($closed_projects as $project) {
      if (in_array($project->getId(), $existing_ids)) {
        $project->releaseContent();
        $project->setStatus($project->getStatus()->getCode());
      }
    }
  }
  \Drupal::configFactory()->getEditable('cloudwords.settings')->set('cloudwords_last_cron_run', REQUEST_TIME)->save();
}