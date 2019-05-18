<?php

namespace Drupal\cloudwords\Form;

use Drupal\cloudwords\CloudwordsProject;
use Drupal\cloudwords\CloudwordsProjectInterface;
use Drupal\cloudwords\CloudwordsLanguage;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\StreamWrapper\PrivateStream;

/**
 * Class CloudwordsProjectCreateForm.
 *
 * @package Drupal\cloudwords\Form
 */
class CloudwordsProjectCreateForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_create_project_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

// @todo check that API key is set property and client can init - don't WSOD
    $client = cloudwords_get_api_client();
    $departments = $client->get_departments();
    $private_files = PrivateStream::basePath();
    $disabled = false;
    if (!$client->getAuthToken() || strlen($client->getAuthToken()) < 11) {
      drupal_set_message($this->t('Cloudwords Authorization Token must be set in the Cloudwords module settings <a href="@url">here.</a>', ['@url' => \Drupal\Core\Url::fromRoute('cloudwords.settings_form')->toString()]), 'error');
      $disabled = true;
    }
    if (!$private_files) {
      drupal_set_message($this->t('The <a href="@url">private file system</a> path must be configured before you can create a project.', ['@url' => \Drupal\Core\Url::fromRoute('system.file_system_settings')->toString()]), 'error');
      $disabled = true;
    }


    unset($_SESSION['cloudwords_project']);
    $default_value = '';
//    if (!empty($form_state['project'])) {
//      $default_value = $form_state['project']->getName();
//    }
//    elseif (!empty($_SESSION['cloudwords_project'])) {
//      $default_value = $_SESSION['cloudwords_project']->getName();
//      $form_state['project'] = $_SESSION['cloudwords_project'];
//    }
//
    $form['project_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 50,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#description' => $this->t('What do you want to call this project?'),
      '#default_value' => $default_value,
      '#disabled' => $disabled,
    ];

    if($departments){
      $options = [];
      foreach($departments as $department){
        $options[$department['id']] = $department['name'];
      }
      $form['department'] = [
        '#type' => 'select',
        '#title' => $this->t('Department'),
        '#required' => TRUE,
        '#description' => $this->t('Select a department'),
        '#options' => $options,
      ];
    }

    $form['reference'] = [
      '#type' => 'file',
      '#title' => $this->t('Project reference materials'),
      '#description' => $this->t('Upload additional reference materials as a zip file.'),
      '#file_info' => NULL,
      '#size' => 10,
      '#disabled' => $disabled,
    ];

    $languages = cloudwords_language_list();
//    $info = cloudwords_translatable_info();
    $rows = [];
    $map = [];
    $added = [];
    $uid = \Drupal::currentUser()->id();
    if ($cache = cloudwords_project_user_get($uid)) {
      $translatables = \Drupal::database()->select('cloudwords_translatable', 'ct')
        ->fields('ct', ['id'])
        ->condition('id', $cache, 'IN')
        ->execute()
        ->fetchCol();

      $translatables = cloudwords_translatable_load_multiple($translatables);

      // @todo use proper getters on translatable
      // Map each object to a list of languages.
      foreach ($translatables as $translatable) {
        $t_type = $translatable->get('type')->value;
        $t_objectid = $translatable->getObjectId();
        $t_language = $translatable->get('language')->value;

        //$type_info = $info[$t_type];
        $map[$t_type][$t_objectid][] = $languages[$t_language];
      }

      foreach ($translatables as $translatable) {
        $t_type = $translatable->get('type')->value;
        $t_objectid = $translatable->getObjectId();
        $t_language = $translatable->get('language')->value;

        if (!isset($added[$t_type][$t_objectid])) {
          //$type_info = $info[$t_type];
          // Gather target languages. Wrap every 4 languages.
          $langs = [];
          $key = 0;
          foreach ($map[$t_type][$t_objectid] as $delta => $lang) {
            if ($delta % 4 === 0) {
              $key++;
            }
            $langs[$key][] =  $lang;
          }
          foreach ($langs as $delta => $lang) {
            $langs[$delta] = implode(', ', $langs[$delta]);
          }
          $targetLanguage = implode('<br />', $langs);

          // remove
          $translatable->getData();

          // end remove

          // Build row.
          $rows[] = [
            $translatable->getEditLink(),
            $targetLanguage,
            $translatable->getTextGroupLabel(),
            $translatable->getTypeLabel(),
            $translatable->getTranslationStatusLabel(),
          ];

          // Mark that we've added this id.
          $added[$t_type][$t_objectid] = TRUE;
        }
      }
    }

    $header = [
      $this->t('Source name'),
      $this->t('Language'),
      $this->t('Group'),
      $this->t('Type'),
      $this->t('Translation exists'),
    ];

    if ($rows) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#disabled' => $disabled,
      ];
    }

    $form['table_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Review content'),
    ];

    $form['table_wrapper']['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'cloudwords-project-table'],
      '#empty' => $this->t('Nothing added to project.'),
    ];
