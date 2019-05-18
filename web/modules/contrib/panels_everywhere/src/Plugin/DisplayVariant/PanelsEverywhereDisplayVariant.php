<?php

namespace Drupal\panels_everywhere\Plugin\DisplayVariant;

use Drupal\Core\Form\FormStateInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;

/**
 * Provides a display variant that simply contains blocks.
 *
 * @todo: This shouldn't be necessary - the PanelsDisplayVariant should
 * implement PageVariantInterface, because, it's easy and then it can be used
 * to render the full page.
 *
 * @DisplayVariant(
 *   id = "panels_everywhere_variant",
 *   admin_label = @Translation("Panels Everywhere")
 * )
 */
class PanelsEverywhereDisplayVariant extends PanelsDisplayVariant implements PageVariantInterface {

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent = [];

  /**
   * The title for the display variant.
   *
   * @var string
   */
  protected $title;

  /**
   * Sets the title for the page being rendered.
   *
   * @param string|array $title
   *   The page title: either a string for plain titles or a render array for
   *   formatted titles.
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->title = (string) (is_array($title) ? drupal_render_root($title) : $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $main_content_included = NULL;
    $this->setPageTitle($this->title);
    foreach ($this->getRegionAssignments() as $region => $blocks) {
      if (!$blocks) {
        continue;
      }
      foreach ($blocks as $block_id => $block) {
        if ($block instanceof MainContentBlockPluginInterface) {
          $block->setMainContent($this->mainContent);
          $main_content_included = [$region, $block_id];
        }
      }
    }

    // Build it the render array!
    $build = parent::build();

    // Copied from BlockPageVariant.php.
    // The main content block cannot be cached: it is a placeholder for the
    // render array returned by the controller. It should be rendered as-is,
    // with other placed blocks "decorating" it.
    if (!empty($main_content_included)) {
      list ($region, $block_id) = $main_content_included;
      unset($build[$region][$block_id]['#cache']['keys']);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['route_override_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable page-manager route override'),
      '#default_value' => $this->isRouteOverrideEnabled(),
      '#description' => $this->t('The default behaviour of page-manager is create a route for the specified path and override any existing route (which effectively replaces that route). This behaviour is not desired for panels_everywhere as it prevents the original content of the route from being rendered properly. This is why panels_everywhere will remove those overrides by default. You may want do enable route overrides in cases you do not want the original route to be processed.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['route_override_enabled'] = $form_state->getValue('route_override_enabled');
  }

  /**
   * Denotes whether route overrides are enabled for this variant.
   *
   * @return bool
   *   True if route overrides are enabled for this variant, false otherwise.
   */
  public function isRouteOverrideEnabled() {
    return !empty($this->configuration['route_override_enabled']) ? $this->configuration['route_override_enabled'] : FALSE;
  }
}
