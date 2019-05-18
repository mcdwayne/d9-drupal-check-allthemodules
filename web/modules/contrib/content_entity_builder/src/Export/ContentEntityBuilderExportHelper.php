<?php

namespace Drupal\content_entity_builder\Export;


use Drupal\Core\Archiver\ArchiveTar;
use Drupal\content_entity_builder\Entity\ContentType;

/**
 * Class ContentEntityBuilderDownloadController.
 *
 * @package Drupal\content_entity_builder\Controller
 */
class ContentEntityBuilderExportHelper{

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $config;

  /**
   * @param $config
   */
  public function __construct($config) {
    $this->config = $config;

  }
  
  /**
   * generate info yml.
   */
  public function generateArchiveTarFile() {
    $name = isset($this->config['name']) ? $this->config['name'] : '';
	if(empty($name)){
      return;		
	}
    file_unmanaged_delete(file_directory_temp() . '/' . $name .'.tar.gz');

    $archiver = new ArchiveTar(file_directory_temp() . '/' . $name .'.tar.gz', 'gz');
	
	$archiver->addString("$name.info.yml", $this->generateInfoYml());
    $archiver->addString("$name.module", $this->generateModulePhp());
    $archiver->addString("$name.install", $this->generateInstallPhp());
    $archiver->addString("$name.permissions.yml", $this->generatePermissionsYml());
    $archiver->addString("$name.links.action.yml", $this->generateLinksActionYml());
    $archiver->addString("$name.links.task.yml", $this->generateLinksTaskYml());
    $archiver->addString("$name.links.menu.yml", $this->generateLinksMenuYml());
    $archiver->addString("$name.routing.yml", $this->generateRoutingYml());
	
	$content_types = isset($this->config['content_types']) ? $this->config['content_types'] : [];
	
	foreach($content_types as $content_type_id){
      $content_type = \Drupal::entityManager()->getStorage('content_type')->load($content_type_id);
      $entity_name =  $content_type->id();
	  $EntityName = str_replace(' ', '', ucwords(str_replace('_', ' ', $entity_name)));		
	  $archiver->addString("src/Entity/$EntityName.php", $this->generateEntityPhp($content_type, $entity_name, $EntityName));
	  $archiver->addString("src/" . $EntityName . "Interface.php", $this->generateInterfacePhp($content_type, $entity_name, $EntityName));
      $archiver->addString("src/" . $EntityName . "ListBuilder.php", $this->generateListBuilderPhp($content_type, $entity_name, $EntityName));
      $archiver->addString("src/Form/" . $EntityName . "Form.php", $this->generateFormPhp($content_type, $entity_name, $EntityName));
      $archiver->addString("src/Form/" . $EntityName . "DeleteForm.php", $this->generateDeleteFormPhp($content_type, $entity_name, $EntityName));
      $archiver->addString("src/" . $EntityName . "AccessControlHandler.php", $this->generateAccessControlHandlerPhp($content_type, $entity_name, $EntityName));
      $archiver->addString("src/" . $EntityName . "StorageSchema.php", $this->generateStorageSchemaPhp($content_type, $entity_name, $EntityName));	  
	  
	}
	 
	
  }
  
  /**
   * generate info yml.
   */
  public function generateInfoYml() {
  $template = <<<Eof
name: @name
type: module
description: @description
core: 8.x
Eof;

    $ret = format_string($template, array(
      "@name" => isset($this->config['label']) ? $this->config['label'] : '',
      "@description" => isset($this->config['description']) ? $this->config['description'] : '',
    ));
	
    return $ret;
  }

  /**
   * generate module php.
   */
  public function generateModulePhp() {
  $template = <<<Eof
<?php

/**
 * @file
 * @description
 */

Eof;
    $ret = format_string($template, array(
      "@description" => isset($this->config['description']) ? $this->config['description'] : '',
    ));
	
    return $ret;
  }

