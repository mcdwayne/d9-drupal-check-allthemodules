<?php

namespace Drupal\dpl;

class PreviewLinkInstance {

  /**
   * The link instance machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The tab label.
   *
   * @var string
   */
  protected $tabLabel;

  /**
   * The preview URL before token replacement.
   *
   * @var string
   */
  protected $previewUrl;

  /**
   * @var string
   */
  protected $openExternalLabel;

  /**
   * @var string 
   */
  protected $defaultSize;

  /**
   * PreviewLinkInstance constructor.
   * @param string $id
   * @param string $tabLabel
   * @param string $openExternalLabel
   * @param string $previewUrl
   * @param string $defaultSize
   */
  public function __construct($id, $tabLabel, $openExternalLabel, $previewUrl, $defaultSize) {
    $this->id = $id;
    $this->tabLabel = $tabLabel;
    $this->openExternalLabel = $openExternalLabel;
    $this->previewUrl = $previewUrl;
    $this->defaultSize = $defaultSize;
  }

  /**
   * @return string
   */
  public function id() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getTabLabel() {
    return $this->tabLabel;
  }

  /**
   * @return string
   */
  public function getPreviewUrl() {
    return $this->previewUrl;
  }

  /**
   * @return string
   */
  public function getOpenExternalLabel() {
    return $this->openExternalLabel;
  }

  public function getBrowserSizes() {
    return [
      new BrowserSize(240, 500, 'Small', 's'),
      new BrowserSize(500, 800, 'Medium', 'm'),
      new BrowserSize(800, 1200, 'Large', 'l'),
      new BrowserSize(1200, -1, 'Extra large', 'xl'),
      new BrowserSize(-1, -1, 'Full width', 'full'),
    ];
  }

  /**
   * @return string
   */
  public function getDefaultSize() {
    return $this->defaultSize;
  }

}
