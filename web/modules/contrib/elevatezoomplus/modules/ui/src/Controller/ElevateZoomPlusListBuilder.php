<?php

namespace Drupal\elevatezoomplus_ui\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\slick_ui\Controller\SlickListBuilderBase;

/**
 * Provides a listing of ElevateZoomPlus optionsets.
 */
class ElevateZoomPlusListBuilder extends SlickListBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elevatezoomplus_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label'      => $this->t('Optionset'),
      'responsive' => $this->t('Responsive'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = Html::escape($entity->label());
    $row['responsive']['#markup'] = $entity->getSetting('responsive') ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

  /**
   * Adds some descriptive text to the elevatezoomplus optionsets list.
   *
   * @return array
   *   Renderable array.
   *
   * @see admin/config/development/configuration/single/export
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t("<p>Manage the ElevateZoomPlus optionsets. Optionsets are Config Entities.</p><p>By default, when this module is enabled, an optionset is created from configuration. Use the Operations column to edit, clone and delete optionsets.<br /><strong>Important!</strong> Avoid overriding Default optionset as it is meant for Default -- checking and cleaning. Use Duplicate instead. Otherwise messes are yours.<br />ElevateZoomPlus doesn't need ElevateZoomPlus UI to run. It is always safe to uninstall ElevateZoomPlus UI once done with optionsets.</p>"),
    ];

    $build[] = parent::render();
    return $build;
  }

}