  /**
   * generate install php.
   */
  public function generateInstallPhp() {
  $template = <<<Eof
<?php

/**
 * @file
 * Install, update and uninstall functions for the @module_name module.
 */
 
/**
 * Implements hook_schema().
 */
function @module_name_install() {
  \$exist = \Drupal::moduleHandler()->moduleExists('content_entity_builder');
  if(empty(\$exist)) {
    return;
  }
  \$content_types = [@content_types];
  foreach(\$content_types as \$content_type_id) {
    //delete the content type config directly if it exist.
    \$content_type = \Drupal::entityTypeManager()->getStorage('content_type')->load(\$content_type_id);
    if(!empty(\$content_type)) {
      \Drupal::entityTypeManager()->getStorage('content_type')->delete([\$content_type_id => \$content_type]);
    }	  
  }
  drupal_flush_all_caches();
}

Eof;
    $content_types_str = "";
    $content_types = isset($this->config['content_types']) ? $this->config['content_types'] : [];
    foreach ($content_types as $content_type) {
        $content_types_str .= "'" . $content_type . "', ";
    }
    $str_len = strlen($content_types_str);
    if($str_len > 0){
      $content_types_str = substr($content_types_str, 0, ($str_len-2)); 
    }
    $ret = strtr($template, array(
      "@module_name" => $this->config['name'],
      "@content_types" => $content_types_str,
    ));
	
    return $ret;
  }
  
  /**
   * generate entity php content.
   */
  public function generateEntityPhp(ContentType $content_type, $entity_name, $EntityName) {
  $template = <<<Eof
<?php

namespace Drupal\@module_name\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\@module_name\@EntityNameInterface;

/**
 * Defines the @entity_name entity.
 *
 * @ingroup @entity_name
 *
 * @ContentEntityType(
 *   id = "@entity_name",
 *   label = @Translation("@entity_label"),
 *   handlers = {
 *     "view_builder" = "Drupal\\Core\\Entity\\EntityViewBuilder",
 *     "list_builder" = "Drupal\\@module_name\\@EntityNameListBuilder",
 *     "views_data" = "Drupal\\views\\EntityViewsData",
 *     "storage_schema" = "Drupal\\@module_name\\@EntityNameStorageSchema", 
 *     "form" = {
 *       "default" = "Drupal\\@module_name\\Form\\@EntityNameForm",
 *       "add" = "Drupal\\@module_name\\Form\\@EntityNameForm",
 *       "edit" = "Drupal\\@module_name\\Form\\@EntityNameForm",
 *       "delete" = "Drupal\\@module_name\\Form\\@EntityNameDeleteForm",
 *     },
 *     "access" = "Drupal\\@module_name\\@EntityNameAccessControlHandler", 
 *   },
 *   base_table = "@entity_name",
 *   admin_permission = "administer @entity_name entity",
 *   entity_keys = {
@entity_keys_code
 *   },
 *   links = {
 *     "canonical" = "@path_view",
 *     "edit-form" = "@path_edit",
 *     "delete-form" = "@path_edit",
 *     "collection" = "/admin/structure/@entity_names" 
 *   },
 *   field_ui_base_route = "entity.@entity_name.collection",
 * )
 */
class @EntityName extends ContentEntityBase implements @EntityNameInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface \$entity_type) {
    \$fields = parent::baseFieldDefinitions(\$entity_type);
@fields_code
    return \$fields;
  }

}
Eof;

    $entity_keys_code = "";
    $fields_code = "";

	//get entity_keys_code
    $keys = array_filter($content_type->getEntityKeys());
    if (empty($keys)) {
      $keys = [
        'id' => 'id',
        'uuid' => 'uuid',
      ];
    }
	$entity_keys_code .=  ' *     "id" = "' . $keys['id'] . '",';
    if(isset($keys['uuid'])){
	  $entity_keys_code .=  '
 *     "uuid" = "' . $keys['uuid'] . '",';		
	}
    if(isset($keys['label'])){
	  $entity_keys_code .=  '
 *     "label" = "' . $keys['label'] . '",';		
	}

    foreach ($content_type->getBaseFields() as $base_field) {
      $fields_code .= $base_field->exportCode();
    }
	
    //$content_type = \Drupal::entityManager()->getStorage('content_type')->load($content_type_id);
    $paths = $content_type->getEntityPaths();
    $path_view = !empty($paths['view']) ? $paths['view'] : "/$content_type_id/{" . $content_type_id . "}";
    //$path_add = !empty($paths['add']) ? $paths['add'] : "/$content_type_id/add";
    $path_edit = !empty($paths['edit']) ? $paths['edit'] : "/$content_type_id/{" . $content_type_id . "}/edit";
    $path_delete = !empty($paths['delete']) ? $paths['delete'] : "/$content_type_id/{" . $content_type_id . "}/delete";
	  
    $ret = strtr($template, array(
      "@module_name" => $this->config['name'],
      "@entity_name" => $entity_name,
      "@entity_label" => $content_type->getLabel(),	  
	  "@EntityName" => $EntityName,
      "@entity_keys_code" => $entity_keys_code,
      "@fields_code" => $fields_code,
      "@path_view" => $path_view,
      //"@path_add" => $path_add,
      "@path_edit" => $path_edit,
      "@path_delete" => $path_delete,		  
    ));
	
    return $ret;
  }  

  /**
   * generate interface php content.
   */
  public function generateInterfacePhp(ContentType $content_type, $entity_name, $EntityName) {
  $template = <<<Eof
<?php

namespace Drupal\@module_name;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining @entity_name entities.
 *
 * @ingroup @entity_name
 */
interface @EntityNameInterface extends ContentEntityInterface{

}

Eof;

    $ret = format_string($template, array(
      "@module_name" => $this->config['name'],
      "@entity_name" => $entity_name,
	  "@EntityName" => $EntityName,
    ));
	
    return $ret;
  }  
  
  /**
   * generate list builder php content.
   */
  public function generateListBuilderPhp(ContentType $content_type, $entity_name, $EntityName) {
  $template = <<<Eof
<?php

namespace Drupal\@module_name;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of @entity_name entities.
 *
 * @see \Drupal\@module_name\Entity\@EntityName
 */
class @EntityNameListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    \$header['label'] = t('Label');
    return \$header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface \$entity) {
    \$label = !empty(\$entity->label()) ? \$entity->label() : \$entity->id();
	\$row['label'] = new Link(\$label, Url::fromRoute("entity.@entity_name.canonical", ["@entity_name" => \$entity->id()]));
    return \$row + parent::buildRow(\$entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface \$entity) {
    \$operations = parent::getDefaultOperations(\$entity);

    return \$operations;
  }

}


