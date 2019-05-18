<?php
namespace Drupal\cloudwords;

class CloudwordsTranslationPreviewBundle {
  protected $project;
  protected $translatable;
  protected $translated_bundle;
  /**
   * 
   * returns filepath of archive for api call
   */
  public function __construct($project, $translatable, $translated_bundle) {
    $this->project = $project;
    $this->translatable = $translatable;
    $this->translated_bundle =  $translated_bundle;
  }

  public function import(){
    // language code is what the langcode is locally in system whereas cloudwords_language_code is what is defined in the mapping
    $language_code = $this->translatable->getLanguage();
    $cloudwords_language_code = $this->translatable->cloudwordsLanguage();
    $objectid = $this->translatable->getObjectId();
    $entity_manager = \Drupal::entityManager();
    $entity = $entity_manager->getStorage($this->translatable->getType())->load($objectid);
    // $source_path = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    if($entity->isPublished()){
      $translated_entity = $entity->getTranslation($language_code);

      $cloudwords_base_url = \Drupal::config('cloudwords.settings')->get('cloudwords_drupal_base_url');

      $translated_path = $translated_entity->toUrl('canonical', ['absolute' => TRUE, 'base_url' => $cloudwords_base_url])->toString();

      $scraper = new CloudwordsPreviewScraper($this->project->getName(), $language_code, $objectid, $translated_path);
      if($zip_file_path = $scraper->get_zip_file()){
        // Upload the translation preview zip archive.
        try{
          cloudwords_get_api_client()->upload_translation_preview_bundle($this->project->getId(), $cloudwords_language_code, $this->translated_bundle['id'], $zip_file_path);
        }
        catch (Exception $e) {
          drupal_set_message(t($e->getMessage()), 'error');
          \Drupal::logger('cloudwords')->notice(t($e->__toString()), []);
          return;
        }
      }
    }else{
      drupal_set_message(t('Skipped translated preview as source entity is unpublished'), 'notice');
    }
  }
}
