<?php

namespace Drupal\cloudwords\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Filter based on translatable in current project or not for current user.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("cloudwords_translatable_bundle_filter")
 */
class Bundle extends ManyToOne {

  /**
   * Gets the values of the options.
   *
   * @return array
   *   Returns options.
   */
  public function getValueOptions() {
    $this->valueOptions = [];
    $exposed_input = $this->view->getExposedInput();

    if(!isset($exposed_input['textgroup'])){
      return $this->valueOptions;
    }
    // @todo textgroup and entity bundles
    $bundle_opts = [];

    // @todo entityManager is deprecated, change
    $bundles = \Drupal::entityManager()->getAllBundleInfo();
    $entity_type_id = $exposed_input['textgroup'];
    foreach ($bundles[$entity_type_id] as $bundle => $bundle_info) {
      $config = \Drupal\language\Entity\ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
      $content_translation_settings = $config->getThirdPartySettings('content_translation');
      if(isset($content_translation_settings['enabled']) && $content_translation_settings['enabled'] == 1) {
        $bundle_opts[$config->getTargetBundle()] = $bundle_info['label'];
      }
    }

    $this->valueOptions = $bundle_opts;
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $exposed_input = $this->view->getExposedInput();
    if(!isset($exposed_input['textgroup']) || (isset($exposed_input['textgroup']) && $exposed_input['textgroup'] == 'All')){
      $form['bundle']['#disabled'] = true;
      $form['bundle']['#type'] = 'hidden';
    }
  }

}
