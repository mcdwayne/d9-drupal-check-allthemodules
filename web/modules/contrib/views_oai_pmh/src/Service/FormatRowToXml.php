<?php

namespace Drupal\views_oai_pmh\Service;

class FormatRowToXml {

  /**
   * The tags(keys) holder.
   * format: array [alias => nValues]
   */
  protected $tagsPrefixedWith0 = [];


  /**
   * Reset tagsPrefixedWith0 value
   */
  public function resetTagsPrefixedWith0(){
    $this->tagsPrefixedWith0 = [];
  }

  /**
   * Compose end values
   *
   * @param $value
   *
   * @return array
   */
  protected function buildValues($value) {
    $output = [];
    if (is_array($value)) {
      $i = 0; $eval_output = false;
      foreach ($value as $items) {
        if (is_array($items)) {
          if (count($items) === 1) {
            $end = $items[0];
          } else {
            $end = $items;
            $j = $i;
            foreach ($items AS $item) {
              $output[" $j"] = [
                '#' => $item,
              ];
              $j++;
            }
            $eval_output = true;
          }
        } else {
          $end = $items;
        }
        if(!$eval_output){
          $output[" $i"] = [
            '#' => $end,
          ];
        }
        $i++;
      }
    }
    else {
      $output['#'] = $value;
    }

    return $output;
  }

  /**
   * Compose attribute to set
   *
   * @param $attribute_name
   * tag name. e.g "descriptionType"
   *
   * @param $attribute_value
   * tag value. e.g "Abstract"
   *
   * @param $value
   * tag composed value
   *
   * @return array
   * Current attribute to set
   *
   */
  protected function buildAttributes($attribute_name, $attribute_value, $value) {
    $output = [];
    if (is_array($attribute_value) && !array_key_exists('#', $value)) {
      foreach ($attribute_value as $id_row => $val_row) {
        if(is_numeric($id_row)){
          $output[' ' . $id_row]['@' . $attribute_name] = $val_row;
        } else {
          $output[$id_row]['@' . $attribute_name] = $val_row;
        }
      }
    }
    else {
      $output['@' . $attribute_name] = $attribute_value;
    }

    return $output;
  }

  /**
   * Transform views rows data to array composer for xml for this view row
   *
   * @param array $row
   * view row data
   *
   * @return array
   * array ready to convert to xml
   */
  public function transform(array $row): array {
    $values = [];
    $output = [];

    foreach ($row as $alias => $value) {
      if ($attribute = $this->hasAttribute($alias)) {
        $tag_name = $attribute[0];
        $tag_attr_name = $attribute[1];
        $current_value = $this->buildAttributes($tag_attr_name, $value, $values[$tag_name]);
        $alias = $tag_name;
      }
      else {
        $tag_name = $alias;
        $current_value = $this->buildValues($value);
      }
      $values[$tag_name] = $current_value;
      $output = array_merge_recursive(
        $output,
        $this->depth($alias, $current_value)
      );
    }
    $output = array_map(array($this, "trimmingKeys"), $output);

    return $output;
  }

  /**
   * Strip whitespace from the beginning and end of a array keys
   *
   * @param $array
   * array to trim keys
   *
   * @return array
   */
  public function trimmingKeys(&$array) {
    if (gettype($array) === 'array') {
      foreach ($array As $key => $value){
        if (strpos($key, ' ') !== false) {
          $array[trim($key)] = $array[$key];
          unset($array[$key]);
        }
      }
      return array_map(array($this, "trimmingKeys"), $array);
    }
    else {
      return $array;
    }
  }

  /**
   * Process a field data and return array composed for xml for this field
   *
   * @param $alias
   * field name on view.
   * e.g. titles>title,
   *
   * @param $value
   * array that contains the value.
   * e.g.
   * [
   *   ['#'] = 'University of Mexico']
   * ]
   *
   * @return array
   * array composed for xml in depth.
   * e.g.
   * [
   *  [titles] = [
   *    [title] = [
   *      [#] 'University of Mexico'
   *    ]
   *  ]
   * ]
   */
  protected function depth($alias, $value) {
    $parts = explode('>', $alias);
    if (count($parts) === 1) { // dcc, dc
      return [ $alias => $value ];
    }

    // datacite
    $output = []; $end_content = []; $n_values = count($value); $n_parts = count($parts);

    $end_at_pre_last = ($n_parts > 2)? true:false;

    for ($i = 0; $i < $n_values; $i++) {
      if ($end_at_pre_last) {
        $end_content[" $i"][end($parts)] = $value[" $i"];
      } else {
        $end_content[end($parts)][" $i"] = $value[" $i"];
      }
    }

    $i = 0;
    foreach (array_reverse($parts) as $part){
      if (count($output) == 0 && $n_values == 1 && !array_key_exists($alias, $this->tagsPrefixedWith0)) {
        if (strpos(key($value), "@") !== false || !empty($value['#'])) {
          if ($end_at_pre_last) {
            $output[" 0"][$part] = $value;
          } else {
            $output[$part][" 0"] = $value;
          }
        } else {
          if ($end_at_pre_last) {
            $output[" 0"][$part] = $value[" 0"];
          } else {
            $output[$part][" 0"] = $value[" 0"];
          }
        }
      } else if ( ($n_values > 1 && $i === 0) || ($n_values === 1 && $i === 0 && array_key_exists($alias, $this->tagsPrefixedWith0))) { // last element
        $i++;
      } else if ( $n_values > 1 && $i == 1 ) { // pre-last element
        if ($end_at_pre_last) {
          $this->tagsPrefixedWith0 += array("$alias" => $n_values);
          for($j = 0; $j < count($end_content); $j++) {
            $output[$part][" $j"] = $end_content[" $j"];
          }
        } else { // pre-last is the end
          $output[$part] = $end_content;
        }
        $i++;
      } else if ( $n_values === 1 && $i == 1 && array_key_exists($alias, $this->tagsPrefixedWith0) ) { // pre-last element
        for($j = 0; $j < $this->tagsPrefixedWith0[$alias]; $j++){
          $output[$part][" $j"][end($parts)] = $value;
        }
        $i++;
      } else { // other elements
        $last_key = key($output);
        $tmp = $output[$last_key];
        array_pop($output);
        $output[$part][$last_key] = $tmp;
      }
    }
    return $output;
  }

  /**
   * @param $alias
   * @return array|bool
   */
  protected function hasAttribute($alias) {
    $att = explode('@', $alias);
    if (count($att) > 1) {
      return $att;
    }

    return FALSE;
  }

}
