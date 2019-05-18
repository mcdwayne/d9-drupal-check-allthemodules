<?php

namespace Drupal\entity_parser;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Cache\Cache;

class AbstractEntityParser {

  protected $helper;

  public function __construct() {
    $this->helper = new UtilityParser();
  }
  /**
   * @param numeric and entitype = node , block , ...
   * @view cache integration  https://dev.acquia.com/blog/coding-with-cache-tags-in-drupal-8/13/09/2018/19851
   * @return object
   **/
  public function loader_object($entity,$entity_type='node'){
    $item = null;
    if (is_object($entity)) {
      $item = $entity ;
    }else{
      if(is_numeric($entity)){
        $cid = "parser:".$entity_type.":".$entity ;
        if ($item_cached = \Drupal::cache()->get($cid)) {
          $item = $item_cached->data;
        }else{
          $item = \Drupal::entityTypeManager()
            ->getStorage($entity_type)
            ->load($entity);
          if(is_object($item)){
            \Drupal::cache()->set($cid, $item, Cache::PERMANENT, $item->getCacheTags());
          }
        }
      }

    }
    return $item;
  }
  /**
   * @param $entity = is_object node , taxonomy_term or user
   * @param $fields = list of fields you want to get such as nid , uid ,title
   * @param $options =   array('fields_exclude' => array( 'uid' , 'created' )
   *   ,'hook_alias'=>'json');
   **/

  public function entity_parser_load($entity, $fields = [], $options = []) {
    $item = [];
    if (is_object($entity)) {
      // default get all fields
      if (empty($fields)) {

        $fields = array_keys($entity->getFields(TRUE)); // get fields
      }
      // exculde fields
      if (isset($options["#fields_exclude"])) {
        $fields = array_diff($fields, $options["#fields_exclude"]);
      }
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      if ($entity && method_exists($entity, 'hasTranslation') && $entity->hasTranslation($language)) {
        $entity = $entity->getTranslation($language);
      }

      $item = $this->entity_parser_load_default($entity, $fields, $options);

    }
    return $item;
  }


  protected function entity_parser_load_default($entity, $fields, $options) {
    $item = [];
    $config = \Drupal::config('entity_parser.config');
    $default_hook_alias = $config->get('default_hook_alias');
    $current_hook_alias = NULL;

    if ($default_hook_alias && $default_hook_alias != "") {
      $current_hook_alias = $default_hook_alias;
    }

    foreach ($fields as $key => $field) {

      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {

        $field_type = $entity->get($field)->getFieldDefinition()->getType();
        $setting_field = $entity->get($field)
          ->getFieldDefinition()
          ->getSettings();
        $bool = TRUE;
        //hook by field type
        if (isset($field_type)) {
          $type_fun = "ers_451e8e6d7b53f8a06e3f8517cf02b856_" . $field_type;
          if (method_exists($this, $type_fun)) {
            $field_value = $this->{$type_fun}($entity, $field);
            $bool = FALSE;
          }
          if ($current_hook_alias) {
            $alias = $current_hook_alias;
            $type_fun = $alias . "_" . $field_type;
            if (method_exists($this, $type_fun)) {
              $field_value = $this->{$type_fun}($entity, $field);
              $bool = FALSE;
            }
          }
          if (isset($options['#hook_alias'])) {
            $alias = $options['#hook_alias'];
            $type_fun = $alias . "_" . $field_type;
            if (method_exists($this, $type_fun)) {
              $field_value = $this->{$type_fun}($entity, $field);
              $bool = FALSE;
            }
          }

        }
        // hook by type and target_type
        if (isset($setting_field['target_type'])) {
          $field_target_type = $setting_field['target_type'];
          /// custom field structure
          $type_fun = "ers_451e8e6d7b53f8a06e3f8517cf02b856_" . $field_type . "_" . $field_target_type;
          if (method_exists($this, $type_fun)) {
            $field_value = $this->{$type_fun}($entity, $field);
            $bool = FALSE;
          }
          if ($current_hook_alias) {
            $alias = $current_hook_alias;
            $field_target_type = $setting_field['target_type'];
            $type_fun = $alias . "_" . $field_type . "_" . $field_target_type;
            if (method_exists($this, $type_fun)) {
              $field_value = $this->{$type_fun}($entity, $field);
              $bool = FALSE;
            }
          }

          if (isset($options['#hook_alias'])) {
            $alias = $options['#hook_alias'];
            $field_target_type = $setting_field['target_type'];
            $type_fun = $alias . "_" . $field_type . "_" . $field_target_type;
            if (method_exists($this, $type_fun)) {
              $field_value = $this->{$type_fun}($entity, $field);
              $bool = FALSE;
            }
          }
        }
        // hook by custom field name_machine
        $field_fun = "ers_451e8e6d7b53f8a06e3f8517cf02b856_" . $field;
        if (method_exists($this, $field_fun)) {
          $field_value = $this->{$field_fun}($entity, $field);
          $bool = FALSE;
        }
        if ($current_hook_alias) {
          $alias = $current_hook_alias;
          $type_fun = $alias . "_" . $field;
          if (method_exists($this, $type_fun)) {
            $field_value = $this->{$type_fun}($entity, $field);
            $bool = FALSE;
          }
        }
        if (isset($options['#hook_alias'])) {
          $alias = $options['#hook_alias'];
          $type_fun = $alias . "_" . $field;
          if (method_exists($this, $type_fun)) {
            $field_value = $this->{$type_fun}($entity, $field);
            $bool = FALSE;
          }

        }
        if ($bool) {
          $field_value = $entity->get($field)->getValue();
          $field_value['helper_info'] = "please create a formatter like : function HOOK_ALIAS_" . $field_type . "(entity, field) or HOOK_ALIAS_" . $field . "(entity, field)";
        }
        $item[$field] = ($field_value);
      }
    }
    return $item;
  }


