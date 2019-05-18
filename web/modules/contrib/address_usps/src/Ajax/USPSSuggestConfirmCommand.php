<?php

namespace Drupal\address_usps\Ajax;

use Drupal\address_usps\AddressUSPSHelper;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command for USPS address element conversion.
 */
class USPSSuggestConfirmCommand implements CommandInterface {

  protected $elementSelector;
  protected $originalData;
  protected $suggestedData;
  protected $dialogTitle;
  protected $dialogContent;
  protected $replaceRequired = TRUE;

  /**
   * USPSSuggestConfirmCommand constructor.
   *
   * @param string $element_selector
   *   Address element jQuery selector.
   * @param array $suggested_data
   *   Suggested data for element keyed by element keys.
   * @param array $original_data
   *   Original data for element keyed by element keys.
   * @param string $dialog_title
   *   Title for dialog.
   * @param array $dialog_content
   *   Render array that will be used as dialog content.
   */
  public function __construct($element_selector, array $suggested_data, array $original_data = [], $dialog_title = '', array $dialog_content = []) {
    $this->elementSelector = $element_selector;
    $this->originalData = $original_data;
    $this->suggestedData = $suggested_data;

    // Fill suggested data with not required values from original data.
    $properties_for_copy = [
      'given_name',
      'family_name',
      'organization',
    ];
    foreach ($properties_for_copy as $property) {
      if (isset($this->originalData[$property])) {
        $this->suggestedData[$property] = $this->originalData[$property];
      }
    }

    $this->dialogTitle = !empty($dialog_title) ? $dialog_title : $this->getDefaultDialogTitle();
    $this->dialogContent = !empty($dialog_content) ? $dialog_content : $this->getDefaultDialogContent();
  }

  /**
   * Returns default dialog title.
   *
   * @return string
   *   Translated string for dialog title.
   */
  public function getDefaultDialogTitle() {
    return t('Convert to USPS format');
  }

  /**
   * Returns default dialog content render array.
   *
   * @return array
   *   Default dialog content render array.
   */
  public function getDefaultDialogContent() {
    $from = AddressUSPSHelper::renderAddressElementByValue($this->originalData);
    $to = AddressUSPSHelper::renderAddressElementByValue($this->suggestedData);

    if ($from == $to) {
      $this->replaceRequired = FALSE;
    }

    $dialog_content = [
      'message' => [
        '#markup' => t('Do you want to convert address to USPS suggested format?'),
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [t('From'), t('To')],
        '1' => [
          'from' => $from,
          'to' => $to,
        ],
      ],
    ];

    return $dialog_content;
  }

  /**
   * Return an array to be run through json_encode and sent to the client.
   *
   * @return array
   *   Array to be run through json_encode and sent to the client.
   */
  public function render() {
    return [
      'command' => 'addressUSPSSuggestConfirm',
      'selector' => $this->elementSelector,
      'original_data' => $this->originalData,
      'suggested_data' => $this->suggestedData,
      'dialog_title' => $this->dialogTitle,
      'dialog_content' => \Drupal::service('renderer')->render($this->dialogContent),
      'replace_required' => $this->replaceRequired,
    ];
  }

}
