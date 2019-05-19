<?php

namespace Drupal\tealium\Data;

/**
 * Class for binding Tealium utag_data to a jQuery selector event.
 *
 * Binds the sending of a Tealium Universal Data Object (utag_data)
 * to the firing of a jQuery element event.
 */
class TealiumJqueryEventBinding implements TealiumJqueryEventBindingInterface {

  private $jQuerySelector = '';

  private $domEvent = 'click';

  private $trackType = 'link';

  private $tealiumData = NULL;

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $utag_data = strval($this->getTealiumData());
    $selector = $this->getJquerySelector();
    $event = $this->getDomEvent();
    $type = $this->getTrackType();

    return <<< "__END_OF_BOUND_VARIABLE_SCRIPT__"

    (function($, u) {
      $(document).ready(function () {
        $('{$selector}').bind('{$event}', function(event){
          u.{$type}({$utag_data});
        });
      });
    }(jQuery, utag));
__END_OF_BOUND_VARIABLE_SCRIPT__;
  }

  /**
   * Gets jQuery code to attach to module.
   *
   * @TODO: Look at renaming this method to improve
   * @codingStandardsIgnoreStart
   */
  function getJqueryCodeToAttachBindings() {
    // @codingStandardsIgnoreEnd
    if ($this->getTealiumData() !== NULL
        && $this->getJquerySelector()
        && $this->getDomEvent()
        && $this->getTrackType()
    ) {
      return strval($this);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see jQuery
   *
   * @link http://api.jquery.com/jQuery/#jQuery-selector-context jQuery
   */
  public function setJquerySelector($jquery_selector) {
    $this->jQuerySelector = strval($jquery_selector);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJquerySelector() {
    return $this->jQuerySelector;
  }

  /**
   * {@inheritdoc}
   *
   * @param string $event_name
   *   A string containing one or more DOM event types,
   *   such as "click" or "submit," or custom event names.
   *
   * @link http://api.jquery.com/bind/ jQuery.bind
   */
  public function setDomEvent($event_name) {
    $this->domEvent = strval($event_name);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomEvent() {
    return $this->domEvent;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO remove this method and switch usages to self::setTrackTypeToLink or self::setTrackTypeToView
   */
  public function setTrackType($tracking_type) {
    if ($tracking_type === 'link' || $tracking_type === 'view') {
      $this->trackType = $tracking_type;
    }

    return $this;
  }

  /**
   * Set the Tealium tracking type to 'link'.
   *
   * @return $this
   */
  public function setTrackTypeToLink() {
    $this->trackType = 'link';

    return $this;
  }

  /**
   * Set the Tealium tracking type to 'view'.
   *
   * @return $this
   */
  public function setTrackTypeToView() {
    $this->trackType = 'view';

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackType() {
    return $this->trackType;
  }

  /**
   * {@inheritdoc}
   */
  public function setTealiumData(TealiumUtagData $tealium_data_source_values) {
    $this->tealiumData = $tealium_data_source_values;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTealiumData() {
    if ($this->tealiumData instanceof TealiumUtagData
        && count($this->tealiumData->getAllDataSourceValues()) > 0
    ) {
      return $this->tealiumData;
    }
    else {
      return NULL;
    }
  }

}
