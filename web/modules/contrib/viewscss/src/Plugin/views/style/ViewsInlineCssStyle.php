<?php

namespace Drupal\viewscss\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render the view as inline css.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "viewscss_inline_css",
 *   title = @Translation("Inline CSS"),
 *   help = @Translation("Attaches views output as inline css to page."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class ViewsInlineCssStyle extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * @inheritDoc
   */
  public function render() {
    $rendered = parent::render();
    $rendered['#theme_wrappers']['viewscss_dummy'] = ['#view' => $this->view];
    return $rendered;
  }

}
