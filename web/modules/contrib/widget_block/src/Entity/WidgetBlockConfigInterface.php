<?php
/**
 * @file
 * Contains \Drupal\widget_block\Entity\WidgetBlockConfigInterface.
 */

namespace Drupal\widget_block\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface which describes a widget block configuration entity.
 */
interface WidgetBlockConfigInterface extends ConfigEntityInterface {

  /**
   * Includes a widget using embed code.
   *
   * @var string
   */
  const MODE_EMBED = 'embed';

  /**
   * Includes a widget using server side include without seperate
   * assets.
   *
   * @var string
   */
  const MODE_SSI = 'ssi';

  /**
   * Includes a widget using server side include which provides the
   * different assets natively to the CMS.
   *
   * @var string
   */
  const MODE_SMART_SSI = 'smart-ssi';

  /**
   * HTTP protocol without SSL support.
   *
   * @var string
   */
  const PROTOCOL_HTTP = 'http';

  /**
   * HTTP protocol with SSL support.
   *
   * @var string
   */
  const PROTOCOL_HTTPS = 'https';

  /**
   * Get the widget identifier.
   *
   * @return string
   *   Unique identifier of the widget.
   */
  public function id();

  /**
   * Get the protocol.
   *
   * @return string
   *   Protocol which should be used for server to server communication.
   *
   * @see WidgetBlockConfigInterface::PROTOCOL_HTTP
   * @see WidgetBlockConfigInterface::PROTOCOL_HTTPS
   */
  public function getProtocol();

  /**
   * Set the protocol.
   *
   * @param string $protocol
   *   Protocol to use for server to server communication.
   *
   * @see WidgetBlockConfigInterface::PROTOCOL_HTTP
   * @see WidgetBlockConfigInterface::PROTOCOL_HTTPS
   */
  public function setProtocol($protocol);

  /**
   * Get the hostname.
   *
   * @return string
   *   String which contains the hostname.
   */
  public function getHostname();

  /**
   * Set the hostname.
   *
   * @param string $hostname
   *   String which contains the hostname.
   */
  public function setHostname($hostname);

  /**
   * Get the widget include mode.
   *
   * @return integer
   *   The mode currently used for including a widget.
   *
   * @see WidgetBlockConfigInterface::WIDGET_MODE_EMBED
   * @see WidgetBlockConfigInterface::WIDGET_MODE_SSI
   * @see WidgetBlockConfigInterface::WIDGET_MODE_SMART_SSI
   */
  public function getIncludeMode();

  /**
   * Set the widget include mode.
   *
   * @param string $mode
   *   The mode which should be used for including a widget.
   *
   * @see WidgetBlockConfigInterface::WIDGET_MODE_EMBED
   * @see WidgetBlockConfigInterface::WIDGET_MODE_SSI
   * @see WidgetBlockConfigInterface::WIDGET_MODE_SMART_SSI
   */
  public function setIncludeMode($mode);

  /**
   * Get the widget block markup.
   *
   * @return \Drupal\widget_block\Renderable\WidgetMarkupInterface
   *   An instance of WidgetMarkupInterface.
   */
  public function getMarkup();

}
