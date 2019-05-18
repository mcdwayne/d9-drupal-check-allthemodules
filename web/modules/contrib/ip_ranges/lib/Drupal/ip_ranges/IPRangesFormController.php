<?php

namespace Drupal\ip_ranges;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityManagerInterface;

class IPRangesFormController extends ContentEntityForm implements ContentEntityFormInterface {

  protected $ownIp;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->ownIp = \Drupal::request()->getClientIp();
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {

    $entity = $this->entity;

    // Show whitelist your own IP link only on Add IP Range form.
    if ($this->operation == 'add') {
      $form['own_ip_warning'] = array(
        '#type' => 'value',
        '#markup' =>
        t('<strong>Note that your own IP Address is currently @ip. Be careful not to lock yourself out!</strong>',
          array('@ip' => $this->ownIp))
        . '<br />'
        . l(t('Click here to whitelist your own IP-address.'), '',
            array(
              'fragment' => '',
              'external' => TRUE,
              'attributes' => array(
                'id' => 'add-my-own-ip',
                'data-my-ip' => $this->ownIp,
              ),
            )
        ),
      );
    }

    $form['ip_lower'] = array(
      '#title' => t('IP range start / Single IP-address'),
      '#type' => 'textfield',
      '#size' => 48,
      '#required' => TRUE,
      '#maxlength' => 15,
      '#description' => t('Enter IP-address (100.100.100.100). If range end is specified, it will be used as start of the range, otherwise as a single IP-address.'),
    );
    $form['ip_higher'] = array(
      '#title' => t('IP range end (optional)'),
      '#type' => 'textfield',
      '#size' => 48,
      '#required' => FALSE,
      '#maxlength' => 15,
      '#description' => t('If entered, the banned ip will be treated as a range.'),
    );

    $form['type'] = array(
      '#title' => t('List type'),
      '#description' => t('Add the IP to a whitelist to ensure access or the blacklist to deny access'),
      '#type' => 'select',
      '#options' => array(
        0 => t('Blacklist'),
        1 => t('Whitelist')),
      '#multiple' => FALSE,
      '#default_value' => 0,
      '#required' => TRUE,
    );

    $form['description'] = array(
      '#title' => t('IP Range description (optional)'),
      '#type' => 'textfield',
      '#size' => 68,
      '#required' => FALSE,
      '#maxlength' => 254,
      '#description' => t('Description can be used to better identify added IP Ranges'),
    );

    // Default values for edit form.
    if ($this->operation == 'edit') {
      $ip_lower = $entity->getIpLower();
      $ip_higher = $entity->getIpHigher();

      $form['type']['#default_value'] = $entity->getType();
      $form['ip_lower']['#default_value'] = long2ip($ip_lower);
      // Only show higher IP if its different than lower.
      if ($ip_lower != $ip_higher) {
        $form['ip_higher']['#default_value'] = long2ip($ip_higher);
      }
      $form['description']['#default_value'] = $entity->getDescription();
    }

    // Attach form javascript.
    $form['#attached']['js'] = array(
      drupal_get_path('module', 'ip_ranges') . '/js/ip_ranges_form.js',
    );

    return parent::form($form, $form_state, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    $ip_lower = trim($form_state['values']['ip_lower']);
    $ip_higher = isset($form_state['values']['ip_higher']) ? trim($form_state['values']['ip_higher']) : NULL;
    $type = $form_state['values']['type'];

    if (filter_var($ip_lower, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
      \Drupal::formBuilder()->setErrorByName('ip_lower', $form_state, t("IP(/range start) is not a valid IP address."));
    }
    elseif ($ip_higher && filter_var($ip_higher, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
      \Drupal::formBuilder()->setErrorByName('ip_higher', $form_state, t("IP range end is not a valid IP address."));
    }
    elseif ((ip2long($ip_lower) <= ip2long($this->ownIp) && ip2long($ip_higher) >= ip2long($this->ownIp)) ||
      ip2long($ip_lower) == ip2long($this->ownIp) ||
      ip2long($ip_higher) == ip2long($this->ownIp)) {
      // Own ip cannot be blacklisted, but whitelisting is ok.
      if ($type == 0) {
        \Drupal::formBuilder()->setErrorByName('', $form_state, t("You may not block your own IP address"));
      }
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $ip_lower =& $form_state['values']['ip_lower'];
    $ip_higher =& $form_state['values']['ip_higher'];

    $ip_lower = trim($ip_lower);
    $ip_higher = trim($ip_higher);

    // We're dealing with ranges so let's create an artificial range if
    // there is no specified higher bound.
    if (empty($form_state['values']['ip_higher'])) {
      $ip_higher = $ip_lower;
    }

    // Convert the IP address strings into a proper address. Use sprintf to
    // get the string representation of the unsigned IP address.
    $ip_lower = sprintf("%u", ip2long($ip_lower));
    $ip_higher = sprintf("%u", ip2long($ip_higher));

    // If the higher and lower IPs are in fact lower and higher, let's swap
    // them prior to database insertion.
    if ($ip_lower > $ip_higher) {
      $temp = $ip_lower;
      $ip_lower = $ip_higher;
      $ip_higher = $temp;
    }

    parent::submit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $entity->save();
    $form_state['redirect_route']['route_name'] = 'ip_ranges.admin_list';
  }
}
