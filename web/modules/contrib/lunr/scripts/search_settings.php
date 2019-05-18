<?php

/**
 * @file
 * Allows Node to get information about a search.
 */

use Drupal\Core\Url;

if (php_sapi_name() !== 'cli') {
  die;
}

$input_data = file_get_contents('php://stdin');
$input = json_decode($input_data, TRUE);

if (!is_array($input) || empty($input['id'])) {
  throw new \Exception('Invalid input.');
}

$lunr_search = \Drupal::entityTypeManager()->getStorage('lunr_search')->load($input['id']);

$view = $lunr_search->getView();

$paths = [];
$upload_paths = [];
foreach (\Drupal::languageManager()->getLanguages() as $language) {
  $paths[] = $view->getUrl()->setOption('language', $language)->toString();
  $upload_paths[] = Url::fromRoute('entity.lunr_search.upload_index', [
    'lunr_search' => $lunr_search->id(),
  ], ['language' => $language])->toString();
}

$settings = [
  'paths' => $paths,
  'uploadPaths' => $upload_paths,
  'usePager' => $view->getPager()->usePager(),
  'indexFields' => $lunr_search->getIndexFields(),
  'displayField' => $lunr_search->getDisplayField(),
];

echo json_encode($settings);
