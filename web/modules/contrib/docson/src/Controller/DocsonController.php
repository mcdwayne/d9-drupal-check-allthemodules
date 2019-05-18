<?php

namespace Drupal\docson\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides callback methods for defined docson routes.
 */
class DocsonController extends ControllerBase {

  /**
   * Page callback for the 'docson.schema_inspector' route.
   *
   * If the 'schema' query parameter exists, return the render array with the
   * value passed. Otherwise, redirect back to the the 'Schema Selector' page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current requeest object.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   If the 'schema' query parameter exists, return the render aray.
   *   Otherwise, return the redirect response to the 'Schema Selector' page.
   */
  public function inspectSchema(Request $request) {
    $base_path = $this->moduleHandler()->getModule('docson')->getPath();
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'src' => sprintf('/%s/js/widget.js', $base_path),
        'data-schema' => '/docson',
      ],
    ];

    if ($schema = $request->query->get('schema')) {
      $build['#attributes']['data-schema'] = $schema;
    }
    else {
      drupal_set_message($this->t('No schema was selected, please choose one.'), 'warning');
      return new RedirectResponse(Url::fromRoute('docson.schema_selector')->toString());
    }

    return $build;
  }

}