Eof;

    $ret = format_string($template, array(
      "@module_name" => $this->config['name'],
      "@entity_name" => $entity_name,
	  "@EntityName" => $EntityName,
    ));
	
    return $ret;
  }

  /**
   * generate form php content.
   */
  public function generateFormPhp(ContentType $content_type, $entity_name, $EntityName) {
  $template = <<<Eof
<?php

namespace Drupal\@module_name\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for @entity_name edit forms.
 *
 * @ingroup @module_name
 */
class @EntityNameForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array \$form, FormStateInterface \$form_state) {
    \$form = parent::buildForm(\$form, \$form_state);
    return \$form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array \$form, FormStateInterface \$form_state) {
    \$element = parent::actions(\$form, \$form_state);
    \$entity = \$this->entity;

    \$account = \Drupal::currentUser();
    \$type_id = \$entity->getEntityTypeId();
    \$element['delete']['#access'] = \$account->hasPermission('delete @entity_name entity');
    \$element['delete']['#weight'] = 100;

    return \$element;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array \$form, FormStateInterface \$form_state) {
    // Build the entity object from the submitted values.
    \$entity = parent::submit(\$form, \$form_state);
    return \$entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array \$form, FormStateInterface \$form_state) {
    \$entity = \$this->entity;
    \$status = \$entity->save();

    switch (\$status) {
      case SAVED_NEW:
        drupal_set_message(\$this->t('Created the %label.', [
          '%label' => \$entity->label(),
        ]));
        break;

      default:
        drupal_set_message(\$this->t('Saved the %label.', [
          '%label' => \$entity->label(),
        ]));
    }
    \$form_state->setRedirect("entity.@entity_name.canonical", ["@entity_name" => \$entity->id()]);
  }

}

