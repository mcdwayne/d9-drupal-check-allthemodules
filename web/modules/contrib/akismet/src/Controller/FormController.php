<?php

namespace Drupal\akismet\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\akismet\Element\Akismet;
use Drupal\akismet\Storage\ResponseDataStorage;
use Drupal\akismet\Utility\Logger;
use Drupal\akismet\Utility\AkismetUtilities;
use Drupal\Core\Form\FormState;
use Drupal\user\Entity\User;

/**
 * Controller with functions for managing protected forms.
 */
class FormController extends ControllerBase {

  const MESSAGE_CAPTCHA = 'To complete this form, please complete the word verification.';

  /**
   * Returns information about a form registered via hook_akismet_form_info().
   *
   * @param $form_id
   *   The form id to return information for.
   * @param $module
   *   The module name $form_id belongs to.
   * @param array $form_list
   *   (optional) The return value of hook_akismet_form_list() of $module, if
   *   already kown. Primarily used by akismet_form_load().
   */
  public static function getProtectedFormDetails($form_id, $module, $form_list = NULL) {
    // Default properties.
    $form_info = array(
      // Base properties.
      'form_id' => $form_id,
      'title' => $form_id,
      'module' => $module,
      'entity' => NULL,
      'bundle' => NULL,
      // Configuration properties.
      'mode' => NULL,
      'checks' => array(),
      'enabled_fields' => array(),
      'strictness' => 'normal',
      'unsure' => 'moderate',
      'discard' => 1,
      'moderation' => 0,
      // Meta information.
      'bypass access' => array(),
      'elements' => array(),
      'mapping' => array(),
      'mail ids' => array(),
      'orphan' => TRUE,
    );

    // Fetch the basic form information from hook_akismet_form_list() first.
    // This makes the integrating module (needlessly) rebuild all of its available
    // forms, but the base properties are absolutely required here, so we can
    // apply the default properties below.
    if (!isset($form_list)) {
      $form_list = \Drupal::moduleHandler()
        ->invoke($module, 'akismet_form_list');
    }
    // If it is not listed, then the form has vanished.
    if (!isset($form_list[$form_id])) {
      return $form_info;
    }
    $module_form_info = \Drupal::moduleHandler()
      ->invoke($module, 'akismet_form_info', array($form_id));
    // If no form info exists, then the form has vanished.
    if (!isset($module_form_info)) {
      return $form_info;
    }
    unset($form_info['orphan']);

    // Any information in hook_akismet_form_info() overrides the list info.
    $form_info = array_merge($form_info, $form_list[$form_id]);
    $form_info = array_merge($form_info, $module_form_info);

    // Allow modules to alter the default form information.
    \Drupal::moduleHandler()->alter('akismet_form_info', $form_info, $form_id);

    return $form_info;
  }

