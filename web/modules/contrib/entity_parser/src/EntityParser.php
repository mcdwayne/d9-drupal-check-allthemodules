<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 23/03/2018
 * Time: 14:49
 */

namespace Drupal\entity_parser;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Language\LanguageInterface;

class EntityParser extends AbstractEntityParser {

  public function loader_entity_by_type($item, $entity_type, $field = [], $options = []) {
    $entity = NULL;
    if (is_object($item)) {
      $entity = $item;
      //add support views field render id
      if (method_exists($item, '__toString') && $item->__toString() && is_numeric($item->__toString())) {
        $entity = \Drupal::entityTypeManager()
          ->getStorage($entity_type)
          ->load($item->__toString());
      }
    } else {
      if (is_numeric(floatval($item))) {
        $entity = $this->loader_object($item,$entity_type);
      }
    }
    return $this->entity_parser_load($entity, $field, $options);
  }

  public function node_parser($item, $field = [], $options = []) {
    $entity_type = 'node';
    $node_array = $this->loader_entity_by_type($item, $entity_type, $field, $options);

    if (!empty($node_array)) {
      if (isset($node_array['url'])) {
        $node_array['node_url'] = UtilityParser::url_node($item);
      }
      else {
        $node_array['url'] = UtilityParser::url_node($item);
      }
    }
    return $node_array;
  }

  public function paragraph_parser($item, $field = [], $options = []) {
    $entity_type = 'paragraph';
    $paragraph_array = $this->loader_entity_by_type($item, $entity_type, $field, $options);
    return $paragraph_array;
  }

  public function group_parser($item, $field = [], $options = []) {
    $entity_type = 'group';
    $group_array = $this->loader_entity_by_type($item, $entity_type, $field, $options);
    $gid = NULL;
    if (is_object($item)) {
      $gid = $item->id();
    }
    if (is_numeric($item)) {
      $gid = $item;
    }
    if ($gid && !isset($group_array['url'])) {
      $language = \Drupal::languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $group_array['url'] = Url::fromRoute('entity.group.canonical', ['group' => $gid], ['language' => $language])
        ->toString();
    }
    return $group_array;

  }

  public function group_content_parser($item, $field = [], $options = []) {
    $entity_type = 'group_content';
    $gid = NULL;
    $group_array = $this->loader_entity_by_type($item, $entity_type, $field, $options);
    if (is_object($item)) {
      $gid = $item->id();
    }
    if (is_numeric($item)) {
      $gid = $item;
    }
    if ($gid && !isset($group_array['url'])) {
      $language = \Drupal::languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $group_array['url'] = Url::fromRoute('entity.group_content.canonical', ['group_content' => $gid], ['language' => $language])
        ->toString();
    }
    return $group_array;
  }

  public function taxonomy_term_parser($item, $field = [], $options = []) {
    $entity_type = 'taxonomy_term';
    return $this->loader_entity_by_type($item, $entity_type, $field, $options);
  }

  public function user_parser($item, $field = [], $options = []) {
    $entity_type = 'user';
    return $this->loader_entity_by_type($item, $entity_type, $field, $options);
  }

  public function block_parser($item, $field = [], $options = []) {
    $entity_type = 'block_content';
    return $this->loader_entity_by_type($item, $entity_type, $field, $options);
  }
  //**
  // List of Hook Fields By name
  //*//


  public function ers_451e8e6d7b53f8a06e3f8517cf02b856_vid($entity, $field) {
    $vid = $entity->get($field)->getValue()[0];
    if (isset($vid['target_id'])) {
      return $vid['target_id'];
    }
    else {
      if (isset($vid['value'])) {
        return $vid['value'];
      }
      else {
        return $vid;
      }
    }
  }





  /**Function get field input**/
  public function list_string($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $field_value = NULL;
    if ($bool) {
      $field_value = array_column($entity->get($field)->getValue(), "value");
    }
    return $field_value;
  }

  public function decimal($entity, $field) {
    return $this->string($entity, $field);
  }

