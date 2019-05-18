<?php

namespace Drupal\enquirycart\Form;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with button.
 */
class EnquirycartButtonForm extends FormBase {

  private $config;

  /**
   * Constructor for enquirycart config.
   */
  public function __construct() {
    $this->config = $this->config('enquirycart.settings');

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enquirycart_button_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $node = $this->getRouteMatch()->getParameter('node');

    // Check if the current page is a node and display the button.
    // we don't want to give errors for the ones that cannot be accessed.
    if (!empty($node)) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->config->get('buttonTitle'),
        '#attributes' => ['class' => ['buttonnew btn-primary pull-right']]  ,
      ];

      return $form;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $node = $this->getRouteMatch()->getParameter('node');
    $request = $this->getRequest();

    if ($node != NULL) {
      $nodetitle = $node->getTitle();

      $this->managesession($request, $nodetitle);

      $message = $this->t("'@prodtitle' has been added to the @pagetitle", ['@prodtitle' => $nodetitle, '@pagetitle' => $this->config->get('title')]);
      drupal_set_message($message);

      $form_state->setRedirect('enquirycart.getEnquiryBasket');

    }
    else {
      $message = $this->t('Sorry this cannot be added to the basket');
      drupal_set_message($message, 'error');
    }

  }

  /**
   * Manage the session of the cart.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request service for session.
   * @param string $nodetitle
   *   Title of the node.
   */
  private function managesession(Request $request, $nodetitle) {
    $session = $request->getSession();

    $value = $session->get('enquire');

    if ($value == NULL && $nodetitle != NULL) {

      $temp = [$nodetitle];
      $session->set('enquire', $temp);

    }
    else {

      if (!in_array($nodetitle, $value)) {

        $value[] = $nodetitle;
        $session->set('enquire', $value);

      }

    }

  }

}
