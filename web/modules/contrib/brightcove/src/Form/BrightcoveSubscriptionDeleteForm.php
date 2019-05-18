<?php

namespace Drupal\brightcove\Form;

use Brightcove\API\Exception\APIException;
use Drupal\brightcove\Entity\BrightcoveSubscription;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the form for Brightcove Subscription delete.
 */
class BrightcoveSubscriptionDeleteForm extends ConfirmFormBase {

  /**
   * Brightcove Subscription object.
   *
   * @var \Drupal\brightcove\Entity\BrightcoveSubscription
   */
  protected $brightcoveSubscription;

  /**
   * BrightcoveSubscriptionDeleteForm constructor.
   */
  public function __construct() {
    $request = $this->getRequest();
    $this->brightcoveSubscription = BrightcoveSubscription::load($request->get('id'));
    if (empty($this->brightcoveSubscription)) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brightcove_subscription_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure that you want to delete the subscription with the %endpoint endpoint?', [
      '%endpoint' => $this->brightcoveSubscription->getEndpoint(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.brightcove_subscription.list');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prevent deletion of the default Subscription entity.
    if (!empty($this->brightcoveSubscription) && $this->brightcoveSubscription->isDefault()) {
      drupal_set_message($this->t('The API client default Subscription cannot be deleted.'), 'error');
      return $this->redirect('entity.brightcove_subscription.list');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->brightcoveSubscription->delete(FALSE);
    }
    catch (APIException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    drupal_set_message($this->t('Subscription has been successfully deleted.'));
    $form_state->setRedirect('entity.brightcove_subscription.list');
  }

}
