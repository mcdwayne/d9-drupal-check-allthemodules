metatags_quick.admin.inc => lib/MetatagsQuickAdminSettingsForm
variable_get('metatags_quick_settings', _metatags_quick_settings_default()); => config()
field_info_bundles() => entity_get_bundles()
Hint: status => messages--status

https://drupal.org/node/1567792
Undefined index: q in metatags_quick_menu_local_tasks_alter() $_GET['q'] => current_path() 

$form_state['clicked_button'] => $form_state['triggering_element'] 
_metatags_quick_admin_... => class member functions (protected)

global $language => \Drupal::languageManager()->getLanguage();

field_create_field($field); => $this->entityManager = \Drupal::entityManager(); 
$this->entityManager->getStorageController('field_entity')->create($field)->save();

field_create_instance($instance) =>    $new_instance = $this->entityManager->getStorageController('field_instance')->create($instance);
    $new_instance->save();

field_schema, hook_field_schema, hook_entity_info => Plugin/Type
hook_entity_info() has completely new meaning in D8

add 'class' to hook_field_info() (or use annotation-based plugin discovery) - confuses Plugin discovery (overrides annotation-based discovery, class)

https://drupal.org/node/1882526 Annotation based plugin discovery
@todo: hook_update_8000 upgrade path
tests
field_create_field() ?