//    $form['pager'] = array(
//      '#theme' => 'pager',
//    );
    if ($rows) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#disabled' => $disabled,
      ];
    }

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if(empty(\Drupal::config('cloudwords.settings')->get('cloudwords_auth_token'))){
      $form_state->setErrorByName('submit', 'No Cloudwords API Authorization Token set.');
    }

    $project_name = trim($form_state->getValue('project_name'));

    if (( strpos($project_name, '/') !== FALSE)
      || (strpos($project_name, '\\') !== FALSE) ) {
      $form_state->setErrorByName('project_name', $this->t('The project name cannot contain slashes.'));
    }

    $query = \Drupal::database()->query("SELECT name FROM {cloudwords_project} WHERE name = :name", array(':name' => $project_name));
    $result = $query->fetchObject();
    if (isset($result->name)) {
      $form_state->setErrorByName('project_name', $this->t('The project name already exists.  Please create a unique project name.'));
    }

    //@todo need to prompt that a private directory is required before getting here
    $upload_dir = 'private://cloudwords/reference_material';
    if (!file_prepare_directory($upload_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $form_state->setErrorByName('reference', $this->t('Unable to create the upload directory.'));
    }

    // If there is a file uploaded, save it.
    if (!empty($_FILES['files']['name']['reference'])) {
      if (!($file = file_save_upload('reference', ['file_validate_extensions' => ['zip']], $upload_dir))) {
        $form_state->setErrorByName('reference', $this->t('Please upload a zip file.'));
      }
      else {
        $form_state->set('reference_material', $file);
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $in_cron =  false;

    $project = FALSE;

    $client = cloudwords_get_api_client();

    try {
      if ($form_state->get('project') == null) {
        $error_message = 'There was an error creating the project. Please check that your <a href="/admin/config/services/cloudwords">settings</a> are correct.';
        $params = [
          'name' => $form_state->getValue('project_name'),
          'notes' => 'The content for this project has been generated by the Cloudwords for Drupal integration. Please read the following to understand how to properly translate this content: https://cloudwords.zendesk.com/entries/23056462',
          'sourceLanguage' => 'en',
          'projectContentType' => 'Drupal',
          'uiFeatures' => [
            'change_source_language' => false,
            'change_target_languages' => false,
            'change_source_material' => true,
            'clone_project' => false,
          ],
        ];
        if($form_state->getValue('department') !== null){
          $params['department'] = ['id'=> (int) $form_state->getValue('department')];
        }

        $project = $client->create_project($params);
      } else {
        $project = $form_state->get('project');
      }
      if ($form_state->get('reference_material') !== null) {
        $error_message = 'There was a problem uploading the reference material. Please try again.';
        $reference_material = $form_state->get('reference_material');

        foreach($reference_material as $file){
          $client->upload_project_reference($project->getId(), \Drupal::service("file_system")->realpath($file->getFileUri()));
        }
      }
    }
    catch (CloudwordsApiException $e) {
      \Drupal::logger('cloudwords')->notice($this->t($e->__toString()), []);
      drupal_set_message($this->t($error_message), 'error');
      $form_state['rebuild'] = TRUE;

      // Stash the project so that we can re-use it.
      $form_state['project'] = $project;
      return;
    }

    $_SESSION['cloudwords_project'] = $project;

    $project_info = [
      'id' => $project->getId(),
      'name' => $project->getName(),
      'status' => $project->getStatus()->getCode(),
      'user_id' => \Drupal::currentUser()->id(),
      'created' =>  REQUEST_TIME,
      'source_language' => $project->getSourceLanguage()->getLanguageCode(),
    ];

    if (\Drupal::database()->query("SELECT id FROM {cloudwords_project} WHERE id = :id", [':id' => $project->getId()])->fetchField()) {
      \Drupal::database()->merge('cloudwords_project')->fields($project_info)->key(['id'])->execute();
    }
    else {
      \Drupal::database()->insert('cloudwords_project')->fields($project_info)->execute();
    }

    \Drupal::database()->delete('cloudwords_content')
      ->condition('pid', $project->getId())
      ->execute();
    \Drupal::database()->delete('cloudwords_project_language')
      ->condition('pid', $project->getId())
      ->execute();

    $batch = [
      'title' => $this->t('Building project ...'),
      'operations' => [],
      'init_message' => $this->t('Loading items to be processed'),
      'progress_message' => $this->t('Saving items as XML.'),
      'error_message' => $this->t('An error occurred during processing.'),
      'finished' => [$this, 'cloudwords_project_finished'],
    ];

    $uid = \Drupal::currentUser()->id();
    if($form_state->get('ctids') !== null){
      $cache = $form_state->get('ctids');
    }else if($ctids = cloudwords_project_user_get($uid)){
      $cache = $ctids;
    }

    if ($cache) {

      $batch['operations'][] = [[$this, 'cloudwords_project_start'], [$project, $in_cron]];


      foreach (array_chunk($cache, CLOUDWORDS_BATCH_SIZE) as $ctids) {
        $batch['operations'][] = [[$this, 'cloudwords_project_create_batch_serialize'], [$ctids, $project]];
      }
      $batch['operations'][] = [[$this, 'cloudwords_project_create_finilize_files'], [$project]];
      $batch['operations'][] = [[$this, 'cloudwords_project_create_update_target_languages'], [$project]];
      $batch['operations'][] = [[$this, 'cloudwords_project_create_upload_zip'], [$project]];
      $batch['operations'][] = [[$this, 'cloudwords_project_create_update_reviewer_instructions'], [$project]];


      //@todo enable preview bundles for OneReview
      if(\Drupal::config('cloudwords.settings')->get('cloudwords_preview_bundle_enabled') != FALSE) {
        foreach (array_chunk($cache, CLOUDWORDS_BATCH_SIZE) as $ctids) {
          foreach($ctids as $ctid){
            $batch['operations'][] = [[$this, 'cloudwords_project_create_source_preview_bundle'], [$project, $ctid]];
          }
        }

        foreach (array_chunk($cache, CLOUDWORDS_BATCH_SIZE) as $ctids) {
          foreach($ctids as $ctid){
            $batch['operations'][] = [[$this, 'cloudwords_project_upload_source_preview_bundle'], [$project, $ctid]];
          }
        }
      }
      $batch['operations'][] = [[$this, 'cloudwords_project_create_project_reference_materials'], [$project]];
    }

    batch_set($batch);
  }

  /**
   * Creates a project via rest call.
   */
  public static function cloudwords_project_start(\Drupal\cloudwords\CloudwordsDrupalProject $project, $in_cron, &$context) {
    $context['results']['in_cron'] = $in_cron;
    // Prepare directory.
    // @todo DO NOT USE PUBLIC DIRECTORY for projects
    $destination = 'private://cloudwords/projects/' . $project->getName();
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      drupal_set_message(t('Files directory either cannot be created or is not writable.'), 'error');
      throw new Exception(t('Files directory either cannot be created or is not writable.'));
    }
  }

  public static function _cloudwords_file_name($project, $translatable) {
    return $project->getName() . '-' .
      $project->getSourceLanguage()->getLanguageCode() . '-' .
      $translatable->cloudwordsLanguage() . '-' .
      $translatable->getTextGroup() . '-' .
      $translatable->getObjectId();
  }

  /**
   * Callback for the cloudwords_project_create action.
   */
  public static function cloudwords_project_create_batch_serialize($ctids, \Drupal\cloudwords\CloudwordsDrupalProject $project, &$context) {
    $translatables = cloudwords_translatable_load_multiple($ctids);

    //@todo turn serializer into service
    module_load_include('inc', 'cloudwords', 'includes/cloudwords.serializer');
    $serializer = new \CloudwordsFileformatXLIFF();
    $destination = 'private://cloudwords/projects/' . $project->getName();

    if (!isset($context['results']['files'])) {
      $context['results']['files'] = [];
    }

    if (!isset($context['results']['translable_bundles'])) {
      $context['results']['translable_bundles'] = [];
    }

    $file_storage =& $context['results']['files'];

    foreach ($translatables as $translatable) {
      $language = $translatable->getLanguage();

      // Find a file to append to.
      // First run.
      if (!isset($file_storage[$language][$translatable->getObjectId()])) {
        $file = $destination . '/' . \Drupal\cloudwords\Form\CloudwordsProjectCreateForm::_cloudwords_file_name($project, $translatable) . '-1.' . CLOUDWORDS_XLIFF_EXTENSION;
        file_put_contents($file, $serializer->beginExport($project, $translatable));
        $file_storage[$language][$translatable->getObjectId()][] = $file;
      }
      else {
        $i = 1;
        $existing_file = FALSE;
        foreach ($file_storage[$language][$translatable->getObjectId()] as $file) {
          // If the file is smaller than 1 megabyte.
          if (filesize($file) < (1048576 * \Drupal::config('cloudwords.settings')->get('cloudwords_upload_file_size'))) {
            $existing_file = $file;
            break;
          }
          $i++;
        }

        if (!$existing_file) {
          // The existing files are too large. We need to create a new one.
          $file = $destination . '/' . \Drupal\cloudwords\Form\CloudwordsProjectCreateForm::_cloudwords_file_name($project, $translatable) . '-' . $i . '.' . CLOUDWORDS_XLIFF_EXTENSION;
          file_put_contents($file, $serializer->beginExport($project, $translatable));
          $file_storage[$language][$translatable->objectid][] = $file;
        }
      }

      // Save our content to project map.
      $map = array(
        'pid' => $project->getId(),
        'ctid' => $translatable->getId(),
      );

      \Drupal::database()->insert('cloudwords_content')->fields($map)->execute();

      // Indicate that this target language is in use.
      $context['results']['target_languages'][$translatable->getLanguage()] = $translatable->cloudwordsLanguage();

      // Append this translatable.
      $output = $serializer->exportTranslatable($translatable);
      file_put_contents($file, $output, FILE_APPEND | LOCK_EX);

      // Mark this translatable as "in project".
      $translatable->status = CLOUDWORDS_QUEUE_IN_PROJECT;
      $translatable->save();

      $context['results']['translable_bundles'][] = ['file_name' => $file, 'translatable' => $translatable];

      // Count the number processed.
      if (empty($context['results']['processed'])) {
        $context['results']['processed'] = 0;
        $context['results']['project'] = $project;
      }
      $context['results']['processed']++;
    }
  }

  public static function cloudwords_project_create_finilize_files(\Drupal\cloudwords\CloudwordsDrupalProject $project, &$context) {
    $serializer = new \Drupal\cloudwords\CloudwordsFileformatXLIFF();

    $destination = 'private://cloudwords/projects/' . $project->getName();
    // Find our xml files.
    $valid_files = [];
    if ($items = @scandir($destination)) {
      foreach ($items as $item) {
        if (is_file("$destination/$item") && strpos($item, '.') !== 0) {
          $valid_files[] = "$destination/$item";
        }
      }
    }

    foreach ($valid_files as $file) {
      file_put_contents($file, $serializer->endExport($project), FILE_APPEND | LOCK_EX);
    }
  }

  /**
   * Batch callback to update a project's target language.
   */
  public static function cloudwords_project_create_update_target_languages(\Drupal\cloudwords\CloudwordsDrupalProject $project, &$context) {
    // Update the target languages.
    // @todo handle if the source language is not english
    $params = [
      'id' => $project->getId(),
      'name' => $project->getName(),
      'targetLanguages' => array_values($context['results']['target_languages']),
      'sourceLanguage' => 'en',
    ];
    try {
      $project = cloudwords_get_api_client()->update_project($params);
      foreach($project->getTargetLanguages() as $language) {
        $project->setLanguageImportStatus($language, CLOUDWORDS_LANGUAGE_NOT_IMPORTED);
      }
    }
    catch (Exception $e) {
      drupal_set_message(t($e->getMessage()), 'error');
      throw $e;
    }
  }

  public static function cloudwords_project_create_upload_zip(\Drupal\cloudwords\CloudwordsDrupalProject $project, &$context) {
    // Create zip archive.
    $archiver = new \ZipArchive();
    $zip_file = \Drupal::service("file_system")->realpath('private://cloudwords/projects/' . $project->getName() . '.zip');
    $destination = 'private://cloudwords/projects/' . $project->getName();

    if ($archiver->open($zip_file, \ZIPARCHIVE::CREATE || \ZIPARCHIVE::OVERWRITE) !== TRUE) {
      return FALSE;
    }

    // Find our xml files.
    $valid_files = [];
    if ($items = @scandir($destination)) {
      foreach ($items as $item) {
        if (is_file("$destination/$item") && strpos($item, '.') !== 0) {
          $valid_files[] = "$destination/$item";
        }
      }
    }

    foreach ($valid_files as $file) {
      $archiver->addFromString(basename($file), file_get_contents($file));
    }
    $archiver->close();

    // Upload the source materials zip archive.
    try{
      cloudwords_get_api_client()->upload_project_source($project->getId(), $zip_file);
    }
    catch (Exception $e) {
      drupal_set_message(t($e->getMessage()), 'error');
      throw $e;
    }

    $context['results']['project'] = $project;
  }

  /**
   * Batch callback to update a project's reviewer instructions.
   */
  public static function cloudwords_project_create_update_reviewer_instructions(\Drupal\cloudwords\CloudwordsDrupalProject $project, &$context) {
    global $base_url;

    $language_list = [];
    foreach(\Drupal::languageManager()->getlanguages() as $k => $v){
      $language_list[$k] = $v->getName();
    }

    foreach($context['results']['target_languages'] as $target_language_drupal => $target_language_cloudwords){
      //text for target langauage
      $language = $language_list[$target_language_drupal];
      $project_url = $base_url.'/admin/structure/cloudwords/project/'.$project->getId();
      $content = 'To review the ' . $language . ' translation, please follow these steps:
1. Go to the project page in Drupal: ' . $project_url . '
2. For ' . $language . ', click Import.
';

      try {
        cloudwords_get_api_client()->create_reviewer_instruction($project->getId(), $target_language_cloudwords, $content);
      }
      catch (Exception $e) {
        drupal_set_message(t($e->getMessage()), 'error');
        throw $e;
      }
    }
  }

  /**
   * Batch callback to create archives for a project's in context preview.
   */
  public static function cloudwords_project_create_source_preview_bundle(\Drupal\cloudwords\CloudwordsDrupalProject $project, $ctid, &$context) {
    if(!isset($context['results']['translable_bundles'])){
      return;
    }

    if (!isset($context['results']['source_bundle_documents'])) {
      $context['results']['source_bundle_documents'] = [];
    }

    if (!isset($context['results']['source_bundle_archives'])) {
      $context['results']['source_bundle_archives'] = [];
    }

    // Get source bundles to map
    $source_bundles = cloudwords_get_api_client()->get_source_bundle($project->getId());

    foreach($context['results']['translable_bundles'] as $translable_bundle){
      //get source
      if($translable_bundle['translatable']->getType() == 'node' && $translable_bundle['translatable']->getId() == $ctid){
        $file_name = basename($translable_bundle['file_name']);
        $file_name = str_replace(' ','_',$file_name);

        $source_bundle = _cloudwords_get_bundle_by_filename($file_name, $source_bundles);

        $entity = \Drupal::entityTypeManager()->getStorage($translable_bundle['translatable']->getType())->load($translable_bundle['translatable']->getObjectId());

        $cloudwords_base_url = \Drupal::config('cloudwords.settings')->get('cloudwords_drupal_base_url');

        $source_path = $entity->toUrl('canonical', ['absolute' => TRUE, 'base_url' => $cloudwords_base_url])->toString();

        $language_code = $entity->language()->getId();

        // ensure source entity is published and accessible by preview generator
        if($entity->isPublished()){
          //only create the archive once
          if(!isset($context['results']['source_bundle_documents'][$entity->id()])){

            $scraper = new \Drupal\cloudwords\CloudwordsPreviewScraper($project->getName(), $language_code, $entity->id(), $source_path);
            
            $zip_file_path = $scraper->get_zip_file();

            //$zip_file_path = cloudwords_preview_generate_archive_from_path($project->getName(), $language_code, $node->nid, $url);
            $context['results']['source_bundle_documents'][$entity->id()] = $zip_file_path;
          }else{
            $zip_file_path = $context['results']['source_bundle_documents'][$entity->id()];
          }

          $context['results']['source_bundle_archives'][$ctid] = array('document_id' => $source_bundle['id'], 'zip_file_path' => $zip_file_path);
          
        }else{
          drupal_set_message(t('Skipped source preview as source entity is unpublished'), 'notice');
        }
      }
    }

    //$context['results']['project'] = $project;
  }

  /**
   * Batch callback to upload archives for a project's in context preview.
   */
  public static function cloudwords_project_upload_source_preview_bundle(\Drupal\cloudwords\CloudwordsDrupalProject $project, $ctid, &$context) {
    if(isset($context['results']['source_bundle_archives'][$ctid])){
      $document_id = $context['results']['source_bundle_archives'][$ctid]['document_id'];
      $zip_file_path = $context['results']['source_bundle_archives'][$ctid]['zip_file_path'];
      try{
        cloudwords_get_api_client()->upload_source_preview_bundle($project->getId(), $document_id, $zip_file_path);
      }
      catch (Exception $e) {
        drupal_set_message(t($e->getMessage()), 'error');
        \Drupal::logger('cloudwords')->notice(t($e->__toString()), []);
        return;
      }
    }
  }

  /**
   * Batch callback to add automatically generated reference materials
   */
  public static function cloudwords_project_create_project_reference_materials(\Drupal\cloudwords\CloudwordsDrupalProject $project, &$context) {
    if (!isset($context['results']['translable_bundles'])) {
      return;
    }
    $lines = array();

    foreach($context['results']['translable_bundles'] as $translable_bundle){
      $objectid = $translable_bundle['translatable']->getObjectId();
      $language = $translable_bundle['translatable']->getLanguage();
      $textgroup = $translable_bundle['translatable']->getTextGroup();
      $i = $objectid.$language;

      $file_name = explode('/', $translable_bundle['file_name']);

      // @todo get the url of the object id rather than putting together string.
      $lines[$i] = [
        $translable_bundle['translatable']->getName(),
        end($file_name),
        $language.'/'.$textgroup.'/'.$objectid
      ];
    }

    $file_content = 'label,filename,url'. PHP_EOL;
    foreach($lines as $line){
      $file_content .= implode(',', $line). PHP_EOL;
    }

    $upload_dir = 'private://cloudwords/reference_material/';
    $manifest_file_path = $upload_dir.'/'.$project->getName().'-manifest';

    $archiver = new \ZipArchive();
    $zip_file = \Drupal::service("file_system")->realpath($manifest_file_path . '.zip');

    if ($archiver->open($zip_file, \ZIPARCHIVE::CREATE || \ZIPARCHIVE::OVERWRITE) !== TRUE) {
      return FALSE;
    }
    //$archiver->addFromString($project->getName().'-manifest.csv', file_get_contents($manifest_file_path.'.csv'));
    $archiver->addFromString($project->getName().'-manifest.csv', $file_content);
    $archiver->close();

    $client = cloudwords_get_api_client();

    $client->upload_project_reference($project->getId(), \Drupal::service("file_system")->realpath($manifest_file_path . '.zip'));
  }

  /**
   * Finished callback.
   */
  public static function cloudwords_project_finished($success, $results, $operations, $time) {
    // @todo to finish
    if ($success == "block") {
      $message = \Drupal::translation()->formatPlural($results['processed'],
        'Uploaded %count item.',
        'Uploaded %count items.',
        ['%count' => $results['processed']]
      );
      drupal_set_message($message);

      unset($_SESSION['cloudwords_project']);

      //$user = \Drupal::currentUser();
      \Drupal::database()->update('cloudwords_translatable')
        ->condition('user_id', \Drupal::currentUser()->id())
        ->fields(array(
          'user_id' => 0,
        ))
        ->execute();

      if ($results['in_cron'] == false) {
        return new RedirectResponse(Url::fromRoute('cloudwords.cloudwords_project_overview_form', ['cloudwords_project' => $results['project']->getId()], ['absolute' => TRUE])->toString());
      }
    }
    else {
      return new RedirectResponse(Url::fromRoute('cloudwords.cloudwords_project_overview_form', ['cloudwords_project' => $results['project']->getId()], ['absolute' => TRUE])->toString());
    }
  }
}
