<?php

namespace Drupal\decoupled_auth\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for whether user is decoupled.
 *
 * Field handler label is 'Web account', so therefore if the user IS
 * decoupled, it does NOT have a web account.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_decoupled")
 */
class Decoupled extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['uid'] = 'uid';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $this->getValue($values, 'uid');
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);

    // Render whether the user does or does not have a web account.
    return $user->isDecoupled() ? 'No' : 'Yes';
  }

}
