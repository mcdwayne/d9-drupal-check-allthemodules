<?php

namespace Drupal\inmail\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for analyzers.
 *
 * Analyzer plugins that inherit
 * \Drupal\Component\Plugin\ConfigurablePluginInterface may specify
 * plugin-specific configuration.
 *
 * @ingroup analyzer
 */
class AnalyzerConfigurationForm extends PluginConfigurationForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.inmail.analyzer'),
      $container->get('entity.manager')->getStorage('inmail_analyzer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('entity.inmail_analyzer.collection');
  }

}
