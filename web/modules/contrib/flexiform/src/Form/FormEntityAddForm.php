<?php

namespace Drupal\flexiform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\SetDialogTitleCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;
use Drupal\flexiform\MultipleEntityFormState;

/**
 * Provides a form for adding new entity forms.
 */
class FormEntityAddForm extends FormEntityBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexiform_form_entity_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FlexiformEntityFormDisplayInterface $form_display = NULL) {
    $form_state = MultipleEntityFormState::createForForm($form, $form_state);
    parent::buildForm($form, $form_state, $form_display);

    $available_plugins = $this->pluginManager->getDefinitionsForContexts($this->formEntityManager($form_state)->getContexts());

    // Add prefix and suffix for ajax purposes.
    $form['#prefix'] = '<div id="flexiform-form-entity-add-wrapper">';
    $form['#suffix'] = '</div>';

    if ($plugin_id = $form_state->get('selected_form_entity')) {
      $plugin = $this->pluginManager->createInstance($plugin_id, ['manager' => $this->formEntityManager($form_state)]);
      return $this->buildConfigurationForm($form, $form_state, $plugin);
    }
    else {
      // Prepare selector form.
      $plugin_options = [];
      foreach ($available_plugins as $plugin_id => $plugin_definition) {
        if (empty($plugin_definition['no_ui'])) {
          $plugin_options[$plugin_id] = $plugin_definition['label'];
        }
      }
      $form['form_entity'] = [
        '#type' => 'select',
        '#required' => TRUE,
        '#options' => $plugin_options,
        '#title' => $this->t('Form Entity'),
      ];

      $form['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Continue'),
          '#submit' => [
            [$this, 'submitSelectPlugin'],
          ],
          '#ajax' => [
            'callback' => [$this, 'ajaxSubmit'],
            'event' => 'click',
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Submit the plugin selection.
   */
  public function submitSelectPlugin(array $form, FormStateInterface $form_state) {
    $form_state->set('selected_form_entity', $form_state->getValue('form_entity'));
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->get('selected_form_entity')) {
      $response->addCommand(new ReplaceCommand('#flexiform-form-entity-add-wrapper', $form));
      $response->addCommand(new SetDialogTitleCommand(NULL, $this->t('Configure form entity')));
    }
    else {
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->set('selected_form_entity', FALSE);
  }

}