  //**
  // List of Hook Fields Type
  //*//
  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_telephone($entity, $field) {
    return $this->ers_451e8e6d7b53f8a06e3f8517cf02b856_string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_list_string($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $field_value = NULL;
    if ($bool) {
      $field_value = array_column($entity->get($field)->getValue(), "value");
    }
    $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
    if (!$is_multple && count($field_value) == 1) {
      return array_shift($field_value);
    }
    return $field_value;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_decimal($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_string($entity, $field) {

    $bool = UtilityParser::is_field_ready($entity, $field);

    $result = [];
    if ($bool) {
      $items = $entity->get($field)->getValue();

      foreach ($items as $key => $item) {
        $result[] = $item['value'];
      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($result) == 1) {
        return array_shift($result);
      }
    }
    return $result;

  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_entity_reference_revisions($entity, $field) {

    $bool = UtilityParser::is_field_ready($entity, $field);

    $item = [];
    if ($bool) {
      $fields = $entity->get($field)->getValue();
      if (!empty($fields)) {
        foreach ($fields as $key => $field_item) {
          $paragraph = $this->loader_object($field_item['target_id'],'paragraph');
          $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
          if ($paragraph && method_exists($paragraph, 'hasTranslation') && $paragraph->hasTranslation($language)) {
            $paragraph = $paragraph->getTranslation($language);
          }
          if (is_object($paragraph)) {
            $item[$field_item['target_id']] = $this->entity_parser_load($paragraph);
          }
        }
        $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
        if (!$is_multple && count($item) == 1) {
          return array_shift($item);
        }
      }

    }

    return $item;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_integer($entity, $field) {
    return $this->ers_451e8e6d7b53f8a06e3f8517cf02b856_string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_uuid($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_language($entity, $field) {
    return $this->ers_451e8e6d7b53f8a06e3f8517cf02b856_string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_entity_reference_taxonomy_term($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);

    $result = [];
    if ($bool) {
      $terms = $entity->get($field)->getValue();
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      foreach ($terms as $key => $value) {
        $term = $this->loader_object($value['target_id'],'taxonomy_term');
        if ($term && $term->hasTranslation($language)) {
          $term = $term->getTranslation($language);
        }
        if (is_object($term)) {
          $result[$value['target_id']] = [
            "term" => $term,
            "title" => $term->label(),
            "tid" => $value['target_id'],
          ];
        }
      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($result) == 1) {
        return array_shift($result);
      }
    }

    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_link($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $field_value = NULL;
    if ($bool) {
      $field_value = $entity->get($field)->getValue();
      $links = [];
      foreach ($field_value as $key => $link) {
        $link['url_object'] = Url::fromUri($link['uri']);
        $link['url'] = Url::fromUri($link['uri'])->toString();
        $links[] = $link;
      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($links) == 1) {
        return array_shift($links);
      }
    }
    return $link;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_image_file($entity, $field, $style = NULL) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $img_result = [];
    if ($bool) {
      $images = $entity->get($field)->getValue();
      foreach ($images as $key => $image) {
        $file = File::load($image['target_id']);
        if (is_object($file)) {
          $img = $image;
          if ($style) {
            $style_object = ImageStyle::load($style);
            if (is_object($style_object)) {
              $img['image'] = $style_object->buildUrl($file->getFileUri());
            }
          }
          $img['uri'] = $file->getFileUri();
          $img['url'] = file_create_url($file->getFileUri());
          $img_result[] = $img;
        }

      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($img_result) == 1) {
        return array_shift($img_result);
      }
    }
    return $img_result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_file($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $files = $entity->get($field)->getValue();
      foreach ($files as $key => $item_file) {
        $file = File::load($item_file['target_id']);
        if (is_object($file)) {
          $result[$key] = $item_file;
          $result[$key]['file_url'] = URl::fromUri(file_create_url($file->getFileUri()))
            ->toString();
          $result[$key]['uri'] = $file->getFileUri();
        }
      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($result) == 1) {
        return array_shift($result);
      }
    }
    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_path($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = NULL;
    if ($bool) {
      $result = $entity->get($field)->getValue();
    }
    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_comment($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = NULL;
    if ($bool) {
      $result = $entity->get($field)->getValue();
    }
    return $result;
  }


  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_text_long($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $field_value = NULL;
    if ($bool) {
      $field_value = array_column($entity->get($field)->getValue(), "value");
    }
    $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
    if (!$is_multple && count($field_value) == 1) {
      return array_shift($field_value);
    }
    return $field_value;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_entity_reference_user($entity, $field) {
    $result = [];
    $bool = UtilityParser::is_field_ready($entity, $field);
    if ($bool) {
      $users = $entity->get($field)->getValue();
      foreach ($users as $key => $value) {
        $item_user = \Drupal\user\Entity\User::load($value['target_id']);
        if (is_object($item_user)) {
          $result[$value['target_id']] = [
            "user" => $item_user,
            "name" => $item_user->getUsername(),
            "uid" => $value['target_id'],
          ];
        }
      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($result) == 1) {
        return array_shift($result);
      }
    }
    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_entity_reference($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $nodes = $entity->get($field)->getValue();
      foreach ($nodes as $key => $value) {
        $result[] = $value['target_id'];
      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($result) == 1) {
        return array_shift($result);
      }
    }
    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_entity_reference_node($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $nodes = $entity->get($field)->getValue();
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      foreach ($nodes as $key => $value) {
        $node = $this->loader_object($value['target_id']);
        if ($node && $node->hasTranslation($language)) {
          $node = $node->getTranslation($language);
        }
        if (is_object($node)) {
          $result[$value['target_id']] = [
            "node" => $node,
            "title" => $node->label(),
            "nid" => $value['target_id'],
          ];
        }

      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($result) == 1) {
        return array_shift($result);
      }
    }
    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_float($entity, $field) {
    return $this->ers_451e8e6d7b53f8a06e3f8517cf02b856_string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_coordinates($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $values = $entity->get($field)->getValue();
      foreach ($values as $value) {
        $result[] = [
          "lat" => $value['lat'],
          "lng" => $value['lng'],
        ];
      }
      if (count($result) == 1) {
        return array_shift($result);
      }
      $is_multple = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition()->isMultiple();
      if (!$is_multple && count($result) == 1) {
        return array_shift($result);
      }
    }
    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_string_long($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_email($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_boolean($entity, $field) {
    return $this->ers_451e8e6d7b53f8a06e3f8517cf02b856_string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_changed($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_created($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_timestamp($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_text_with_summary($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $result = $entity->get($field)->getValue();
    }
    return $result;
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_password($entity, $field) {
    return $entity->get($field)->getValue();
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_datetime($entity, $field) {
    return $this->string($entity, $field);
  }

  protected function ers_451e8e6d7b53f8a06e3f8517cf02b856_text($entity, $field) {
    return $this->string($entity, $field);
  }


}