  /**
   * Given an array of values and an array of fields, extract data for use.
   *
   * This function generates the data to send for validation to Akismet by walking
   * through the submitted form values and
   * - copying element values as specified via 'mapping' in
   *   hook_akismet_form_info()
   *   into the dedicated data properties
   * - collecting and concatenating all fields that have been selected for textual
   *   analysis into the 'post_body' property
   *
   * The processing accounts for the following possibilities:
   * - A field was selected for textual analysis, but there is no submitted form
   *   value. The value should have been appended to the 'post_body' property, but
   *   will be skipped.
   * - A field is contained in the 'mapping' and there is a submitted form value.
   *   The value will not be appended to the 'post_body', but instead be assigned
   *   to the specified data property.
   * - All fields specified in 'mapping', for which there is a submitted value,
   *   but which were NOT selected for textual analysis, are assigned to the
   *   specified data property. This is usually the case for form elements that
   *   hold system user information.
   *
   * @param FormState $form_state
   *   Contains both the submitted form values and a list of button form
   *   elements.
   * @param $fields
   *   A list of strings representing form elements to extract. Nested fields are
   *   in the form of 'parent][child'.
   * @param $mapping
   *   An associative array of form elements to map to Akismet's dedicated data
   *   properties. See hook_akismet_form_info() for details.
   *
   * @return array
   *   An associative array of keys and values suitable for
   *   Akismet::checkContent().
   *
   * @see hook_akismet_form_info()
   */
  public static function extractAkismetValues(FormState $form_state, $fields, $mapping) {
    $user = \Drupal::currentUser();

    // All elements specified in $mapping must be excluded from $fields, as they
    // are used for dedicated $data properties instead. To reduce the parsing code
    // size, we are turning a given $mapping of e.g.
    //   array('comment_author' => 'author_form_element')
    // into
    //   array('author_form_element' => 'comment_author')
    // and we reset $mapping afterwards.
    // When iterating over the $fields, this allows us to quickly test whether the
    // current field should be excluded, and if it should, we directly get the
    // mapped property name to rebuild $mapping with the field values.
    $exclude_fields = array();
    if (!empty($mapping)) {
      $exclude_fields = array_flip($mapping);
    }
    $mapping = array();

    // Process all fields that have been selected for text analysis.
    $post_body = array();
    foreach ($fields as $field) {
      // Nested elements use a key of 'parent][child', so we need to recurse.
      $parents = explode('][', $field);
      $value = $form_state->getValue($parents);
      // If this field was contained in $mapping and should be excluded, add it to
      // $mapping with the actual form element value, and continue to the next
      // field. Also unset this field from $exclude_fields, so we can process the
      // remaining mappings below.
      if (isset($exclude_fields[$field])) {
        if (is_array($value)) {
          $value = implode(' ', AkismetUtilities::flattenFormValue($value));
        }
        $mapping[$exclude_fields[$field]] = $value;
        unset($exclude_fields[$field]);
        continue;
      }
      // Only add form element values that are not empty.
      if (isset($value)) {
        // UTF-8 validation happens later.
        if (is_string($value) && strlen($value)) {
          $post_body[$field] = $value;
        }
        // Recurse into nested values (e.g. multiple value fields).
        elseif (is_array($value) && !empty($value)) {
          // Ensure we have a flat, indexed array to implode(); form values of
          // field_attach_form() use several subkeys.
          $value = AkismetUtilities::flattenFormValue($value);
          $post_body = array_merge($post_body, $value);
        }
      }
    }
    $post_body = implode("\n", $post_body);

    // Try to assign any further form values by processing the remaining mappings,
    // which have been turned into $exclude_fields above. All fields that were
    // already used for 'post_body' no longer exist in $exclude_fields.
    foreach ($exclude_fields as $field => $property) {
      // If the postTitle field was not included in the enabled fields, then don't
      // set it's mapping here.
      if ($property === 'post_title' && !in_array($field, $fields)) {
        continue;
      }
      // Nested elements use a key of 'parent][child', so we need to recurse.
      $parents = explode('][', $field);
      $value = $form_state->getValue($parents);
      if (isset($value)) {
        if (is_array($value)) {
          $value = AkismetUtilities::flattenFormValue($value);
          $value = implode(' ', $value);
        }
        $mapping[$property] = $value;
      }
    }

    // Build the data structure expected by the Akismet API.
    $data = array();
    // Post id; not sent to Akismet.
    // @see submitForm()
    if (!empty($mapping['post_id'])) {
      $data['post_id'] = $mapping['post_id'];
    }
    // Post body.
    if (!empty($post_body)) {
      $data['comment_content'] = $post_body;
    }

    // Author ID.
    // If a non-anonymous user ID was mapped via form values, use that.
    if (!empty($mapping['comment_author_id'])) {
      $author_id = $mapping['comment_author_id'];
    }
    // Otherwise, the currently logged-in user is the author.
    elseif (!empty($user->id())) {
      $author_id = $user->id();
    }

    // Load the user account of the author, if any, for the following author*
    // property assignments.
    if (isset($author_id)) {
      /** @var \Drupal\user\Entity\User $account */
      $account = User::load($author_id);
      $author_username = $account->getUsername();
      $author_email = $account->getEmail();

      // In case a post of a registered user is edited and a form value mapping
      // exists for author_id, but no form value mapping exists for author_name,
      // use the name of the user account associated with author_id.
      // $account may be the same as the currently logged-in $user at this point.
      if (!empty($author_username)) {
        $data['comment_author'] = $author_username;
      }

      if (!empty($author_email)) {
        $data['comment_author_email'] = $author_email;
      }
    }

    // Author name.
    // A form value mapping always has precedence.
    if (!empty($mapping['comment_author'])) {
      $data['comment_author'] = $mapping['comment_author'];
    }

    // Author e-mail.
    if (!empty($mapping['comment_author_email'])) {
      $data['comment_author_email'] = $mapping['comment_author_email'];
    }

    // Author homepage.
    if (!empty($mapping['comment_author_url'])) {
      $data['comment_author_url'] = $mapping['comment_author_url'];
    }

    // Author IP.
    $data['user_ip'] = \Drupal::request()->getClientIp();

    $akismet_form = $form_state->getValue('akismet');

    // Add the contextCreated parameter if a callback exists.
    if (isset($akismet_form['context created callback']) && function_exists($akismet_form['context created callback'])) {
      if (!empty($mapping['context_id'])) {
        $contextCreated = call_user_func($akismet_form['context created callback'], $mapping['context_id']);
        if ($contextCreated !== FALSE) {
          $data['comment_post_modified_gmt'] = $contextCreated;
        }
      }
    }

    // Ensure that all $data values contain valid UTF-8. Invalid UTF-8 would be
    // sanitized into an empty string, so the Akismet backend would not receive
    // any value.
    $invalid_utf8 = FALSE;
    foreach ($data as $key => $value) {
      if (!Unicode::validateUtf8($value)) {
        $invalid_utf8 = TRUE;
        // Replace the bogus string, since $data will be logged as
        // check_plain(var_export($data)), and check_plain() would empty the
        // entire exported variable string otherwise.
        $data[$key] = '- Invalid UTF-8 -';
      }
    }
    if ($invalid_utf8) {
      $form_state->setErrorByName('', t('Your submission contains invalid characters and will not be accepted.'));

      Logger::addMessage([
        'message' => 'Invalid @type in form values',
        'arguments' => ['@type' => $invalid_utf8 ? 'UTF-8' : 'XML characters'],
        'data' => $data
      ]);
      $data = FALSE;
    }

    return $data;
  }

