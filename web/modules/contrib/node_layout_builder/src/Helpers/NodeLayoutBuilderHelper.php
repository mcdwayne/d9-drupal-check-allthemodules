<?php

namespace Drupal\node_layout_builder\Helpers;

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Class NodeLayoutBuilderHelper.
 *
 * Methods for help.
 */
class NodeLayoutBuilderHelper {

  /**
   * Get list fields of entity.
   *
   * @param array $build
   *   Build entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   Display entity.
   * @param string $display_context
   *   Display context of entity.
   *
   * @return array
   *   List fields.
   */
  public static function getFieldsEntity(array $build, EntityViewDisplayInterface $display, $display_context = 'view') {
    $entityManager = \Drupal::service('entity_field.manager');

    $components = $display->getComponents();

    $field_definitions = array_diff_key(
      $entityManager->getFieldDefinitions($display->getTargetEntityTypeId(), $display->getTargetBundle()),
      $entityManager->getExtraFields($display->getTargetEntityTypeId(), $display->getTargetBundle())
    );

    $fields_to_exclude = array_filter($field_definitions, function (FieldDefinitionInterface $field_definition) use ($display_context) {
      return !$field_definition->isDisplayConfigurable($display_context);
    });

    $components = array_diff_key($components, $fields_to_exclude);

    if (count($build) > 0) {
      $components = array_intersect_key($components, $build);
    }

    return $components;
  }

  /**
   * Hide fields for entity.
   *
   * @param array $build
   *   Build entity.
   * @param array $fields
   *   Fields entity.
   */
  public static function hideFields(array &$build, array $fields) {
    if ($fields) {
      foreach ($fields as $name => $field) {
        unset($build[$name]);
      }
    }
  }

  /**
   * Set cache.
   *
   * @param string|int $key
   *   Key data in cache.
   * @param string|array $value
   *   Value to set in cache.
   */
  public static function setCache($key, $value) {
    \Drupal::cache()
      ->set('nlb_element_' . $key, $value);
  }

  /**
   * Get content cache.
   *
   * @param string|int $key
   *   Key data in cache.
   *
   * @return array
   *   Data element.
   */
  public static function getCache($key) {
    $data = [];

    if ($cache = \Drupal::cache()
      ->get('nlb_element_' . $key)) {
      $data = $cache->data;
    }

    return $data;
  }

  /**
   * Delete content cache.
   *
   * @param string|int $key
   *   Key data in cache.
   */
  public static function deletCache($key) {
    \Drupal::cache()
      ->delete('nlb_element_' . $key);
  }

