<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\pager;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersPagerWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Radio Buttons pager widget implementation.
 *
 * @BetterExposedFiltersPagerWidget(
 *   id = "bef_links",
 *   label = @Translation("Links"),
 * )
 */
class Links extends BetterExposedFiltersPagerWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    if (count($form['items_per_page']['#options']) > 1) {
      $form['items_per_page']['#theme'] = 'bef_links';
      $form['items_per_page']['#items_per_page'] = max($form['items_per_page']['#default_value'], key($form['items_per_page']['#options']));

      // Exposed form displayed as blocks can appear on pages other than
      // the view results appear on. This can cause problems with
      // select_as_links options as they will use the wrong path. We
      // provide a hint for theme functions to correct this.
      $form['items_per_page']['#bef_path'] = $this->getExposedFormActionUrl($form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormAlter(array &$form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * Returns exposed form action URL object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Exposed views form state.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  protected function getExposedFormActionUrl(FormStateInterface $form_state) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $form_state->get('view');
    $display = $form_state->get('display');

    if (isset($display['display_options']['path'])) {
      return Url::fromRoute(implode('.', [
        'view',
        $view->id(),
        $display['id'],
      ]));
    }

    $request = \Drupal::request();
    $url = Url::createFromRequest($request);
    $url->setAbsolute();

    return $url;
  }

}
