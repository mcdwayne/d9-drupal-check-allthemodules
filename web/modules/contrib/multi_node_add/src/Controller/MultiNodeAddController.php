<?php

namespace Drupal\multi_node_add\Controller;

use Drupal\node\Controller\NodeController;
use Drupal\node\NodeTypeInterface;

/**
 * Controller for Multi Node Add.
 */
class MultiNodeAddController extends NodeController {

  /**
   * Provides links to specific bundle multi node add forms.
   */
  public function overview() {
    $content = [];

    // Only use node types the user has access to.
    foreach (\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple() as $type) {
      if (\Drupal::entityTypeManager()->getAccessControlHandler('node')->createAccess($type->getOriginalId())) {
        $content[$type->getOriginalId()] = $type;
      }
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('multi_node_add.add', ['node_type' => $type->type]);
    }

    return [
      '#theme' => 'multi_node_add_list',
      '#content' => $content,
    ];
  }

  /**
   * Content of the iframe with the modified node form.
   */
  public function formPage(NodeTypeInterface $node_type = NULL) {
    $account = $this->currentUser();
    $langcode = $this->moduleHandler()->invoke('language', 'get_default_langcode', ['node', $node_type->getOriginalId()]);

    $node = $this->entityTypeManager()->getStorage('node')->create([
      'uid' => $account->id(),
      'name' => $account->getAccountName() ?: '',
      'type' => $node_type->getOriginalId(),
      'langcode' => $langcode ? $langcode : $this->languageManager()->getCurrentLanguage()->getId(),
    ]);

    $form = $this->entityFormBuilder()->getForm($node, 'default', ['multi_node_add_hijacked' => TRUE]);
    return $form;
  }

  /**
   * Status page after the node creation.
   *
   * @todo: upgrade the calls to D8
   */
  public function statusPage($node = NULL) {
    return $this->renderBarePage(
      t('The node is created. Title: %title , node id: !nid',
        [
          '%title' => $node->title,
          '!nid' => l($node->nid, 'node/' . $node->nid,
            [
              'attributes' => [
                'target' => '_blank',
              ],
            ]
          ),
        ]
      )
    );
  }

  /**
   * Renders a bare page for the iFrame.
   *
   * @todo: get rid of symfony exception
   * @todo: make sure that status messages are printed
   */
  private function renderBarePage($output) {
    $render = [
      '#type' => 'page',
      '#page_object' => drupal_render($output),
    ];
    $render['#attached']['library'][] = 'multi_node_add/multi_node_add';
    return $render;
  }

}
