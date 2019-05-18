<?php

namespace Drupal\jmol_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Display a chart with minimal options.
 */
class Index extends ControllerBase {

  /**
   * Function content.
   */
  public function content() {
    $lite_items[] = $this->l($this->t('Manage Jmol regions with Jquery'), new Url('jmol_examples.lite2'));
    $lite_items[] = $this->l($this->t('Render inline data'), new Url('jmol_examples.lite3'));
    $lite_items[] = $this->l($this->t('Render an external file'), new Url('jmol_examples.lite4'));

    $output['Lite'] = [
      '#title' => 'Lite version',
      '#theme' => 'item_list',
      '#items' => $lite_items,
    ];

    $full_items[] = $this->l($this->t('Full Version'), new Url('jmol_examples.content'));
    $full_items[] = $this->l($this->t('Super Simple'), new Url('jmol_examples.supersimple'));

    $output['Full'] = [
      '#title' => 'Full Version',
      '#theme' => 'item_list',
      '#items' => $full_items,
    ];
    return $output;
  }

}
