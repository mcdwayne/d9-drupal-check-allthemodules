<?php

namespace Drupal\eform\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Url;

/**
 * Defines the EForm type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "eform_type",
 *   label = @Translation("EForm type"),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\eform\Form\EFormTypeForm",
 *       "edit" = "Drupal\eform\Form\EFormTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\eform\EFormTypeListBuilder",
 *   },
 *   admin_permission = "administer eform types",
 *   config_prefix = "type",
 *   bundle_of = "eform_submission",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name",
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/eform_types/manage/{eform_type}/delete",
 *     "edit-form" = "/admin/structure/eform_types/manage/{eform_type}",
 *     "collection" = "/admin/structure/eform_types",
 *   }
 * )
 */
class EFormType extends ConfigEntityBundleBase {

  /**
   * Closed status for a form.
   */
  const STATUS_CLOSED = 'EFORM_CLOSED';
  /**
   * Open status for a form.
   */
  const STATUS_OPEN = 'EFORM_OPEN';

  const RESUBMIT_ACTION_OLD = 'EFORM_RESUBMIT_OLD';
  const RESUBMIT_ACTION_NEW = 'EFORM_RESUBMIT_NEW';
  const RESUBMIT_ACTION_DISALLOW = 'EFORM_RESUBMIT_DISALLOW';
  // @todo Should action be changed from routing to confirm page to routing to user given url defaulting to confirm page?
  const RESUBMIT_ACTION_CONFIRM = 'EFORM_RESUBMIT_CONFIRM';
  /**
   * The machine name of this eform type.
   *
   * @var string
   *
   * @todo Rename to $id.
   */
  public $type;

  public $preview_page = 0;

  /**
   * The human-readable name of the eform type.
   *
   * @var string
   *
   * @todo Rename to $label.
   */
  public $name;

  /**
   * The title to use for the form.
   *
   * @var string
   */
  protected $form_title;

  /**
   * View to use admin submission view.
   *
   * @var string
   */
  protected $admin_submissions_view;

  /**
   * View to use user submission view.
   *
   * @var string
   */
  protected $user_submissions_view;

  /**
   * @return string
   */
  public function getUserView() {
    return $this->user_submissions_view;
  }

  /**
   * @param string $user_submissions_view
   */
  public function setUserView($user_submissions_view) {
    $this->user_submissions_view = $user_submissions_view;
  }

  /**
   * @return string
   */
  public function getAdminView() {
    return $this->admin_submissions_view;
  }

  /**
   * @param string $admin_submissions_view
   */
  public function setAdminView($admin_submissions_view) {
    $this->admin_submissions_view = $admin_submissions_view;
  }

  /**
   * @return string
   */
  public function getFormTitle() {
    return $this->form_title;
  }

  /**
   * @param string $form_title
   */
  public function setFormTitle($form_title) {
    $this->form_title = $form_title;
  }

  /**
   * A brief description of this eform type.
   *
   * @var string
   */
  protected $description;

  /**
   * @var boolean
   */
  protected $submission_show_submitted = FALSE;

  /**
   * @var array
   */
  protected $submission_text;

  /**
   * @var string
   */
  protected $submission_page_title;

  /**
   * @return string
   */
  public function getSubmissionPageTitle() {
    return $this->submission_page_title;
  }

  /**
   * @param string $submission_page_title
   */
  public function setSubmissionPageTitle($submission_page_title) {
    $this->submission_page_title = $submission_page_title;
  }

  /**
   * @return array
   */
  public function getSubmissionText() {
    return $this->submission_text;
  }

  /**
   * @return boolean
   */
  public function isSubmissionShowSubmitted() {
    return $this->submission_show_submitted;
  }

  /**
   * Load default values for any text or formatted text properties that are empty.
   *
   * @return $this
   */
  public function loadDefaults() {
    $config = $this->getConfigManager()->getConfigFactory()->get('eform.type_defaults');

    $default_values = $config->getRawData();
    foreach ($default_values as $key => $value) {
      $this->overrideWithDefaut($key, $value);
    }
    return $this;
  }