  /**
   * Returns a list of protectable forms registered via hook_akismet_form_info().
   */
  public static function getProtectableForms() {
    $form_list = array();
    foreach (\Drupal::moduleHandler()
               ->getImplementations('akismet_form_list') as $module) {
      $function = $module . '_akismet_form_list';
      $module_forms = $function();
      foreach ($module_forms as $form_id => $info) {
        $form_list[$form_id] = $info;
        $form_list[$form_id] += array(
          'form_id' => $form_id,
          'module' => $module,
        );
      }
    }

    // Allow modules to alter the form list.
    \Drupal::moduleHandler()->alter('akismet_form_list', $form_list);

    return $form_list;
  }

  /**
   * Returns a cached mapping of protected and delete confirmation form ids.
   *
   * @param $reset
   *   (optional) Boolean whether to reset the static cache, flush the database
   *   cache, and return nothing (TRUE). Defaults to FALSE.
   *
   * @return
   *   An associative array containing:
   *   - protected: An associative array whose keys are protected form IDs and
   *     whose values are the corresponding module names the form belongs to.
   *   - delete: An associative array whose keys are 'delete form' ids and whose
   *     values are protected form ids; e.g.
   * @code
   *     array(
   *       'node_delete_confirm' => 'article_node_form',
   *     )
   * @endcode
   *     A single delete confirmation form id can map to multiple registered
   *     $form_ids, but only the first is taken into account. As in above example,
   *     we assume that all 'TYPE_node_form' definitions belong to the same entity
   *     and therefore have an identical 'post_id' mapping.
   */
  public static function getProtectedForms($reset = FALSE) {
    $forms = &drupal_static(__FUNCTION__);

    if ($reset) {
      unset($forms);
      return true;
    }

    if (isset($forms)) {
      return $forms;
    }

    // Get all forms that are protected
    $protected_forms = \Drupal\akismet\Entity\Form::loadMultiple();
    foreach ($protected_forms as $form_id => $info) {
      $forms['protected'][$form_id] = $info->get('module');
    }

    // Build a list of delete confirmation forms of entities integrating with
    // Akismet, so we are able to alter the delete confirmation form to display
    // our feedback options.
    $forms['delete'] = array();
    foreach (self::getProtectableForms() as $form_id => $info) {
      if (!isset($info['delete form']) || !isset($info['entity'])) {
        continue;
      }
      // We expect that the same delete confirmation form uses the same form
      // element mapping, so multiple 'delete form' definitions are only processed
      // once. Additionally, we only care about protected forms.
      if (!isset($forms['delete'][$info['delete form']]) && isset($forms['protected'][$form_id])) {
        // A delete confirmation form integration requires a 'post_id' mapping.
        $form_info = self::getProtectedFormDetails($form_id, $info['module']);
        if (isset($form_info['mapping']['post_id'])) {
          $forms['delete'][$info['delete form']] = $form_id;
        }
      }
    }
    return $forms;
  }

