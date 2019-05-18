<?php

namespace Drupal\client_config_care\Validator;

class ArrayDiffer {

  public function hasDifference(array $originalConfig, array $newConfig): bool {

    $diff = $this->arrayRecursiveDiff($originalConfig, $newConfig);

    if ((\is_array($originalConfig) && \is_array($newConfig)) &&
      empty($diff)) {
      return FALSE;
    }

    return TRUE;
  }

  protected function arrayRecursiveDiff($aArray1, $aArray2) {
    $aReturn = [];

    foreach ($aArray1 as $mKey => $mValue) {
      if (array_key_exists($mKey, $aArray2)) {
        if (is_array($mValue)) {
          $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
          if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
        } else {
          if ($mValue != $aArray2[$mKey]) {
            $aReturn[$mKey] = $mValue;
          }
        }
      } else {
        $aReturn[$mKey] = $mValue;
      }
    }

    return $aReturn;
  }

}
