<?php

namespace Drupal\js_management;

class JavaScriptManaged {
  public function getScripts() {
    $query = \Drupal::entityQuery('js_management_managed_js');
    $ids = $query->execute();
    $scripts = \Drupal::entityTypeManager()->getStorage('js_management_managed_js')->loadMultiple($ids);

    return $scripts;
  }
}
