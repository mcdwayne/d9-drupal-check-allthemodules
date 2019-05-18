<?php

namespace Drupal\flexiform\FormEnhancer;

use Drupal\Core\Plugin\PluginBase;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;

/**
 * Base class for form enhancers.
 */
class FormEnhancerBase extends PluginBase implements FormEnhancerInterface {

  /**
   * The form display entity.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * An array of supported events.
   *
   * @var array
   */
  protected $supportedEvents = [];

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(FlexiformEntityFormDisplayInterface $form_display) {
    $this->formDisplay = $form_display;
    return $this;
  }

  /**
   * Get the form display.
   *
   * @return \Drupal\flexiform\FlexiformEntityFormDisplayInterface
   *   The form display.
   */
  protected function getFormDisplay() {
    return $this->formDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($event) {
    return in_array($event, $this->supportedEvents);
  }

}
