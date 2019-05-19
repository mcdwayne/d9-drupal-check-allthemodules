<?php

namespace Drupal\ulogin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ulogin\UloginHelper;
use Drupal\user\Entity\User;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * User Identity form.
 */
class UserIdentity extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ulogin_user_identity';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = 0) {
    $account = User::load($uid);
    if ($route = \Drupal::request()->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', $account->getDisplayName());
    }

    $identities = UloginHelper::identityLoadByUid($uid);
    $providers = UloginHelper::providersList();

    $header = [t('Authentication provider'), t('Identity'), t('Delete')];
    $rows = [];
    $data_array = [];
    foreach ($identities as $identity) {
      $data = unserialize($identity['data']);
      $data_array[] = $data;
      $rows[] = [
        $providers[$data['network']],
        Link::fromTextAndUrl($data['identity'], Url::fromUri($data['identity'], [
          'attributes' => ['target' => '_blank'],
          'external' => TRUE
        ])),
        Link::createFromRoute(t('Delete'), 'ulogin.user_delete', [
          'uid' => $uid,
          'id' => $identity['id']
        ]),
      ];
    }

    $form = [];

    $form['identity'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('You don\'t have any identities yet.')
    ];

    // Add more identities.
    if (\Drupal::currentUser()->hasPermission('use ulogin')) {
      $form['ulogin_widget'] = [
        '#type' => 'ulogin_widget',
        '#title' => t('Add more identities'),
        '#weight' => 10,
        '#ulogin_destination' => '',
      ];
    }

    // Tokens browser for admins.
    if (
      \Drupal::currentUser()->hasPermission('administer site configuration')
      || \Drupal::currentUser()->hasPermission('administer users')
    ) {
      $form['vtabs'] = [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-fset-user-tokens',
        '#weight' => 20,
      ];

      $header = [t('Token'), t('Value')];
      // User tokens.
      $ulogin = \Drupal::service('user.data')->get('ulogin', $uid);
      if (!empty($ulogin)) {
        $form['fset_user_tokens'] = [
          '#type' => 'details',
          '#title' => t('User tokens'),
          '#group' => 'vtabs'
        ];

        $rows = [];
        foreach ($ulogin as $key => $value) {
          if (!in_array($key, ['manual', 'ulogin'])) {
            $rows[] = ['[user:ulogin:' . $key . ']', $value];
          }
        }
        $form['fset_user_tokens']['tokens'] = [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }

      // Data from auth providers.
      foreach ($data_array as $data) {
        $form['fset_' . $data['network'] . '_' . $data['uid']] = [
          '#type' => 'details',
          '#title' => $providers[$data['network']],
          '#group' => 'vtabs'
        ];

        $rows = [];
        foreach ($data as $key => $value) {
          $rows[] = [$key, $value];
        }
        $form['fset_' . $data['network'] . '_' . $data['uid']]['tokens'] = [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }
    }

    return $form;
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
  }

}
