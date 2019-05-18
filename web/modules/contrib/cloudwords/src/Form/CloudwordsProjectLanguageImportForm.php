<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Archiver\Zip;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CloudwordsProjectLanguageImportForm extends ConfirmFormBase  {

  /**
   * The ID of the project
   *
   * @var int
   */
  protected $project_id;

  /**
   * Language of translations in project.
   *
   * @var string
   */
  protected $language;

  /**
   * Language status of translations in project.
   *
   * @var string
   */
  protected $language_status;


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_language_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to import content for %lang?', ['%lang' => $this->language]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    //@todo change to proper cancel url
    return Url::fromUri('internal:/admin/cloudwords/projects/' . $this->project_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription () {
    if ($this->language_status == 2) {
      return $this->t('You have already imported content from this project and language. If you import again it will overwrite any existing content for this language.');
    }
  }
  //@ todo type requirement on params- qualified names  \ClouwordsLanguage nee
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\cloudwords\CloudwordsDrupalProject $cloudwords_project = NULL, \Drupal\cloudwords\CloudwordsLanguage $cloudwords_language = NULL) {
    $this->language = $cloudwords_language->getDisplay();
    $this->project_id = $cloudwords_project->getId();

// @todo add publishing state options for entities that status is available on editing per language.
//  $node_status_opts = _cloudwords_project_language_import_publish_opts($project, $language);
//
//    if ($node_status_opts) {
//      $form['select_all'] = [
//        '#type' => 'checkbox',
//        '#title' => $this->t('Select / Deselect all for publishing'),
//      ];
//      $form['node_status'] = $node_status_opts;
//    }
    $form_state->set(['cloudwords_project'], $cloudwords_project);
    $form_state->set(['cloudwords_language'], $cloudwords_language);

    $this->language_status = 1;
    $this->language_status = db_query("SELECT status FROM {cloudwords_project_language} WHERE pid = :pid AND language = :lang", [
      ':pid' => $cloudwords_project->getId(),
      ':lang' => $cloudwords_language->getLanguageCode(),
    ])->fetchField();

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $in_cron = (!$form_state->get(['in_cron'])) ? $form_state->get(['in_cron']) : FALSE;
    $project = $form_state->get(['cloudwords_project']);
    $language = $form_state->get(['cloudwords_language']);

    // @todo publishing status for publishable items in set
    $node_statuses = [];
    //$node_statuses = !$form_state->getValue(['node_status']) ? $form_state->getValue(['node_status']) : [];
    //$node_statuses = array_filter($node_statuses);
    // Get translated bundles to map
    $translated_bundles = cloudwords_get_api_client()->get_translated_bundles($project->getId(), $language->getLanguageCode());

    // Download file and save as a temporary file.
    $file = cloudwords_get_api_client()->download_translated_file($project->getId(), $language->getLanguageCode());

    $temp_dir = \Drupal::service('file_system')->realpath(cloudwords_temp_directory());
    $temp = \Drupal::service('file_system')->tempnam($temp_dir, 'cloudwords-');

    file_put_contents($temp, $file);

    // Determine if tmp file is an xliff or a zip file
    if (is_resource(zip_open($temp))) {
      // Read zip file contents.
      $zip = new Zip($temp);
      $file_names_in_zip = $zip->listContents();

      $file_names = [];
      foreach ($file_names_in_zip as $file_name_in_zip) {
        // Three validation options:
      // Only include files, not directories or dotfiles
      // always allow 'xliff', 'xlf', 'xml' - check this only to skip running an extra valid xml test
      // validate files without extensions as some xliff clients will return valid xliff without an ext
        $file_name_parts = explode('/', $file_name_in_zip);
        $file_name = end($file_name_parts);

        $file_name_ext_text_arr = explode(".", strtolower($file_name));
        $file_ext = end($file_name_ext_text_arr);

        if (substr($file_name_in_zip, -1) == '/' || preg_match('/^([.])/', $file_name)) {
          continue;
        }
        elseif (in_array($file_ext, ['xliff', 'xlf', 'xml'])) {
          $file_names[] = $file_name_in_zip;
        }
        elseif (strpos($file_name, '.') == FALSE) {
          libxml_use_internal_errors(TRUE);
          if (simplexml_load_string(_cloudwords_filter_xml_control_characters(file_get_contents('zip://' . $temp . '#' . $file_name)))) {
            $file_names[] = $file_name_in_zip;
          }
          libxml_clear_errors();
          libxml_use_internal_errors(FALSE);
        }
      }

//      $archive = $zip->getArchive();
      $isArchive = TRUE;
    }
    else {
      if (simplexml_load_string(_cloudwords_filter_xml_control_characters(file_get_contents($temp)))) {
        $file_names = [$temp];
        $isArchive = FALSE;
      }
    }

    if ($file_names) {
      $batch = [
        'title' => $this->t('Importing files ...'),
        'operations' => [],
        'init_message' => $this->t('Loading files'),
        'progress_message' => $this->t('Processed @current out of @total files.'),
        'error_message' => $this->t('An error occurred during processing.'),
        'finished' => [$this, 'cloudwords_import_batch_finished'],
 //       'progressive' => FALSE,
      ];

      foreach ($file_names as $file_name) {
        //we need to fetch the translated document based on the filename
        $translated_bundle = _cloudwords_get_bundle_by_filename($file_name, $translated_bundles);

        $batch['operations'][] = [[$this, 'cloudwords_import_batch'],[$isArchive,$temp,$file_name,$project,$language,$node_statuses,$translated_bundle,$in_cron]];

        $batch['operations'][] = [[$this, 'cloudwords_import_translation_preview_bundle'],[$project,$translated_bundle]];
      }
      batch_set($batch);
    }
  }

  public static function cloudwords_import_translation_preview_bundle($project,$translated_bundle,&$context){
    if(\Drupal::config('cloudwords.settings')->get('cloudwords_preview_bundle_enabled') != FALSE) {

      foreach ($context['results']['processed_translatables'] as $translatable) {
        if($translatable->getType() == 'node') {
          $translation_preview_bundle = new \Drupal\cloudwords\CloudwordsTranslationPreviewBundle($project, $translatable, $translated_bundle);
          $translation_preview_bundle->import();
        }
      }
    }
  }

  /**
   * Extracts one xml file from a zip file and imports it.
   */
  public static function cloudwords_import_batch($isArchive, $temp_file, $file_name, $project, $language, $node_statuses, $translated_bundle, $in_cron, &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['temp_file'] = $temp_file;
      $context['results']['project'] = $project;
      $context['results']['language'] = $language;
      $context['results']['processed_translatables'] = [];
      $context['results']['successful'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['in_cron'] = $in_cron;
    }
    module_load_include('inc', 'cloudwords', 'includes/cloudwords.serializer');
    $serializer = new \CloudwordsFileformatXLIFF();
    if($isArchive == true){
      $file = 'zip://' . $temp_file . '#' . $file_name;
    }else{
      $file = $file_name;
    }

    if ($serializer->validateImport($project, $language, $file)) {
      $valid_ctids = $project->getCtids(cloudwords_map_cloudwords_drupal($language->getLanguageCode()));
      $imported = $serializer->import($file);
      $to_be_imported = array_intersect($valid_ctids, array_keys($imported));
      $translatables = cloudwords_translatable_load_multiple($to_be_imported);

      foreach ($imported as $ctid => $data) {
        if (!in_array($ctid, $to_be_imported)) {
          $context['results']['success'] = FALSE;
          $context['results']['failed']++;
          drupal_set_message(t('%file contains an invalid id: %id', ['%file' => $file, '%id' => $ctid]), 'error');
        }
        else {
          $translatable = $translatables[$ctid];
          try {
            //$translatable->setSetting('node_status', (int) isset($node_statuses[$ctid]));
            $translatable->saveData($data);
            $translatable->translation_status = CLOUDWORDS_TRANSLATION_STATUS_TRANSLATION_EXISTS;
            $translatable->translated_document_id = $translated_bundle['id'];
            $translatable->last_import = time();
            $translatable->save();
            $translatable->setProjectTranslationStatus($project, CLOUDWORDS_LANGUAGE_IMPORTED);
            $context['results']['successful']++;
            $context['results']['processed_translatables'][] = $translatable;
            $context['message'] = t('Importing %label (%type).', ['%label' => $translatable->label, '%type' => $translatable->typeLabel()]);
          }
          catch (Exception $e) {
            drupal_set_message(t($e->getMessage()), 'error');
            $context['results']['failed']++;
            $translatable->setProjectTranslationStatus($project, CLOUDWORDS_LANGUAGE_FAILED);
          }
          $context['results']['processed']++;
        }
      }
    }
    else {
      drupal_set_message(t('%file failed to validate.', ['%file' => $file]), 'error');
      $context['results']['success'] = FALSE;
      $project->setLanguageImportStatus($language, CLOUDWORDS_LANGUAGE_FAILED);
    }
  }

  /**
   * Finished callback for batch importing.
   *
   * @todo Cleanup after error.
   */
  public static function cloudwords_import_batch_finished($success, $results, $operations, $time) {
    $results += ['success' => TRUE];
    if ($success) {
      if ($results['successful']) {
        $message = \Drupal::translation()->formatPlural($results['successful'],
          'Saved %count item.',
          'Saved %count items.',
          ['%count' => $results['successful']]
        );
        drupal_set_message($message);
      }

      if ($results['failed']) {
        $message = \Drupal::translation()->formatPlural($results['failed'],
          'Failed saving %count item.',
          'Failed saving %count items.',
          ['%count' => $results['failed']]
        );
        drupal_set_message($message, 'error');
      }
    }

    // This means something blew up. bail.
    else {
      return;
    }

    if ($results['success']) {
      $results['project']->setLanguageImportStatus($results['language'], CLOUDWORDS_LANGUAGE_IMPORTED);
    }
    else {
      $results['project']->setLanguageImportStatus($results['language'], CLOUDWORDS_LANGUAGE_FAILED);
    }

    // Delete the temporary zip file.
    if (!empty($results['temp_file'])) {
      file_unmanaged_delete($results['temp_file']);
    }

    if ($results['in_cron'] == false) {
      return new RedirectResponse(\Drupal::url('cloudwords.cloudwords_project_overview_form', ['cloudwords_project' => $results['project']->getId()], ['absolute' => TRUE]));
    }
  }

}
