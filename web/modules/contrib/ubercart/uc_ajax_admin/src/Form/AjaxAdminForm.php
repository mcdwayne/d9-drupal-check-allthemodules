<?php

namespace Drupal\uc_ajax_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_cart\Plugin\CheckoutPaneManager;
use Drupal\uc_store\AjaxAttachTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Configures Ajax behaviours on the Ubercart checkout page.
 */
class AjaxAdminForm extends FormBase {
  use AjaxAttachTrait;

  /**
   * The checkout pane manager.
   *
   * @var \Drupal\uc_cart\Plugin\CheckoutPaneManager
   */
  protected $checkoutPaneManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_ajax_admin_form';
  }

  /**
   * Form constructor.
   *
   * @param \Drupal\uc_cart\Plugin\CheckoutPaneManager $checkout_pane_manager
   *   The checkout pane manager.
   */
  public function __construct(CheckoutPaneManager $checkout_pane_manager) {
    $this->checkoutPaneManager = $checkout_pane_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_cart.checkout_pane')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param string $target_form
   *   The form for which ajax behaviors are to be administered. Currently only
   *   'checkout' is supported.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $target_form = 'checkout') {
    switch ($target_form) {
      case 'checkout':
        $triggers = _uc_ajax_admin_checkout_trigger_options(_uc_ajax_admin_build_checkout_form());
        $panes = $this->checkoutPaneManager->getDefinitions();
        $wrappers = [];
        foreach ($panes as $id => $pane) {
          $wrappers["$id-pane"] = $pane['title'];
        }
        break;

      default:
        throw new NotFoundHttpException();
    }
    $form['#uc_ajax_target'] = $target_form;
    $form['#uc_ajax_config'] = $this->config('uc_cart.settings')->get('ajax.' . $target_form) ?: [];

    $form['table'] = uc_ajax_admin_table($triggers, $wrappers, $form['#uc_ajax_config']);
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $form['#uc_ajax_config'];
    foreach ($form_state->getValue('table') as $index => $entry) {
      $key = $entry['key'];
      if ($index === '_new') {
        if (!empty($key) && !empty($entry['panes'])) {
          $config[$key] = $entry['panes'];
        }
      }
      elseif ($entry['remove'] || empty($entry['panes'])) {
        unset($config[$key]);
      }
      else {
        $config[$key] = $entry['panes'];
      }
    }
    $this->configFactory()->getEditable('uc_cart.settings')
      ->set('ajax.' . $form['#uc_ajax_target'], $config)
      ->save();
    $this->messenger()->addMessage($this->t('Your changes have been saved.'));
  }

}
