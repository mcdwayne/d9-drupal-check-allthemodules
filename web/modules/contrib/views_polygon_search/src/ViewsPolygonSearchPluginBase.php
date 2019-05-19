<?php

namespace Drupal\views_polygon_search;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class where implemented all necessary methods.
 */
class ViewsPolygonSearchPluginBase implements ViewsPolygonSearchPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formOptions(&$form, FormStateInterface $form_state, array $options) {}

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormState $form_state, $handler) {}

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormState $form_state, $handler) {}

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormState $form_state, $handler) {}

}
