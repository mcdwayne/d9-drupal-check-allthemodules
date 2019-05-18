<?php

namespace Drupal\inmail\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\inmail\AnalyzerConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Route controller for message analyzers.
 *
 * @ingroup analyzer
 */
class AnalyzerController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.inmail.analyzer'));
  }

  /**
   * Returns a title for the analyzer configuration edit page.
   */
  public function titleEdit(AnalyzerConfigInterface $inmail_analyzer) {
    return $this->t('Configure %label analyzer', array('%label' => $inmail_analyzer->label()));
  }

  /**
   * Enables a message analyzer.
   */
  public function enable(AnalyzerConfigInterface $inmail_analyzer) {
    $inmail_analyzer->enable()->save();
    return new RedirectResponse(Url::fromRoute('entity.inmail_analyzer.collection', [], ['absolute' => TRUE])->toString());
  }

  /**
   * Disables a message analyzer.
   */
  public function disable(AnalyzerConfigInterface $inmail_analyzer) {
    $inmail_analyzer->disable()->save();
    return new RedirectResponse(Url::fromRoute('entity.inmail_analyzer.collection', [], ['absolute' => TRUE])->toString());
  }

}
