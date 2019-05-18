<?php

/**
 * @file
 * Contains \Drupal\edit_ui\Controller\EditUiBlockController.
 */

namespace Drupal\edit_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\edit_ui\Ajax\MessageCommand;
use Drupal\edit_ui\Ajax\AddBlockCommand;
use Drupal\block\Entity\Block;

/**
 * Controller managing edit_ui backbone block model.
 */
class EditUiBlockController extends ControllerBase {

  /**
   * Returns all blocks for the current theme.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function listAction() {
    $rendered = array();

    $theme  = \Drupal::config('system.theme')->get('default');

    $blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties(array('theme' => $theme));
    foreach ($blocks as $block_id => $block) {
      $rendered[] = $this->getBlockArray($block_id, $block);
    }

    return new JsonResponse($rendered);
  }

  /**
   * Do nothing.
   *
   * The block creation is managed by the createAction method.
   * This method is needed to avoid an error with Backbone.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function addAction() {
    return new JsonResponse(array());
  }

  /**
   * Returns the requested block.
   *
   * @param string $block_id
   *   The block instance ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function readAction($block_id) {
    $rendered = $this->getBlockArray($block_id, NULL, TRUE);
    unset($rendered['region']);
    unset($rendered['weight']);
    return new JsonResponse($rendered);
  }

  /**
   * Update given block.
   *
   * @param string $block_id
   *   The block instance ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function updateAction($block_id, Request $request) {
    $block = $this->getBlock($block_id);
    $data = Json::decode($request->getContent());

    if (isset($data['region'])) {
      $block->setRegion($data['region']);
    }
    if (isset($data['weight'])) {
      $block->setWeight($data['weight']);
    }
    if (isset($data['status'])) {
      $block->set('status', (bool) $data['status']);
    }
    if (isset($data['visibility'])) {
      $block->set('visibility', $data['visibility']);
    }
    $block->save();

    $rendered = $this->getBlockArray($block_id);
    return new JsonResponse($rendered);
  }

  /**
   * Delete given block.
   *
   * @param string $block_id
   *   The block instance ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function deleteAction($block_id) {
    $block = $this->getBlock($block_id);

    try {
      $block->delete();
      drupal_set_message(
        t('The block %block has been deleted', array('%block' => $block->label())),
        'status'
      );
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    $response = new AjaxResponse();
    $response->addCommand(new MessageCommand());

    return $response;
  }

  /**
   * Get the requested block.
   *
   * @param string $block_id
   *   The block instance ID.
   *
   * @return \Drupal\block\Entity\Block
   *   The block entity.
   */
  public function getBlock($block_id) {
    if (empty($block_id)) {
      throw new BadRequestHttpException(t('No block id specified.'));
    }

    $block = Block::load($block_id);
    if (empty($block)) {
      throw new BadRequestHttpException(t('Block not found.'));
    }

    return $block;
  }

  /**
   * Get the array representation requested block.
   *
   * @param mixed $block_id
   *   The block ID.
   * @param mixed $block
   *   The block instance (optionnal).
   * @param bool $with_content
   *   Return the block content or not.
   *
   * @return array
   *   The block array representation.
   */
  public function getBlockArray($block_id, $block = NULL, $with_content = FALSE) {
    if (!($block instanceof \Drupal\block\Entity\Block)) {
      $block = $this->getBlock($block_id);
    }

    $content = NULL;
    if ($with_content) {
      if ($block->access('view')) {
        $content = \Drupal::entityTypeManager()
          ->getViewBuilder($block->getEntityTypeId())
          ->view($block);
        $content = \Drupal::service('renderer')->renderRoot($content);
        $content = (string) $content;
      }
      else {
        $content = '';
      }
    }

    $settings = $block->get('settings');
    return array(
      'id'        => $block->getOriginalId(),
      'plugin_id' => $block->getPluginId(),
      'region'    => $block->getRegion(),
      'weight'    => $block->getWeight(),
      'label'     => $block->label(),
      'status'    => $block->status(),
      'html_id'   => Html::getId('block-' . $block_id),
      'provider'  => $settings['provider'],
      'content'   => $content,
    );
  }

  /**
   * AJAX callback called after a block modal is submitted.
   *
   * @param string $block_id
   *   The block instance ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function modalAction($block_id) {
    try {
      $response = new AjaxResponse();
      $block    = $this->getBlock($block_id);
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    $response->addCommand(new CloseDialogCommand());
    $response->addCommand(new MessageCommand());
    $response->addCommand(new AddBlockCommand($block));

    return $response;
  }

}
