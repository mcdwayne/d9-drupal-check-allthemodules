<?php

namespace Drupal\panels_extended;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\panels_extended\BlockConfig\AdminInfoInterface;
use Drupal\panels_extended\BlockConfig\BlockFormInterface;
use Drupal\panels_extended\BlockConfig\BlockFormWithValidationInterface;
use Drupal\panels_extended\BlockConfig\JsonConfigurationInterface;
use Drupal\panels_extended\BlockConfig\JsonOutputInterface;
use Drupal\panels_extended\BlockConfig\VisibilityInterface;

/**
 * Defines a base block implementation for outputting to JSON.
 */
abstract class JsonBlockBase extends BlockBase implements AdminInfoInterface, JsonConfigurationInterface, JsonOutputInterface, VisibilityInterface {

  /**
   * The configuration plugins to use in the block.
   *
   * @var \Drupal\panels_extended\BlockConfig\BlockConfigBase[]
   */
  protected $configs = [];

  /**
   * The reason when the block isn't visible.
   *
   * @var string|null
   */
  protected $notVisibleReason = NULL;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    foreach ($this->configs as $config) {
      if ($config instanceof BlockFormInterface) {
        $config->modifyBlockForm($form, $form_state);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    foreach ($this->configs as $config) {
      if ($config instanceof BlockFormWithValidationInterface) {
        $config->validateBlockForm($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($this->configs as $config) {
      if ($config instanceof BlockFormInterface) {
        $config->submitBlockForm($form, $form_state);
      }
    }
  }

  /**
   * Gets the block type for front-end.
   *
   * @return string
   *   The block type for the front-end.
   */
  public function getBlockType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPrimaryInfo() {
    $data = [];
    foreach ($this->configs as $config) {
      if ($config instanceof AdminInfoInterface) {
        $info = $config->getAdminPrimaryInfo();
        if ($info !== NULL) {
          $data[] = $info;
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminSecondaryInfo() {
    $short = [];
    $long = [];
    foreach ($this->configs as $config) {
      if ($config instanceof AdminInfoInterface) {
        $info = $config->getAdminSecondaryInfo();
        if ($info !== NULL && count($info) === 2) {
          $short[] = $info[0];
          $long[] = $info[1];
        }
      }
    }
    return [
      implode(' • ', $short),
      implode(PHP_EOL, $long),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationForJson() {
    $label = NULL;
    if ($this->configuration['label_display'] === BlockPluginInterface::BLOCK_LABEL_VISIBLE) {
      // @todo replace tokens?
      // $label = $this->tokenService->replace($this->label(), $this->getContextValues());
      $label = $this->label();
    }

    $customConfig = [
      'uuid' => $this->configuration['uuid'],
      'label' => $label,
    ];
    foreach ($this->configs as $config) {
      if ($config instanceof JsonConfigurationInterface) {
        $customConfig += $config->getConfigurationForJson();
      }
    }
    return $customConfig;
  }

  /**
   * Gets the data for the block content.
   *
   * @return array
   *   The data for the block content.
   */
  protected function getData() {
    return [];
  }

  /**
   * Prepare the data for rendering to HTML.
   *
   * @param array $data
   *   The data as fetched from self::getData().
   *
   * @return array
   *   Renderable array.
   */
  protected function prepareDataForHtmlRendering(array $data) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForJson() {
    return [
      '#configuration' => $this->getConfigurationForJson(),
      'type' => $this->getBlockType(),
      'content' => $this->getData(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    foreach ($this->configs as $config) {
      if ($config instanceof VisibilityInterface) {
        if (!$config->isVisible()) {
          $this->notVisibleReason = $config->getNotVisibleReason();
          return FALSE;
        }
      }
    }
    if (!$this->renderIfNoData() && empty($this->getData())) {
      $this->notVisibleReason = 'No data available for this block.';
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotVisibleReason() {
    return $this->notVisibleReason;
  }

  /**
   * Do we render the block when we have no data?
   *
   * @return bool
   *   TRUE to show if the block has no data, FALSE otherwise.
   */
  protected function renderIfNoData() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $start = microtime(TRUE);
    $data = $this->getData();
    $content = $this->prepareDataForHtmlRendering($data);
    $loadingTime = round(microtime(TRUE) - $start, 3) * 1000;

    $result = [
      '#theme' => 'panels_extended_base_block_build',
      '#blockType' => $this->getBlockType(),
      '#loadtime' => $loadingTime,
      '#content' => $content,
    ];

    return $result;
  }

}
