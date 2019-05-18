<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort;

use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersSortWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Radio Buttons sort widget implementation.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "bef_links",
 *   label = @Translation("Links"),
 * )
 */
class Links extends BetterExposedFiltersSortWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state, $field) {
    $form[$field]['#theme'] = 'bef_links';

    // Exposed form displayed as blocks can appear on pages other than
    // the view results appear on. This can cause problems with
    // select_as_links options as they will use the wrong path. We
    // provide a hint for theme functions to correct this.
    $form[$field]['#bef_path'] = $this->getExposedFormActionUrl($form_state);
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