Eof;

    $ret = format_string($template, array(
      "@module_name" => $this->config['name'],
      "@entity_name" => $entity_name,
	  "@EntityName" => $EntityName,
    ));
	
    return $ret;
  }

  /**
   * generate delete form php content.
   */
  public function generateDeleteFormPhp(ContentType $content_type, $entity_name, $EntityName) {
  $template = <<<Eof
<?php

namespace Drupal\@module_name\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a @entity_name entity.
 *
 * @ingroup @module_name
 */
class @EntityNameDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \$this->t('Are you sure you want to delete %name?', ['%name' => \$this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the contact list.
   */
  public function getCancelUrl() {
    return new Url('entity.@entity_name.canonical',["@entity_name" => \$this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return \$this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &\$form, FormStateInterface \$form_state) {
    \$entity = \$this->getEntity();
    \$entity->delete();

    \$form_state->setRedirect('entity.@entity_name.collection');
  }

}

Eof;

    $ret = format_string($template, array(
      "@module_name" => $this->config['name'],
      "@entity_name" => $entity_name,
	  "@EntityName" => $EntityName,
    ));
	
    return $ret;
  }
  
  /**
   * generate access control handler php content.
   */
  public function generateAccessControlHandlerPhp(ContentType $content_type, $entity_name, $EntityName) {
  $template = <<<Eof
<?php

namespace Drupal\@module_name;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the @entity_name entity.
 */
class @EntityNameAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess() is called with the
   * \$operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface \$entity, \$operation, AccountInterface \$account) {
    // Check the admin_permission as defined in your @ContentEntityType
    // annotation.
    \$admin_permission = \$this->entityType->getAdminPermission();
    if (\Drupal::currentUser()->hasPermission(\$admin_permission)) {
      return AccessResult::allowed();
    }
    switch (\$operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission(\$account, 'view @entity_name entity');

      case 'update':
        return AccessResult::allowedIfHasPermission(\$account, 'edit @entity_name entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission(\$account, 'delete @entity_name entity');
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist. It
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface \$account, array \$context, \$entity_bundle = NULL) {
    // Check the admin_permission as defined in your @ContentEntityType
    // annotation.
    \$admin_permission = \$this->entityType->getAdminPermission();
    if (\Drupal::currentUser()->hasPermission(\$admin_permission)) {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermission(\$account, 'add @entity_name entity');
  }

}

Eof;

    $ret = format_string($template, array(
      "@module_name" => $this->config['name'],
      "@entity_name" => $entity_name,
	  "@EntityName" => $EntityName,
    ));
	
    return $ret;
  }  

  /**
   * generate access control handler php content.
   */
  public function generateStorageSchemaPhp(ContentType $content_type, $entity_name, $EntityName) {
  $template = <<<Eof
<?php

namespace Drupal\@module_name;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the @entity_name schema handler.
 */
class @EntityNameStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface \$storage_definition, \$table_name, array \$column_mapping) {
    \$schema = parent::getSharedTableFieldSchema(\$storage_definition, \$table_name, \$column_mapping);
    \$field_name = \$storage_definition->getName();
    \$index_fields = [@index_fields];
    if(in_array(\$field_name, \$index_fields)){
      \$this->addSharedTableFieldIndex(\$storage_definition, \$schema, TRUE);
	}
    return \$schema;
  }

}
Eof;
    $index_fields = "";
    foreach ($content_type->getBaseFields() as $base_field) {
      if($base_field->hasIndex()){
        $index_fields .= "'" . $base_field->getFieldName() . "', ";
      }
    }
    if(strlen($index_fields) > 0){
      $index_fields = substr($index_fields,0,strlen($index_fields)-2); 
    }
    $ret = strtr($template, array(
      "@module_name" => $this->config['name'],
      "@entity_name" => $entity_name,
	  "@EntityName" => $EntityName,
      "@index_fields" => $index_fields, 
    ));
	
    return $ret;
  }

  /**
   * generate permissions yml.
   */
  public function generatePermissionsYml() {
  $template = <<<Eof

'delete @entity_name entity':
  title: 'Delete @entity_name entity'
'add @entity_name entity':
  title: 'Add @entity_name entity'
'view @entity_name entity':
  title: 'View @entity_name entity'
'edit @entity_name entity':
  title: 'Edit @entity_name entity'
'administer @entity_name entity':
  title: 'Administer @entity_name entity'

Eof;
    $ret = "";

	$content_types = isset($this->config['content_types']) ? $this->config['content_types'] : [];
    foreach($content_types as $content_type_id){
      $ret .= format_string($template, array(
        "@entity_name" => $content_type_id,
      ));
    }
	
    return $ret;
  }

  /**
   * generate links action yml.
   */
  public function generateLinksActionYml() {
  $template = <<<Eof

@module_name.@entity_name_add:
  route_name: @module_name.@entity_name_add
  title: 'Add @entity_name'
  appears_on:
    - entity.@entity_name.collection

Eof;
    $ret = "";

	$content_types = isset($this->config['content_types']) ? $this->config['content_types'] : [];
    foreach($content_types as $content_type_id){
      $ret .= format_string($template, array(
        "@module_name" => $this->config['name'],
        "@entity_name" => $content_type_id,
      ));
    }
	
    return $ret;
  }

  /**
   * generate links task yml.
   */
  public function generateLinksTaskYml() {
  $template = <<<Eof

entity.@entity_name.canonical:
  route_name: entity.@entity_name.canonical
  base_route: entity.@entity_name.canonical
  title: 'View'
entity.@entity_name.edit_form:
  route_name: entity.@entity_name.edit_form
  base_route: entity.@entity_name.canonical
  title: Edit
entity.@entity_name.delete_form:
  route_name: entity.@entity_name.delete_form
  base_route: entity.@entity_name.canonical
  title: Delete
  weight: 10
entity.@entity_name.collection:
  route_name: entity.@entity_name.collection
  title: 'List'
  base_route: entity.@entity_name.collection

Eof;
    $ret = "";

	$content_types = isset($this->config['content_types']) ? $this->config['content_types'] : [];
    foreach($content_types as $content_type_id){
      $ret .= format_string($template, array(
        "@entity_name" => $content_type_id,
      ));
    }
	
    return $ret;
  }

  /**
   * generate links menu yml.
   */
  public function generateLinksMenuYml() {
  $template = <<<Eof

entity.@entity_name.collection:
  title: '@EntityNames'
  parent: system.admin_structure
  description: 'Create and manage fields, forms, and display settings for your @entity_name.'
  route_name: entity.@entity_name.collection

Eof;
    $ret = "";

	$content_types = isset($this->config['content_types']) ? $this->config['content_types'] : [];
    foreach($content_types as $content_type_id){
	  $EntityName = ucwords(str_replace('_', ' ', $content_type_id));
      $ret .= format_string($template, array(
        "@entity_name" => $content_type_id,
        "@EntityName" => $EntityName,
      ));
    }
	
    return $ret;
  }

  /**
   * generate routing yml.
   */
  public function generateRoutingYml() {
  $template = <<<Eof

entity.@entity_name.canonical:
  path: '@path_view'
  defaults:
    _entity_view: '@entity_name'
    _title: '@entity_name content'
  requirements:
    _entity_access: '@entity_name.view'

entity.@entity_name.edit_form:
  path: '@path_edit'
  defaults:
    _entity_form: @entity_name.default
    _title: 'Edit @entity_name'
  requirements:
    _entity_access: '@entity_name.update'

entity.@entity_name.delete_form:
  path: '@path_delete'
  defaults:
    _entity_form: @entity_name.delete
    _title: 'Delete @entity_name'
  requirements:
    _entity_access: '@entity_name.delete'

entity.@entity_name.collection:
  path: '/admin/structure/@entity_names'
  defaults:
    _entity_list: '@entity_name'
    _title: '@EntityNames'
  requirements:
    _permission: 'administer @entity_name entity'

@module_name.@entity_name_add:
  path: '@path_add'
  defaults:
    _entity_form: @entity_name.default
    _title: 'Add @entity_name'
  requirements:
    _entity_create_access: '@entity_name'

Eof;
    $ret = "";

	$content_types = isset($this->config['content_types']) ? $this->config['content_types'] : [];
    foreach($content_types as $content_type_id){
      $content_type = \Drupal::entityManager()->getStorage('content_type')->load($content_type_id);
      $paths = $content_type->getEntityPaths();
      $path_view = !empty($paths['view']) ? $paths['view'] : "/$content_type_id/{" . $content_type_id . "}";
      $path_add = !empty($paths['add']) ? $paths['add'] : "/$content_type_id/add";
      $path_edit = !empty($paths['edit']) ? $paths['edit'] : "/$content_type_id/{" . $content_type_id . "}/edit";
      $path_delete = !empty($paths['delete']) ? $paths['delete'] : "/$content_type_id/{" . $content_type_id . "}/delete";	
	  $EntityName = ucwords(str_replace('_', ' ', $content_type_id));
      $ret .= format_string($template, array(
        "@entity_name" => $content_type_id,
        "@EntityName" => $EntityName,		
        "@path_view" => $path_view,
        "@path_add" => $path_add,
        "@path_edit" => $path_edit,
        "@path_delete" => $path_delete,		
        "@module_name" => $this->config['name'],
      ));
    }
	
    return $ret;
  }
  
}