  /**
   * Load node by nid.
   *
   * @param int $nid
   *   ID node.
   *
   * @return object
   *   Node object.
   */
  public static function loadNodeById($nid) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $node_storage->load($nid);
    if ($node) {
      return $node;
    }
    return NULL;
  }

  /**
   * Get list styles bortder.
   *
   * @return array
   *   List style.
   */
  public static function borderStyles() {
    return [
      'none' => t('None'),
      'solid' => t('Solid'),
      'hidden' => t('hidden'),
      'dotted' => t('Dotted'),
      'dashed' => t('Dashed'),
      'double' => t('Double'),
      'groove' => t('Groove'),
      'ridge' => t('Ridge'),
      'inset' => t('Inset'),
      'outset' => t('Outset'),
      'initial' => t('Initial'),
      'inherit' => t('Inherit'),
    ];
  }

  /**
   * Get path to element in array|array multidimensionnel.
   *
   * @param array $arr
   *   Data.
   * @param string|int $lookup
   *   Element to be find in $arr.
   *
   * @return array|null
   *   Path to element in $arr.
   */
  public static function getkeypath(array $arr, $lookup) {
    if (array_key_exists($lookup, $arr)) {
      return [$lookup];
    }
    else {
      foreach ($arr as $key => $subarr) {
        if (is_array($subarr)) {
          $ret = self::getkeypath($subarr, $lookup);
          if ($ret) {
            $ret[] = $key;
            return $ret;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Get element in array data.
   *
   * @param array $array
   *   Data element.
   * @param array $pathresult
   *   Path to element array.
   * @param bool $widthKeys
   *   Want return with keys ?.
   *
   * @return array|mixed
   *   Element wanted.
   */
  public static function getElementFromArrayData(array &$array, array $pathresult, bool $widthKeys = FALSE) {
    $path = [];
    $cur = &$path;
    foreach ($pathresult as $value) {
      $cur[$value] = [];
      $cur = &$cur[$value];
    }
    $cur = NULL;

    $item_id_string = '';
    foreach ($pathresult as $v) {
      $item_id_string .= "$v/";
    }
    $item_id_string = trim($item_id_string, '/');
    $keys = explode('/', $item_id_string);

    $temp = &$array;
    foreach ($keys as $key) {
      $temp = &$temp[$key];
    }

    if ($widthKeys) {
      return [
        'keys' => $keys,
        'data' => $temp,
      ];
    }
    else {
      return $temp;
    }
  }

  /**
   * Update data of element.
   *
   * @param array $data
   *   Data element.
   * @param int|string $parent
   *   Parent element.
   * @param string $id_element
   *   ID element.
   * @param array $new_data
   *   New Data element.
   *
   * @return array|mixed
   *   Data updated.
   */
  public static function updateDataElement(array $data, $parent, $id_element, array $new_data) {
    $pathresult = self::getkeypath($data, $parent);
    krsort($pathresult);

    $path = [];
    $cur = &$path;
    foreach ($pathresult as $value) {
      $cur[$value] = [];
      $cur = &$cur[$value];
    }
    $cur = NULL;

    $product_id_string = '';
    foreach ($pathresult as $v) {
      $product_id_string .= "$v/";
    }
    $product_id_string = trim($product_id_string, '/');
    $keys = explode('/', $product_id_string);

    $temp = &$data;
    foreach ($keys as $key) {
      $temp = &$temp[$key];
    }

    $temp['#children'][$id_element] = $new_data;

    return $data;
  }

  /**
   * Get arguments of form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State form.
   *
   * @return array|mixed
   *   List arguments.
   */
  public static function getFormArgs(FormStateInterface $form_state) {
    $args = [];

    $build_info = $form_state->getBuildInfo();
    if (!empty($build_info['args'])) {
      $args = array_shift($build_info['args']);
    }

    return $args;
  }

  /**
   * Load entity view modes.
   *
   * @param string $entity_type
   *   Type entity.
   *
   * @return array
   *   List of views modes for entity types.
   */
  public static function getEntityViewModes($entity_type = 'node') {
    $view_modes = [];
    $entity_info = \Drupal::entityManager()
      ->getViewModes($entity_type);

    if (!empty($entity_info)) {
      foreach ($entity_info as $k => $v) {
        if ($k == 'full') {
          $k = 'default';
          $v['label'] = 'Default';
        }
        $view_modes[$k] = $v['label'];
      }
    }

    return $view_modes;
  }

  /**
   * Get entity view display.
   *
   * @param string $entity_type
   *   Type entity (node, product...etc).
   * @param string $bundle
   *   Bundle entity (article, page...etc).
   * @param string $view_mode
   *   View mode entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   View display.
   */
  public static function getEntityViewDiplay($entity_type, $bundle, $view_mode = 'default') {
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load($entity_type . '.' . $bundle . '.' . $view_mode);

    if ($display) {
      return $display;
    }

    return NULL;
  }

  /**
   * Load styles image.
   *
   * @return array
   *   List styles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function loadImageStyles() {
    $style_options = ['' => t('None')];

    // $styles = ImageStyle::loadMultiple();
    $styles = \Drupal::entityManager()
      ->getStorage('image_style')
      ->loadMultiple();

    if (!empty($styles)) {
      foreach ($styles as $style) {
        $style_options[$style->getName()] = $style->label();
      }
    }

    return $style_options;
  }

  /**
   * Render element by type.
   *
   * @param string $type
   *   Type element.
   * @param array $values
   *   Values element.
   * @param array $variables
   *   Variables theme of element.
   *
   * @return array|mixed|null|string
   *   Theme (html) of element.
   */
  public static function renderElementByType($type, array $values, array $variables = []) {
    $output = '';

    switch ($type) {
      case 'section':
        break;

      case 'column':
        break;

      case 'text':
        $variables['content_element'] = isset($values['text']['value']) ? check_markup($values['text']['value'], $values['text']['format']) : '';
        break;

      case 'image':
        // From disk
        // example https://gist.github.com/r-daneelolivaw/0edce8fe04de9fd40b1e
        if (isset($values['image_data']['image'][0])) {
          $fid = $values['image_data']['image'][0];
          $file = NodeLayoutFileHelper::loadFileByFid($fid);
          if ($file) {
            $uri = $file->getFileUri();

            // The image.factory service will check if our image is valid.
            $image = \Drupal::service('image.factory')->get($file->getFileUri());
            if ($image->isValid()) {
              $width = $image->getWidth();
              $height = $image->getHeight();
            }
            else {
              $width = $height = NULL;
            }

            if (!empty($values['image_data']['height'])) {
              $height = $values['image_data']['height'];
            }

            // Switch theme image by style image value.
            if (!empty($values['image_data']['style'])) {
              $theme_img = 'image_style';
            }
            else {
              $theme_img = 'image';
            }

            if ($values['image_data']['responsive'] == 1) {
              $width = '100%';
            }

            $image_theme = [
              '#theme' => $theme_img,
              '#style_name' => isset($values['image_data']['style']) ? $values['image_data']['style'] : NULL,
              '#uri' => $uri,
              '#width' => $width,
              '#height' => $height,
              '#title' => $values['image_data']['title'] ?? '',
              '#alt' => $values['image_data']['alt'] ?? '',
            ];

            // Add the file entity to the cache dependencies.
            // This will clear our cache when this entity updates.
            $renderer = \Drupal::service('renderer');
            $renderer->addCacheableDependency($image_theme, $file);

            $variables['content_element'] = render($image_theme);
          }
        }
        break;

      case 'video':
        $url = $values['video_youtube']['url'];
        $options = [
          'width' => $values['video_youtube']['width'],
          'height' => $values['video_youtube']['height'],
          'autoplay' => $values['video_youtube']['autoplay'],
          'responsive' => $values['video_youtube']['responsive'],
        ];
        $video = NodeLayoutBuilderHelper::embedVideoYoutube($url, $options);
        $variables['content_element'] = render($video);
        break;

      case 'audio':
        $url = $values['audio']['url'];
        if ($values['audio']['responsive'] == 1) {
          $style = 'width:100%';
        }
        else {
          $width = $values['audio']['width'];
          $style = 'width:' . $width . 'px';
        }

        $audio_player = [
          '#theme' => 'audio_player',
          '#url' => $url,
          '#style' => $style,
        ];

        $variables['content_element'] = render($audio_player);
        break;

      case 'block':
        if (isset($values['block_id'])) {
          $variables['content_element'] = NodeLayoutBuilderHelper::loadBlockById($values['block_id']);
        }
        break;

      case 'node':
        $nid = !empty($values['node']) ? $values['node'] : NULL;
        $view_mode = !empty($values['view_mode']) ? $values['view_mode'] : 'teaser';
        $node_output = NodeLayoutBuilderHelper::loadNodeView($nid, $view_mode);
        $variables['content_element'] = render($node_output);
        break;

      case 'field':
        $nid = $variables['nid'];
        $node = Node::load($nid);
        $field_name = $values['entity_field'];
        $field_view = $node->$field_name
          ->view($values['view_mode']);
        $variables['content_element'] = render($field_view);
        break;
    }

    return $output;
  }

  /**
   * Load views of entity.
   *
   * @param int $nid
   *   NID entity.
   * @param string $mode_view
   *   Mode view entity.
   *
   * @return mixed
   *   View of entity.
   */
  public static function loadNodeView($nid, $mode_view = 'teaser') {
    $node_output = [
      '#type' => 'markup',
      '#markup' => t('<em>Node does not exist or deleted.</em>'),
      '#prefix' => '<div class="message message-error"',
      '#suffix' => '</div>',
    ];

    $entity = self::loadNodeById($nid);

    if ($entity) {
      $node_view = \Drupal::getContainer()
        ->get('entity.manager')
        ->getViewBuilder('node');

      $node_output = $node_view->view($entity, $mode_view);
    }

    return $node_output;
  }

  /**
   * Remove array element.
   *
   * @param array $path
   *   Path to element in array.
   * @param array $array
   *   Data array.
   *
   * @return array|mixed
   *   Array changed after remove key (element).
   */
  public static function removeArrayKey(array $path, array &$array) {
    $array_temp = &$array;
    $previousItem = NULL;
    $path_bits = $path;

    foreach ($path_bits as &$path_bit) {
      if (!isset($array_temp[$path_bit])) {
        die("Error" . $path_bit);
      }
      $previousItem = &$array_temp;
      $array_temp = &$array_temp[$path_bit];
    }

    if (isset($previousItem)) {
      unset($previousItem[$path_bit]);
    }

    return $array;
  }

  /**
   * Duplicate element.
   *
   * @param array $data
   *   Data array.
   * @param int|string $id_element
   *   ID of element.
   * @param int $nid
   *   ID entity.
   * @param int|string $new_id_element
   *   New ID of element.
   * @param array $element_to_duplicate
   *   Element to duplicated.
   * @param array $clone_element
   *   Clone element.
   */
  public static function duplicateElement(array &$data, $id_element, $nid, $new_id_element, array $element_to_duplicate, array &$clone_element) {
    foreach ($data as $key => &$value) {
      if ($key == $id_element) {
        $data[$new_id_element] = $element_to_duplicate;

        if (isset($data[$new_id_element]['#children'])) {
          if (count($data[$new_id_element]['#children']) > 0) {
            self::updateKeyElement($data[$new_id_element]['#children'], $new_id_element);
          }
        }

        $clone_element = [$new_id_element => $element_to_duplicate];
        break;
      }
      else {
        if (isset($value['#children'])) {
          if (count($value['#children']) > 0) {
            $date = date('YmdHis');
            $new_id_element = $date . uniqid();
            self::duplicateElement($value['#children'], $id_element, $nid, $new_id_element, $element_to_duplicate, $clone_element);
          }
        }
      }
    }

  }

  /**
   * Update key of element.
   *
   * @param array $data
   *   Data array.
   * @param int|string $new_id_parent
   *   New ID of parent.
   */
  public static function updateKeyElement(array &$data, $new_id_parent) {
    foreach ($data as $key => $value) {
      $date = date('YmdHis');
      $new_id_element = $date . uniqid();

      $data[$new_id_element] = $data[$key];
      $data[$new_id_element]['#parent'] = $new_id_parent;

      unset($data[$key]);
      if (isset($data[$new_id_element]['#children'])) {
        if (count($data[$new_id_element]['#children']) > 0) {
          self::updateKeyElement($data[$new_id_element]['#children'], $new_id_element);
        }
      }
      else {
        break;
      }
    }
  }

  /**
   * Update key element child and parent element child.
   *
   * @param array $children
   *   Children of element.
   * @param int|string $parent
   *   Id parent.
   */
  public static function updateKeyElementAndParent(array &$children, $parent) {
    foreach ($children as $key_child => &$child_value) {
      // parent.
      $children[$key_child]['#parent'] = $parent;

      $date = date('YmdHis');
      $new_id_child = $date . uniqid();

      // Key element.
      $children[$new_id_child] = $children[$key_child];
      unset($children[$key_child]);

      if (isset($children[$key_child]['#children'])) {
        if (count($children[$key_child]['#children']) > 0) {
          self::updateKeyElementAndParent($children[$key_child]['#children'], $new_id_child);
        }
      }
      else {
        break;
      }
    }
  }

  /**
   * Get current active theme.
   *
   * @return string
   *   Theme name.
   */
  public static function getActiveTheme() {
    $theme = \Drupal::service('theme.manager')
      ->getActiveTheme()
      ->getName();

    return $theme;
  }

  /**
   * Load all blocks of current theme.
   *
   * @param string $theme
   *   Name machien of theme.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   List blocks.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getAllBlocksByTheme($theme = 'bartik') {
    $blocks = [];
    $blocks_entities = \Drupal::entityManager()
      ->getStorage('block')
      ->loadByProperties(['theme' => $theme]);

    foreach ($blocks_entities as $blocks_entitie) {
      $blocks[$blocks_entitie->getOriginalId()] = $blocks_entitie->label();
    }

    return $blocks;
  }

  /**
   * Load block by id | name machine.
   *
   * @param int $bid
   *   ID or name machine of block.
   *
   * @return array|null
   *   View block.
   */
  public static function loadBlockById($bid) {
    $block = Block::load($bid);

    if ($block) {
      $render = \Drupal::entityManager()
        ->getViewBuilder('block')
        ->view($block);

      return $render;
    }

    return NULL;
  }

  /**
   * Embed video youtube?
   *
   * @param string $url
   *   Url video youtube.
   * @param array $options
   *   Options video youtube.
   *
   * @return array
   *   Youtube video player theme.
   */
  public static function embedVideoYoutube(string $url, array $options) {
    preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $url, $matches);
    $id = $matches[1];
    $options['id'] = $id;
    return [
      '#theme' => 'embed_video_youtube',
      '#options' => $options,
    ];
  }

  /**
   * Move element in data array.
   *
   * @param array $array
   *   Data array.
   * @param int|string $toMove
   *   Position to move at element.
   * @param int|string $targetIndex
   *   Path to index.
   *
   * @return mixed
   *   Data updated.
   */
  public static function moveElementInArray(array $array, $toMove, $targetIndex) {
    $output = $array;
    if (is_int($toMove)) {
      $tmp = array_splice($array, $toMove, 1);
      array_splice($array, $targetIndex, 0, $tmp);
      $output = $array;
    }
    elseif (is_string($toMove)) {
      $indexToMove = array_search($toMove, array_keys($array));
      $itemToMove = $array[$toMove];
      array_splice($array, $indexToMove, 1);
      $i = 0;

      foreach ($array as $key => $item) {
        if ($i == $targetIndex) {
          $output[$toMove] = $itemToMove;
        }
        $output[$key] = $item;
        $i++;
      }
    }
    return $output;
  }

  /**
   * Add element to data.
   *
   * @param array $array
   *   Dara array.
   * @param array $parents
   *   Id parent.
   * @param array $value
   *   Value element to add.
   * @param int $index
   *   Index of element to add.
   *
   * @return array
   *   Data array updated.
   */
  public static function addElementToElementData(array &$array, array $parents, array $value, $index) {
    $ref = &$array;
    foreach ($parents as $parent) {
      if (isset($ref) && !is_array($ref)) {
        $ref = [];
      }
      $ref = &$ref[$parent];
    }

    $key = end($value['keys']);

    array_splice(
      $ref['#children'],
      $index,
      0,
      [
        [$key => $value['data']],
      ]
    );
    self::resetElementKey($array, $key);

    return $array;
  }

  /**
   * Reset element or remove key of element.
   *
   * @param array $data
   *   Data array.
   * @param int|string $key
   *   Key of element.
   */
  public static function resetElementKey(array &$data, $key) {
    if (count($data) > 0) {
      foreach ($data as $key => &$element) {
        if ($key == 0) {
          $key_element = key($element);
          $element = reset($element);
          $data = self::changeKey($data, 0, $key_element);
          break;
        }
        else {
          if (isset($element['#children'])) {
            if (count($element['#children']) > 0) {
              self::resetElementKey($element['#children'], $key);
            }
          }
        }
      }
    }
  }

  /**
   * Change key of element.
   *
   * @param array $array
   *   Data array.
   * @param int|string $old_key
   *   Old key.
   * @param int|string $new_key
   *   New key.
   *
   * @return array
   *   Data array updated.
   */
  public static function changeKey(array &$array, $old_key, $new_key) {
    if (!array_key_exists($old_key, $array)) {
      return $array;
    }

    $keys = array_keys($array);
    $keys[array_search($old_key, $keys)] = $new_key;
    $combine = array_combine($keys, $array);

    return $combine;
  }

  /**
   * Insert element int array.
   *
   * @param array $array
   *   Data array.
   * @param int $position
   *   Position of element to added.
   * @param array $insert
   *   Element to add.
   */
  public static function arrayInsert(array &$array, $position, array $insert) {
    if (is_int($position)) {
      array_splice($array, $position, 0, $insert);
    }
    else {
      $pos = array_search($position, array_keys($array));
      $array = array_merge(
        array_slice($array, 0, $pos),
        $insert,
        array_slice($array, $pos)
      );
    }
  }

  /**
   * Default value of text element.
   *
   * @return string
   *   Text value.
   */
  public static function getLoremText() {
    return '<h1>What is Lorem Ipsum?</h1><p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book</p>';
  }

  /**
   * List type of buttons.
   *
   * @return array
   *   List type buttons.
   */
  public static function listTypeButtons() {
    return [
      'btn-default' => t('Default'),
      'btn-primary' => t('Primary'),
      'btn-success' => t('Success'),
      'btn-info' => t('Info'),
      'btn-warning' => t('Warging'),
      'btn-danger' => t('Danger'),
      'btn-link' => t('Link'),
    ];
  }

}
