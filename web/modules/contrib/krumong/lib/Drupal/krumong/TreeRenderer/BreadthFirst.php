<?php

namespace Drupal\krumong;


class TreeRenderer_BreadthFirst extends TreeRenderer_Abstract {

  // hive management
  // ---------------------------------------------------------------------------

  protected function hiveDetectRecursion($data, $info) {
    if (FALSE === $info) {
      // This cannot happen!
      return FALSE;
    }
    elseif (serialize($info['trail']) === serialize($this->trailOfKeys)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  protected function hiveInit(&$data) {
    // Set the internal key.
    parent::hiveInit($data);
    $this->hiveAddRecursive($data);
  }

  protected function hiveAddRecursive(&$data) {

    if (is_object($data)) {
      $info = $this->hiveGetObjectInfo($data);
    }
    elseif (is_array($data)) {
      $info = $this->hiveGetArrayInfo($data);
    }
    else {
      // This element is not an array or object.
      // No recursion.
      return;
    }

    // From here on, $data is known to be an object or array.
    if (FALSE === $info) {
      // We visit this for the first time.
      $this->hiveAddElement($data);
      $this->hiveAddChildren($data);
    }
    elseif (count($info['trail']) <= count($this->trailOfKeys)) {
      // This position is not closer to root than the exisiting one.
      // Stop recursion.
    }
    else {
      // We found a position that is closer to root.
      $info['trail'] = $this->trailOfKeys;
      // Children need update, because they all move closer to root.
      $this->hiveAddChildren($data);
    }
  }

  protected function hiveAddChildren(&$data) {
    foreach ($data as $k => &$v) {
      if ($k === $this->hiveMarkerKey) {
        continue;
      }
      $this->trailOfKeys[] = $k;
      $this->hiveAddRecursive($v);
      array_pop($this->trailOfKeys);
    }
  }
}
