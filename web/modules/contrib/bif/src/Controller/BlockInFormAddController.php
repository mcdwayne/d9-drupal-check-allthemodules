<?php
/**
 * @file
 */

namespace Drupal\block_in_form\Controller;


use Drupal\Core\Controller\ControllerBase;

class BlockInFormAddController extends ControllerBase {
  /**
   * Build the block instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the block instance.
   * @param string $theme
   *   The name of the theme for the block instance.
   *
   * @return array
   *   The block instance edit form.
   */
  public function blockAddConfigureForm($plugin_id, $entity_type_id, $bundle) {
    // Create a block entity.
    $entity = $this->entityManager()->getStorage('block')
      ->create(
        [
          'plugin' => $plugin_id,
          'entity_type_id' => $entity_type_id,
          'bundle' => $bundle
        ]
      );

    $form = $this->entityFormBuilder()->getForm($entity);
    $form['theme']['#access'] = FALSE; $form['region']['#access'] = FALSE;

    $form['entity_type_id'] = [
      '#title'  => t('Entity type'),
      '#type' => 'textfield',
      '#value' => $entity_type_id
    ];

    $form['bundle'] = [
      '#title' => t('Entity bundle'),
      '#type' => 'textfield',
      '#value'  => $bundle
    ];

    return $form;
  }
}