  public function string($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $items = $entity->get($field)->getValue();
      foreach ($items as $key => $item) {
        if (isset($item['value'])) {
          $result[] = $item['value'];
        }
      }
    }
    return $result;

  }

  public function entity_reference_revisions($entity, $field) {

    $bool = UtilityParser::is_field_ready($entity, $field);

    $item = [];
    if ($bool) {
      $fields = $entity->get($field)->getValue();
      if (!empty($fields)) {
        foreach ($fields as $field) {
          $paragraph = $this->loader_object($field['target_id'],'paragraph');
          if (is_object($paragraph)) {
            $item[$field['target_id']] = $this->entity_parser_load($paragraph);
          }
        }
      }
    }

    return $item;
  }

  public function integer($entity, $field) {
    return $this->string($entity, $field);
  }

  public function uuid($entity, $field) {
    return $this->string($entity, $field);
  }

  public function language($entity, $field) {
    return $this->string($entity, $field);
  }

  public function entity_reference_taxonomy_term($node, $field) {
    $bool = UtilityParser::is_field_ready($node, $field);

    $result = [];
    if ($bool) {
      $terms = $node->get($field)->getValue();
      $entity_type = \Drupal::entityTypeManager();

      foreach ($terms as $key => $value) {
        $term = $this->loader_object($value['target_id'],'taxonomy_term');
        if (is_object($term)) {
          $result[$value['target_id']] = [
            "term" => $term,
            "title" => $term->label(),
            "tid" => $value['target_id'],
          ];
        }
      }
      if (count($result) == 1) {
        return array_shift($result);
      }
    }

    return $result;
  }

  public function link($entity, $field) {
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
      if (count($link) == 1) {
        $link = (array_shift($link));
      }
    }
    return $link;
  }

  public function image_file($entity, $field, $style = NULL) {
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
    }
    return $img_result;
  }

  public function file($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $files = $entity->get($field)->getValue();
      foreach ($files as $key => $item_file) {
        $file = File::load($item_file['target_id']);
        if (is_object($file)) {
          $result = $item_file;
          $result['file_url'] = URl::fromUri(file_create_url($file->getFileUri()))
            ->toString();
          $result['uri'] = $file->getFileUri();
        }
      }
    }
    return $result;
  }

  public function path($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = NULL;
    if ($bool) {
      $result = $entity->get($field)->getValue();
    }
    return $result;
  }

  public function comment($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = NULL;
    if ($bool) {
      $result = $entity->get($field)->getValue();
    }
    return $result;
  }


  public function text_long($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $field_value = NULL;
    if ($bool) {
      $field_value = array_column($entity->get($field)->getValue(), "value");
    }
    return $field_value;
  }

  public function entity_reference_user($entity, $field) {
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
    }
    return $result;
  }

  public function entity_reference($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $nodes = $entity->get($field)->getValue();
      foreach ($nodes as $key => $value) {
        $result[] = $value['target_id'];
      }
    }
    return $result;
  }

  public function entity_reference_node($entity, $field) {
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
        if (is_object($entity)) {
          $result[$value['target_id']] = [
            "node" => $entity,
            "title" => $node->label(),
            "nid" => $value['target_id'],
          ];
        }
      }
    }
    return $result;
  }

  public function float($entity, $field) {
    return $this->string($entity, $field);
  }

  public function coordinates($node, $field) {
    $bool = UtilityParser::is_field_ready($node, $field);
    $result = [];
    if ($bool) {
      $values = $node->get($field)->getValue();
      foreach ($values as $value) {
        $result[] = [
          "lat" => $value['lat'],
          "lng" => $value['lng'],
        ];
      }
      if (count($result) == 1) {
        return array_shift($result);
      }
    }
    return $result;
  }

  public function string_long($entity, $field) {
    return $this->string($entity, $field);
  }

  public function email($entity, $field) {
    return $this->string($entity, $field);
  }

  public function boolean($entity, $field) {
    return $this->string($entity, $field);
  }

  public function changed($entity, $field) {
    return $this->string($entity, $field);
  }

  public function created($entity, $field) {
    return $this->string($entity, $field);
  }

  public function timestamp($entity, $field) {
    return $this->string($entity, $field);
  }

  public function text_with_summary($entity, $field) {
    $bool = UtilityParser::is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $result = $entity->get($field)->getValue();
    }
    return $result;
  }

  public function password($entity, $field) {
    return $entity->get($field)->getValue();
  }

  public function datetime($entity, $field) {
    return $this->string($entity, $field);
  }


}