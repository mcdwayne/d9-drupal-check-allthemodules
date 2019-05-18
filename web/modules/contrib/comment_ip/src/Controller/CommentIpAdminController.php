<?php

namespace Drupal\comment_ip\Controller;

use Drupal\comment\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Allow for a confirmation form for banning IP addresses.
 */
class CommentIpAdminController extends AdminController {

  /**
   * Presents an administrative comment listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param string $type
   *   The type of the overview form ('approval' or 'new') default to 'new'.
   *
   * @return array
   *   Then comment multiple delete confirmation form or the comments overview
   *   administration form.
   */
  public function adminPage(Request $request, $type = 'new') {

    if ($request->request->get('operation') == 'ban' && $request->request->get('comments')) {
      return $this->formBuilder->getForm('\Drupal\comment_ip\Form\ConfirmBanDeleteMultiple', $request);
    }

    return parent::adminPage($request, $type);
  }

}