  /**
   * Helper function to add field form element mappings for fieldable entities.
   *
   * May be used by hook_akismet_form_info() implementations to automatically
   * populate the 'elements' definition with attached text fields on the entity
   * type's bundle.
   *
   * @param array $form_info
   *   The basic information about the registered form. Taken by reference.
   * @param string $entity_type
   *   The entity type; e.g., 'node'.
   * @param string $bundle
   *   The entity bundle name; e.g., 'article'.
   *
   * @return void
   *   $form_info is taken by reference and enhanced with any attached field
   *   mappings; e.g.:
   * @code
   *     $form_info['elements']['field_name][und][0][value'] = 'Field label';
   * @endcode
   */
  public static function addProtectableFields(&$form_info, $entity_type, $bundle) {
    if (!$entity_info = \Drupal::entityManager()->getDefinition($entity_type)) {
      return;
    }
    $form_info['mapping']['post_id'] = $entity_info->getKeys()['id'];
    $title = isset($form_info['mapping']['post_title']) ? $form_info['mapping']['post_title'] : '';
    $title_parts = explode('][', $title);
    $base_title = reset($title_parts);

    // @var $field_definitions \Drupal\Core\Field\FieldDefinitionInterface[]
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
    /* @var $field \Drupal\Core\Field\FieldDefinitionInterface */
    foreach ($field_definitions as $field_name => $field) {
      if ($field_name !== $base_title &&
        !$field->isReadOnly() &&
        !$field->isComputed() &&
        in_array($field->getType(), [
        'string',
        'email',
        'uri',
        'label',
        'plural_label',
        'mail',
        'string_long',
        'text_with_summary',
      ])) {
        $form_info['elements'][$field_name] = $field->getLabel();
      }
    }
  }

