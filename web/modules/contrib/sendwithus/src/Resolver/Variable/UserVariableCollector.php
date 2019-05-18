<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Resolver\Variable;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Template;

/**
 * Provides a variable collector for user module.
 */
class UserVariableCollector implements VariableCollectorInterface {

  /**
   * {@inheritdoc}
   */
  public function collect(Template $template, Context $context) : void {
    $data = $context->getData();

    /** @var \Drupal\user\UserInterface $account */
    $account = $data->get('params')['account'] ?? NULL;

    if (!$account) {
      return;
    }
    $variables = [
      'name' => $account->getAccountName(),
      'display_name' => $account->getDisplayName(),
      'mail' => $account->getEmail(),
      'edit_url' => $account->toUrl('edit-form', ['absolute' => TRUE])->toString(),
      'cancel_url' => $account->toUrl('cancel-form', ['absolute' => TRUE])->toString(),
    ];

    switch ($data->get('key')) {
      case 'status_activated':
      case 'register_no_approval_required':
      case 'register_admin_created':
      case 'password_reset':
        $variables['reset_url'] = user_pass_reset_url($account);
        break;
    }
    $template->setTemplateVariable('user', $variables);
  }

}
