<?php

namespace Drupal\views_tools;

use Zend\Feed\PubSubHubbub\HttpResponse;
use Symfony\Component\Yaml\Yaml;
use Drupal\views\Entity\View;

/**
 * Contains commonly used functions for Views Tools.
 */
class ViewsTools {

  /**
   * {@inheritdoc}
   */
  static public function deleteDisplay(View $view, $display = []) {
    if (empty($display)) {
      return FALSE;
    }
    $displays = $view->get('display');
    foreach ($displays as $key => $item) {
      if (in_array($key, (array) $display) && $key != 'default') {
        unset($displays[$key]);
      }
    }
    $view->set('display', $displays);
    return $view->save();
  }

  /**
   * {@inheritdoc}
   */
  static public function exportDisplaysAsView(View $view, $displayIds = []) {
    $newViewId = $view->id();
    while (View::load($newViewId)) {
      $newViewId .= '_1';
    }
    $newView = $view->createDuplicate()->set('id', $newViewId);
    $displays = $newView->get('display');
    foreach ($displays as $key => $display) {
      if (!in_array($key, (array) $displayIds) && $key != 'default') {
        unset($displays[$key]);
      }
    }
    $newView->set('display', $displays);
    $newView->save();
    return $newView;
  }

  /**
   * {@inheritdoc}
   */
  static public function exportDisplaysToYaml(View $view, $displayIds) {
    $newViewId = $view->id();
    while (View::load($newViewId)) {
      $newViewId .= '_1';
    }
    $newView = $view->createDuplicate()->set('id', $newViewId);
    $displays = $newView->get('display');
    foreach ($displays as $key => $display) {
      if (!in_array($key, (array) $displayIds) && $key != 'default') {
        unset($displays[$key]);
      }
    }
    $newView->set('display', $displays);
    $newView->save();
    $configFileName = 'views.view.' . $newView->id();
    $config = \Drupal::config($configFileName);
    $viewConfig = Yaml::dump($config->get());
    $newView->delete();
    $fileName = "$configFileName.yml";
    $response = new HttpResponse();
    $response->setContent($viewConfig);
    $response->setHeader('Content-Type', 'text/yaml');
    $response->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    $response->send();
    exit;
  }

}
