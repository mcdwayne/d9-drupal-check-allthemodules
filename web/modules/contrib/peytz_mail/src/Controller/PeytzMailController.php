<?php

namespace Drupal\peytz_mail\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller class for the /subscribe route.
 */
class PeytzMailController extends ControllerBase {

  /**
   * A method which initiates the signup form.
   */
  public function subscribe() {

    $config = $this->config('peytz_mail.subscribe_page_settings');

    if (!$config->get('newsletter_lists')) {
      if (\Drupal::currentUser()->hasPermission('administer peytz_mail configuration')) {
        $link = Link::fromTextAndUrl(t('Peytz Mail settings'), Url::fromRoute('peytz_mail.settings'))->toRenderable();
        return ['#markup' => t('You need to configure @link first.', ['@link' => render($link)])];
      }
      else {
        throw new NotFoundHttpException();
      }
    }

    return \Drupal::formBuilder()->getForm('Drupal\peytz_mail\Form\PeytzMailSignUpForm');
  }

}
