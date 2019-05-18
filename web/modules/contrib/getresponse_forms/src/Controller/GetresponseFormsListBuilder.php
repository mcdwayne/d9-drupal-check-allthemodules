<?php

namespace Drupal\getresponse_forms\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a listing of GetresponseFormss.
 *
 * @ingroup getresponse_forms
 */
class GetresponseFormsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['display_modes'] = $this->t('Display Modes');
    $header['lists'] = $this->t('GetResponse List');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    global $base_url;

    $block_url = Url::fromRoute('block.admin_display');
    $page_url = Url::fromUri($base_url . '/' . $entity->path);

    $block_mode = [
      '#title' => $this->t('Block'),
      '#type' => 'link',
      '#url' => $block_url
    ];

    $page_mode = [
      '#title' => $this->t('Page'),
      '#type' => 'link',
      '#url' => $page_url
    ];

    $modes = NULL;
    $gr_lists = getresponse_get_lists();

    switch ($entity->mode) {
      case GETRESPONSE_FORMS_BLOCK:
        $modes = $block_mode;
        break;
      case GETRESPONSE_FORMS_PAGE:
        $modes = $page_mode;
        break;
      case GETRESPONSE_FORMS_BOTH:
        $modes = array(
          'block_link' => $block_mode,
          'separator' => array(
            '#markup' => ' and ',
          ),
          'page_link' => $page_mode
        );
        break;
    }

    $list_id = $entity->gr_lists;
    if (!empty($list_id) && isset($gr_lists[$list_id])) {
      $list_url = Url::fromUri('https://app.getresponse.com/lists?id=' . $gr_lists[$list_id]->campaignId, array('attributes' => array('target' => '_blank', 'rel' => 'noopener noreferrer')));
      $list_link = [
        '#title' => $this->t($gr_lists[$list_id]->name),
        '#type' => 'link',
        '#url' => $list_url,
      ];
      $list_labels[] = $list_link;
      $list_labels[] = array('#markup' => ', ');
    }

    // Remove the last comma from the $list_labels array.
    array_pop($list_labels);

    $row['label'] = $this->getLabel($entity) . ' (Machine name: ' . $entity->id() . ')';
    $row['display_modes']['data'] = $modes;
    $row['lists']['data'] = $list_labels;

    return $row + parent::buildRow($entity);
  }

}
