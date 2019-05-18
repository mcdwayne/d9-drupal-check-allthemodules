<?php

namespace Drupal\eform\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\eform\Entity\EFormType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for eform type forms.
 */
class EFormTypeForm extends EntityForm {

  const VIEW_NONE = 'VIEW-NONE';
  const VIEW_DEFAULT = 'VIEW-DEFAULT';
  const DEFAULT_PROPERTY_TEXT = 'Leave this field blank to use default setting. Use &lt;none&gt; to show no text';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\eform\Entity\EFormType $type */
    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add eform type');
    }
    elseif ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %name eform type', array('%name' => $type->label()));
    }

    $eform_settings = $type->getModuleSettings('eform');
    // Ensure default settings.
    $form['name'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->name,
      '#description' => t('The human-readable name of this eform type. This text will be displayed as part of the list on the <em>Add new eform</em> page. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['type'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => array(
        // 'exists' => 'node_type_load',.
        'source' => array('name'),
      ),
      '#description' => t('A unique machine-readable name for this eform type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %eform-add page, in which underscores will be converted into hyphens.', array(
        '%eform-add' => t('Add new eform type'),
      )),
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('Describe this eform type. The text will be displayed on the <em>Add new eform type</em> page.'),
    );

    $form = $this->EFormTypeElements($form, $type, EFormTypeForm::DEFAULT_PROPERTY_TEXT);
    return $form;

  }

  /**
   * @param array $form
   * @param $type
   * @param $default_value_message
   *
   * @return array
   */
  public function EFormTypeElements(array $form, EFormType $type, $default_value_message = '') {
    // @todo Deal with default value logic and message from D7

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
    );

    $form['submission'] = array(
      '#type' => 'details',
      '#title' => $this->t('Submission form settings'),
      '#group' => 'additional_settings',
    );

    $form['submission']['form_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Form Title'),
      '#default_value' => $type->getFormTitle(),
      '#description' => t('Title For submission form.'),
    );

    $form['submission']['help'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Explanation or submission guidelines'),
      '#default_value' => $type->help,
      '#description' => t('This text will be displayed at the top of the page when creating or editing eform type of this type.'),
    );

    // ACCESS FIELDSET SETTINGS.
    $form['access'] = array(
      '#type' => 'details',
      '#title' => $this->t('Access settings'),
      // '#collapsible' => TRUE,.
      '#group' => 'additional_settings',
      '#weight' => -50,
    );
    $form['access']['form_status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Form status'),
      '#options' => array(
        EFormType::STATUS_OPEN => $this->t('Open for new submissions'),
        EFormType::STATUS_CLOSED => $this->t('Closed form new form submissions'),
      ),
      '#default_value' => empty($type->form_status) ? EFormType::STATUS_OPEN : $type->form_status,
      '#description' => t('Can users submit this form?  Open means the users can submit this form.  Closed means the user can not submit the form.'),
    );
    if ($type->isNew()) {
      $default_roles = [];
    }
    else {
      $default_roles = array_keys(user_role_names(NULL, $type->getPermission('submit')));
    };
    $form['access']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#options' => array_map('\Drupal\Component\Utility\SafeMarkup::checkPlain', user_role_names()),
      '#default_value' => $default_roles,
      '#required' => TRUE,
      '#description' => t('Please select the Role(s) that can submit this form.'),
    );

    $form['access']['resubmit_action'] = array(
      '#type' => 'select',
      '#title' => t('Resubmit action'),
      '#options' => array(
        EFormType::RESUBMIT_ACTION_NEW => t('Allow new submission'),
        EFormType::RESUBMIT_ACTION_OLD => t('Edit old submission'),
        EFormType::RESUBMIT_ACTION_DISALLOW => t("Don't allow"),
        EFormType::RESUBMIT_ACTION_CONFIRM => t('Goto Confirm page'),
      ),
      '#default_value' => $type->getResubmitAction() ? $type->getResubmitAction() : EFormType::RESUBMIT_ACTION_NEW,
      '#description' => t('Action to take if logged in user has already submitted form.'),
    );
    $disallow_text = $type->getDisallowText();
    $form['access']['disallow_text'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Disallow Text'),
      '#default_value' => empty($disallow_text['value']) ? '' : $disallow_text['value'],
      '#format' => empty($disallow_text['format']) ? NULL : $disallow_text['format'],
      '#description' => $this->t('This text will be displayed if the user has already submitted the form.') . $default_value_message,
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="resubmit_action"]' => array('value' => EFormType::RESUBMIT_ACTION_DISALLOW),
          ),
        ),
      ),
    );

    // SUBMISSION PAGE FIELDSET SETTINGS.
    $form['submission_page'] = array(
      '#type' => 'details',
      '#title' => $this->t('Submission page settings'),
      '#group' => 'additional_settings',
      '#weight' => 20,
    );
    $form['submission_page']['preview_page'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Preview Page'),
      '#default_value' => $type->preview_page,
      '#description' => t('Show a Preview page.'),
    );

    $form['submission_page']['submission_page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Submission Page Title'),
      '#default_value' => empty($type->getSubmissionPageTitle()) ? '' : $type->getSubmissionPageTitle(),
      '#description' => t('Page title for correct submission.') . $default_value_message,
    );
    $submission_text = $type->getSubmissionText();
    $form['submission_page']['submission_text'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Submission Text'),
      '#default_value' => empty($submission_text['value']) ? '' : $submission_text['value'],
      '#format' => empty($submission_text['format']) ? NULL : $submission_text['format'],
      '#description' => t('This text will be displayed to the user when a correct form is submitted.') . $default_value_message,
    );
    $show_submitted = $type->isSubmissionShowSubmitted();
    $form['submission_page']['submission_show_submitted'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show submission information'),
      '#default_value' => !empty($show_submitted),
      '#description' => t('Show submitted data on submission page?'),
    );

    // Views SETTINGS.
    $view_options = $this->getViewOptions();
    $form['submission_views'] = array(
      '#type' => 'details',
      '#title' => $this->t('Submission Views'),
      // '#collapsible' => TRUE,.
      '#group' => 'additional_settings',
      '#weight' => 30,
    );
    $form['submission_views']['admin_submissions_view'] = array(
      '#type' => 'select',
      '#title' => t('View for submissions reports'),
      '#description' => t('Select the View that should be used Submission reports.'),
      '#default_value' => $type->getAdminView(),
      '#options' => $view_options,
    );
    $user_view_description = 'Select the View that should be used to show users their previous submissions.';
    $user_view_description .= ' If "None" is selected then the users will not see a previous submissions link.';
    $form['submission_views']['user_submissions_view'] = array(
      '#type' => 'select',
      '#title' => t('View for current user\'s submissions'),
      '#description' => t($user_view_description),
      '#default_value' => $type->getUserView(),
      '#options' => $view_options,
    );

    // DRAFT SETTINGS FIELDSET SETTINGS.
    $form['draft_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Draft settings'),
      '#group' => 'additional_settings',
      '#weight' => 40,
    );
    $form['draft_settings']['draftable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Draftable'),
      '#default_value' => $type->isDraftable(),
      '#description' => $this->t('Is Draftable?'),
    );
    return $form;
  }

  /**
   * Gets the list of views.
   *
   * @param bool $include_default
   *   Include a default option.
   *
   * @return array
   *   A list of view labels keyed by the view's ID.
   */
  protected function getViewOptions($include_default = TRUE) {
    $options = [];
    if ($include_default) {
      $options[''] = '(' . $this->t('Use Default') . ')';
    }

    $view_storage = $this->entityManager->getStorage('view');

    $views = $view_storage->loadByProperties(['base_table' => 'eform_submission_field_data']);
    /** @var \Drupal\views\Entity\View $view */
    foreach ($views as $view) {
      $options[$view->id()] = $view->label();
    }
    $options[$this::VIEW_NONE] = '(' . $this->t('None') . ')';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save eform type');
    $actions['delete']['#value'] = t('Delete eform type');
    // $actions['delete']['#access'] = $this->entity->access('delete');.
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    parent::validateForm($form, $form_state);
    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var EFormType $type */
    $type = $this->entity;
    $type->type = trim($type->id());
    $type->name = trim($type->name);
    $status = $type->save();

    foreach ($form_state->getValue('roles') as $rid => $enabled) {
      user_role_change_permissions($rid, array($type->getPermission() => $enabled));
    }

    $context = array('%name' => $type->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The EForm type %name has been updated.', $context));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The EForm type %name has been added.', $context));
      $this->logger('eform')->notice('Added EForm type %name.', $context);

    }

    $form_state->setRedirectUrl($type->urlInfo('collection'));
  }

}
