<?php

namespace Drupal\nodeletter\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\nodeletter\Entity\NodeletterSendingInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\nodeletter\SendingStatus;

/**
 * Provides an element to list sending entities.
 *
 * @RenderElement("nodeletter_sending_list")
 */
class NodeletterSendingList extends RenderElement {


  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => FALSE,
      '#pre_render' => array(
        array($class, 'preRenderList'),
      ),
    ];
  }

  /**
   * Pre-render callback: Renders a list of sending entities into a render array.
   *
   * Doing so during pre_render gives modules a chance to alter the output.
   *
   * @param array $element
   *   A structured array:
   *   - #sendings: List of sendings to be rendered.
   *
   * @return array
   *   The passed-in element with processed sending entities.
   */
  public static function preRenderList($element) {

    if(empty($element['#sendings'])) {
      return $element;
    }

    /** @var DateFormatterInterface $date_formatter */
    static $date_formatter = NULL;
    if ( ! $date_formatter) {
      $date_formatter = \Drupal::service('date.formatter');
    }

    $element['sendings'] = [
      '#type' => 'table',
      '#header' => [t('Date'), t('Status'), t('Submitted by'), t('Comment')],
    ];

    $sendings = $element['#sendings'];
    foreach($sendings as $i => $sending) {
      if( ! $sending instanceof NodeletterSendingInterface ) {
        continue;
      }
      /** @var NodeletterSendingInterface $sending */

      $status = '';
      if ($sending->getMode() == 'test') {
        $status = "Test Sending to " . $sending->getTestRecipient() . "<br/>";
      }
      if ($sending->getSendingStatus() == SendingStatus::FAILED) {
        $status .= t('Error: %msg', ['%msg' => $sending->getErrorMessage()]);
      } else {
        $status .= $sending->getSendingStatus();
      }

      $element['sendings'][$i]['date'] = [
        '#type' => 'markup',
        '#markup' => $date_formatter->format($sending->getChangedTime())
      ];
      $element['sendings'][$i]['status'] = [
        '#markup' => $status
      ];
      $element['sendings'][$i]['author'] = [
        '#plain_text' => $sending->getOwner()->getAccountName(),
      ];
      $element['sendings'][$i]['comment'] = [
        '#plain_text' => $sending->getComment()
      ];
    }

    return $element;
  }

}