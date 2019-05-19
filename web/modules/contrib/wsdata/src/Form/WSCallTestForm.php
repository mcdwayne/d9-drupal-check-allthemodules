<?php

namespace Drupal\wsdata\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wsdata\WSDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class WSCallForm.
 *
 * @package Drupal\wsdata\Form
 */
class WSCallTestForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(WSDataService $wsdata) {
    $this->wsdata = $wsdata;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('wsdata')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form_state->disableCache();
    $element = $form_state->getTriggeringElement();

    $form['wscallwrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('WSCall'),
      'title' => [
        '#prefix' => '<b>' . $this->t('Title (Machine name): ') . '</b>',
        '#markup' => $this->entity->label() . ' (' . $this->entity->id() . ')',
      ],
    ];

    $wsdata  = \Drupal::service('wsdata');
    $elements = $wsdata->wscallForm(array(), $this->entity->id());

    $form['replacements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Tokens'),
    ];

    $form['replacements']['wscall_form'] = $elements;

    // Remove the wscall, we dont need it.
    unset($form['replacements']['wscall_form']['wscall']);

    if ($element and $element['#id'] == 'call') {
      $form['responsewrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Response'),
        'response' => [
          '#prefix' => '<pre>',
          '#markup' => Xss::filter($form_state->getValue('wscall_response')),
          '#suffix' => '</pre>'
        ],
      ];
    }

    return $form;
  }

  /**
   * Call the wscall.
   */
  public function call(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $form_state->disableCache();

    $replacements = [];
    foreach ($this->entity->getReplacements() as $replacement) {
      $replacements[$replacement] = $form_state->getValue($replacement);
    }

    $response = $this->wsdata->call($this->entity->id(), NULL, $replacements, $form_state->getValue('data'), array(), $form_state->getValue('returnToken'));
    $form_state->setValue('wscall_response', (is_array($response) ? print_r($response, TRUE) : $response));
  }

  /**
    * {@inheritdoc}
    */
   public function buildForm(array $form, FormStateInterface $form_state) {
     // During the initial form build, add this form object to the form state and
     // allow for initial preparation before form building and processing.
     if (!$form_state->has('entity_form_initialized')) {
       $this->init($form_state);
     }

     // Retrieve the form array using the possibly updated entity in form state.
     $form = $this->form($form, $form_state);

     // Retrieve and add the form actions array.
     $actions = $this->actionsElement($form, $form_state);
     if (!empty($actions)) {
       $form['actions'] = $actions;
     }

     return $form;
   }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Call'),
      '#id' => 'call',
      '#submit' => ['::call'],
    ];
    return $actions;
  }
}
