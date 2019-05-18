<?php
namespace Drupal\forena\FrxPlugin\Renderer;
use Drupal\forena\AppService;
use Drupal\forena\FrxAPI;

/**
 * Report Listing
 *
 * @FrxRenderer(id = "FrxMyReports")
 */
class FrxMyReports extends RendererBase {

  /*
   * This custom renderer uses the existing forena reports module
   */
  public function render() {
    $report_list = '';
    $variables = $this->mergedAttributes();
    $category = isset($variables['category']) ? $variables['category']: '';

    if (AppService::instance()->access('list reports')) {
      $report_list = forena_user_reports($category);
    }
    return $report_list;
  }
}