  /**
   * Override a property with a default value if empty.
   *
   * Only overrides string properties or empty formatted text properties.
   *
   * @param $key
   * @param $default_value
   */
  protected function overrideWithDefaut($key, $default_value) {
    $override = FALSE;
    if (!isset($this->{$key})) {
      $override = TRUE;
    }
    $value = $this->{$key};
    if (is_string($value)) {
      if (empty($value)) {
        $override = TRUE;
      }
      elseif ($value == '<none>') {
        $this->{$key} = '';
        return;
      }
    }
    elseif (is_array($value) && isset($value['value']) && isset($value['format'])) {
      if (empty($value['value'])) {
        $override = TRUE;
      }
      elseif ($value['value'] == '<none>') {
        $this->{$key} = '';
        return;
      }
    }
    if ($override) {
      $this->{$key} = $default_value;
    }
  }

  /**
   * @return boolean
   */
  public function isDraftable() {
    return $this->draftable;
  }

  /**
   * @var boolean;
   */
  protected $draftable;

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Help information shown to the user when creating a EForm of this type.
   *
   * @var string
   */
  public $help;

  /**
   * Current status of the form. Currently only closed or open.
   *
   * @var string;
   */
  public $form_status;

  /**
   * Roles @todo text.
   *
   * @var array;
   */
  public $roles;

  /**
   * @var string;
   */
  protected $resubmit_action;

  /**
   * @var array;
   */
  protected $disallow_text;

  /**
   * @return array
   */
  public function getDisallowText() {
    // @todo Should there be a default disallow text?
    return $this->disallow_text;
  }

  /**
   * @param string $resubmit_action
   */
  public function setResubmitAction($resubmit_action) {
    $this->resubmit_action = $resubmit_action;
  }

  /**
   * @return string
   */
  public function getResubmitAction() {
    return $this->resubmit_action;
  }

  /**
   * Module-specific settings for this eform type, keyed by module name.
   *
   * @var array
   *
   * @todo Pluginify.
   */
  public $settings = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleSettings($module) {
    if (isset($this->settings[$module]) && is_array($this->settings[$module])) {
      return $this->settings[$module];
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('eform.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * Get submit link.
   *
   * @return \Drupal\Core\GeneratedLink|string
   */
  public function getSubmitLink() {
    $url = Url::fromRoute('entity.eform_submission.submit_page', array('eform_type' => $this->type));
    // @todo should the submit label be distinct from regular label?
    return \Drupal::l($this->label(), $url);
  }

  /**
   * Get the permission string for an operation for this EForm Type.
   *
   * @param string $op
   *
   * @return string
   *
   * @throws \Exception
   */
  public function getPermission($op = 'submit') {
    switch ($op) {
      case 'submit':
        return 'submit' . $this->id() . ' eform';
      case 'edit own':
      case 'delete own':
        return $op . $this->id() . ' submissions';
      default:
        throw new \Exception('Unknown operation ' . $op . ' for Eform Type');
    }
  }

  /**
   * {@inheritdoc}
   *
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    if (!$update) {
      // Clear the eform type cache, so the new type appears.
      \Drupal::cache()->deleteTags(array('eform_types' => TRUE));

      entity_invoke_bundle_hook('create', 'eform', $this->id());

      // Unless disabled, automatically create a Body field for new eform types.
      if ($this->get('create_body')) {
        $label = $this->get('create_body_label');
        eform_add_body_field($this, $label);
      }
    }
    elseif ($this->getOriginalID() != $this->id()) {
      // Clear the eform type cache to reflect the rename.
      \Drupal::cache()->deleteTags(array('eform_types' => TRUE));

      $update_count = eform_type_update_eforms($this->getOriginalID(), $this->id());
      if ($update_count) {
        drupal_set_message(format_plural($update_count,
          'Changed the eform type of 1 post from %old-type to %type.',
          'Changed the eform type of @count posts from %old-type to %type.',
          array(
            '%old-type' => $this->getOriginalID(),
            '%type' => $this->id(),
          )));
      }
      entity_invoke_bundle_hook('rename', 'eform', $this->getOriginalID(), $this->id());
    }
    else {
      // Invalidate the cache tag of the updated eform type only.
      cache()->invalidateTags(array('eform_type' => $this->id()));
    }
  }

  **
   * {@inheritdoc}
   *
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    // Clear the eform type cache to reflect the removal.
    $storage_controller->resetCache(array_keys($entities));
    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'eform', $entity->id());
    }
  }
*/
}
