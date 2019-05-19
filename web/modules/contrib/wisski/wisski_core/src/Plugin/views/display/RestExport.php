<?php

namespace Drupal\wisski_core\Plugin\views\display;

use Drupal\rest\Plugin\views\display\RestExport as OriginalRestExport;

/**
 * The plugin that handles Data response callbacks for REST resources.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "wisski_rest_export",
 *   title = @Translation("WissKI's REST export"),
 *   help = @Translation("Create a REST export resource for WissKI."),
 *   uses_route = TRUE,
 *   admin = @Translation("WissKI's REST export"),
 *   returns_response = TRUE
 * )
 */
class RestExport extends OriginalRestExport {
  
    /**
     * {@inheritdoc}
     */
    public function usesExposed() {
      return FALSE;
    }



}
