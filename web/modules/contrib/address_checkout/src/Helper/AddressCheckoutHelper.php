<?php

namespace Drupal\address_checkout\Helper;

use Drupal\profile\Entity\Profile;

/**
 * Class AddressCheckoutHelper.
 *
 * @package Drupal\modules\address_checkout
 */
class AddressCheckoutHelper {

  /**
   * Keep class object.
   *
   * @var object
   */
  public static $helper = NULL;

  /**
   * Protected profiles variable.
   *
   * @var profiles
   */
  protected $profiles;

  /**
   * Protected container variable.
   *
   * @var container
   */
  protected $container;

  /**
   * EntityManager object.
   *
   * @var object
   */
  protected $entityManager;

  /**
   * Current user object.
   *
   * @var object
   */
  protected $currentUser;

  /**
   * Protected request variable.
   *
   * @var request
   */

  protected $request;
  /**
   * Current user object.
   *
   * @var object
   */
  protected $pathCurrent;

  /**
   * String Translation function.
   *
   * @var object
   */
  protected $translator;

  /**
   * Private constructor to avoid instantiation.
   */
  public function __construct($entityManager, $currentUser, $request, $pathCurrent, $string_translation) {
    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
    $this->profiles = Profile::loadMultiple();
    $this->request = $request;
    $this->pathCurrent = $pathCurrent;
    $this->translator = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxGetAddress($form_id, &$form, &$form_state) {
    foreach ($this->profiles as $key => $value) {
      if (isset($value)) {
        $profileType = $value->get('type')->getValue()[0]['target_id'];
        $profiles = $this->entityManager->getStorage('profile')->loadMultipleByUser($this->currentUser, $profileType);
      }
    }
    $profilesData = [];

    foreach ($profiles as $key => $value) {
      if ($value->get('address')) {
        $address = $value->get('address')->getValue();
        $addressFinal = t('%firstname %lastname, %address, %locality, %country',
            [
              '%firstname' => $address[0]['given_name'],
              '%lastname' => $address[0]['family_name'],
              '%address' => $address[0]['address_line1'] . ' ' . $address[0]['address_line2'],
              '%locality' => $address[0]['locality'] . ' ' . $address[0]['postal_code'] . ', ' . $address[0]['administrative_area'],
              '%country' => $address[0]['country_code'],
            ]);
$profilesData[$key] = $addressFinal;
      }
    }
    $form['#prefix'] = '<div id="modal_ajax_form">';
    $form['#suffix'] = '</div>';
    $defaultValue = $this->request->getParentRequest()->get('id', 0);
    // Check default value.
    if (!empty($defaultValue)) {
      // Load Profile.
      $profile = Profile::load($defaultValue);
      // Check profile if exists else provide default value.
      if ($profile) {
        $form['payment_information']['billing_information']['#default_value'] = $profile;
      }
      else {
        $defaultValue = 0;
      }
    }
    // Attach dialog library.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $profilesData[0] = 'New Address';
    $form['address_list'] = [
      '#type' => 'radios',
      '#title' => $this->translate('Saved Addresses'),
      '#options' => $profilesData,
      '#weight' => -100,
      '#default_value' => $defaultValue,
      '#ajax' => [
        'callback' => 'address_checkout_callback',
        'event' => 'click',
        'wrapper' => 'modal_ajax_form',
        'arguments' => [$form, $form_state],
        'progress' => [
          'type' => 'throbber',
          'message' => 'Processing',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentStep($steps, $step_id) {
    $current_index = array_key_exists($step_id, $steps);
    return $current_index == TRUE ? $step_id : NULL;
  }

  /**
   * Ajax callback function.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Ajax response
   */
  public function addressCheckoutCallback(array &$form, FormStateInterface &$form_state) {
    $current_path = $this->pathCurrent;
    $id = $form_state->getValue('address_list');
    $option = [];
    if (!empty($id)) {
      $option = [
        'query' => ['id' => $id],
      ];
    }
    $url = Url::fromUri('internal:' . $current_path, $option);
    $response = new AjaxResponse();
    $message = $this->translate('updating address...');
    // Adding modal window.
    $options = [
      'width' => 250,
      'height' => 300,
    ];
    $title = $this->translate('Address Updation.');
    $response->addCommand(new OpenModalDialogCommand($title, $message, $options));
    $response->addCommand(new RedirectCommand($url->toString()));
    return $response;
  }

  /**
   * Get current path.
   *
   * @return string
   *   Return current path.
   */
  public function getCurrentPath() {
    return $this->pathCurrent->getPath();
  }

  /**
   * Translate string.
   *
   * @param string $string
   *   Translate string.
   *
   * @return string
   *   Return translated string.
   */
  public function translate($string) {
    return $this->translator->translate($string);
  }

}
