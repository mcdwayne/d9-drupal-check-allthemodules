<?php

namespace Drupal\eloqua_app_cloud\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Eloqua AppCloud Content Responder plugins.
 * The height and width parameters define the size of the content instance when rendered, while
 * editorImageUrl specifies the URL for an image that Eloqua will display in the
 * editor's design surface.
 */
interface EloquaAppCloudContentResponderInterface extends EloquaAppCloudInteractiveResponderInterface {

  /**
   * @return string
   *  The height to render the content.
   */
  public function height();

  /**
   * @return string
   *  The width to render the content.
   */
  public function width();

  /**
   * @return string
   *  The absolute URL to an image which will be displayed when an editor inserts this content in an email.
   */
  public function editorImageUrl();

}
