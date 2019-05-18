<?php

namespace Drupal\account_modal\AjaxCommand;

use Drupal\Core\Ajax\CommandInterface;

/**
 * An Ajax Command that refreshes the current page.
 */
class RefreshPageCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'accountModalRefreshPage',
    ];
  }

}