  /**
   * Form validation handler to perform textual analysis on submitted form values.
   */
  public static function validateAnalysis(&$form, FormState $form_state) {
    // TODO: Determine whether the @var declaration belongs to $akismet.
    /** @var \Drupal\akismet\Entity\Form $akismet */
    $akismet = $form_state->getValue('akismet');

    // Perform textual analysis.
    $all_data = self::extractAkismetValues($form_state->cleanValues(), $akismet['enabled_fields'], $akismet['mapping']);
    // Cancel processing upon invalid UTF-8 data.
    if ($all_data === FALSE) {
      return FALSE;
    }
    $data = $all_data;
    // Remove post_id property; only used by submitForm().
    if (isset($data['post_id'])) {
      unset($data['post_id']);
    }
    $contentId = isset($akismet['contentId']) ? $akismet['contentId'] : NULL;
    if (!empty($contentId)) {
      $data['id'] = $contentId;
    }
    if (isset($akismet['type'])) {
      $data['type'] = $akismet['type'];
    }
    $data['server'] = \Drupal\akismet\Client\Client::prepareServerVars($_SERVER);

    // Allow modules to alter data sent.
    \Drupal::moduleHandler()->alter('akismet_content', $data);

    /** @var \Drupal\akismet\Client\DrupalClient $akismet */
    $akismet_service = \Drupal::service('akismet.client');
    $response = $akismet_service->checkContent($data);

    // Use all available data properties for log messages below.
    $data += $all_data;

    // Trigger global fallback behavior if there is a unexpected result.
    if (is_int($response)) {
      return AkismetUtilities::handleFallback();
    }

    // Store both the request and the response returned by Akismet.
    $form_state->setValue('akismet_request', $data);
    $form_state->setValue('akismet_response', $response);

    // Prepare watchdog message teaser text.
    $teaser = '--';
    if (isset($data['comment_content'])) {
      $teaser = Unicode::truncate(strip_tags($data['comment_content']), 40);
    }

    // Handle the spam check result.
    switch ($response['classification']) {
      case 'ham':
        $message = SafeMarkup::format('Ham: %teaser', array('%teaser' => $teaser));
        \Drupal::logger('akismet')->notice($message);
        break;

      case 'spam':
        if ($akismet['discard']) {
          $form_state->setError($form, t('Your submission has triggered the spam filter and will not be accepted.'));
        }
        else {
          $form_state->setValue(array('akismet', 'require_moderation'), TRUE);
        }
        $message = SafeMarkup::format('Spam: %teaser', array('%teaser' => $teaser));
        \Drupal::logger('akismet')->notice($message);
        break;

      case 'unsure':
      default:
        if ($akismet['unsure'] == 'moderate') {
          $form_state->setValue(array('akismet', 'require_moderation'), TRUE);
        }
        elseif ($akismet['unsure'] == 'discard') {
          $form_state->setError($form, t('Your submission has triggered the spam filter and will not be accepted'));
        }
        $message = SafeMarkup::format('Unsure: %teaser', array('%teaser' => $teaser));
        \Drupal::logger('akismet')->notice($message);
        break;
    }
  }

  /**
   * Form validation handler to perform post-validation tasks.
   */
  public static function validatePost(&$form, FormState $form_state) {
    // Retain a post instead of discarding it. If 'discard' is FALSE, then the
    // 'moderation callback' is responsible for altering $form_state in a way that
    // the post ends up in a moderation queue. Most callbacks will only want to
    // set or change a value in $form_state.
    $akismet = $form_state->getValue('akismet');
    if ($akismet['require_moderation']) {
      $function = $akismet['moderation callback'];
      if (!empty($function) && is_callable($function)) {
        call_user_func_array($function, array(&$form, $form_state));
      }
    }
  }

  /**
   * Form submit handler to flush Akismet session and form information from cache.
   *
   * This is necessary as the entity forms will no longer automatically save
   * the data with the entity.
   *
   * @todo: Possible problems:
   *   - This submit handler is invoked too late; the primary submit handler might
   *     send out e-mails directly after saving the entity (e.g.,
   *     user_register_form_submit()), so akismet_mail_alter() is invoked before
   *     Akismet session data has been saved.
   */
  public static function submitForm($form, FormState $form_state) {
    // Some modules are implementing multi-step forms without separate form
    // submit handlers. In case we reach here and the form will be rebuilt, we
    // need to defer our submit handling until final submission.
    $is_rebuilding = $form_state->isRebuilding();
    if ($is_rebuilding) {
      return;
    }

    $akismet = $form_state->getValue('akismet');
    $akismet_request = $form_state->getValue('akismet_request');
    $akismet_response = $form_state->getValue('akismet_response');
    $form_object = $form_state->getFormObject();
    // If an 'entity' and a 'post_id' mapping was provided via
    // hook_akismet_form_info(), try to automatically store Akismet session data.
    if (empty($akismet) || empty($akismet['entity']) || !($form_state->getFormObject() instanceof EntityFormInterface)) {
      return;
    }
    /* @var $form_object \Drupal\Core\Entity\EntityFormInterface */
    $entity_id = $form_object->getEntity()->id();
    $data = (object) $akismet;
    $data->id = $entity_id;
    $data->request = $akismet_request;
    //$data->request['server'] = $_SERVER;
    $data->moderate = $akismet['require_moderation'] ? 1 : 0;
    $data->guid = $akismet_response['guid'];
    $data->classification = $akismet_response['classification'];
    $stored_data = ResponseDataStorage::save($data);
    $form_state->setValue(['akismet', 'data'], $stored_data);
  }
}
