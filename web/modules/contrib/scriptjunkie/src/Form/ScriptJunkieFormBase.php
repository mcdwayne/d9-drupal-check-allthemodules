<?php

namespace Drupal\scriptjunkie\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\scriptjunkie\ScriptJunkieStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for path add/edit forms.
 */
abstract class ScriptJunkieFormBase extends FormBase {

  /**
   * An array containing the Script ID and name.
   *
   * @var array
   */
  protected $script;

  /**
   * The Script Junkie storage service.
   *
   * @var \Drupal\scriptjunkie\ScriptJunkieStorageInterface
   */
  protected $scriptJunkieStorage;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a new PathController.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $script_kunkie_storage
   *   The path alias storage.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ScriptJunkieStorageInterface $script_kunkie_storage, RequestContext $request_context) {
    $this->scriptJunkieStorage = $script_kunkie_storage;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('scriptjunkie.scriptjunkie_storage'),
      $container->get('router.request_context')
    );
  }

  /**
   * Builds the Script used by the form.
   *
   * @param int|null $sid
   *   Either the unique script ID, or NULL if a new one is being created.
   */
  abstract protected function buildScript($sid);

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sid = NULL) {

    // Load the given Script, or an empty one.
    $this->script = $this->buildScript($sid);

    $roles = user_roles();
    $role_options = array();
    foreach ($roles as $rid => $name) {
      $role_options[$rid] = $name->id();
    }

    $access = \Drupal::currentUser()->hasPermission('use PHP for scriptjunkie visibility');
    $mode = $this->script['pages']['mode'];
    $pages = $this->script['pages']['list'];

    $form = array(
      '#tree' => TRUE,
    );

    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => t('General settings'),
      '#collapsible' => FALSE,
    );
    $form['general']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Namespace'),
      '#description' => t('For internal use, a unique namespace, this can only be set once.  Must be Unique and may only use lowercase alphanumeric characters and underscores (_).'),
      '#disabled' => !empty($this->script['name']),
      '#tree' => FALSE,
      '#max_length' => 25,
      '#required' => empty($this->script['name']),
      '#default_value' => $this->script['name'],
    );
    $form['general']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('A title to be used within the administration interface.'),
      '#required' => TRUE,
      '#default_value' => $this->script['general']['title'],
    );
    $form['general']['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#description' => t('Used to better identify the scripts use.'),
      '#default_value' => $this->script['general']['description'],
    );
    $form['code'] = array(
      '#type' => 'fieldset',
      '#title' => t('Code'),
      '#collapsible' => FALSE,
    );
    $form['code']['scope'] = array(
      '#type' => 'select',
      '#title' => t('Scope'),
      '#description' => t('The location in which to place the script (header or footer).'),
      '#options' => array(
        'footer' => t('Footer'),
        'header' => t('Header'),
      ),
      '#default_value' => $this->script['scope'],
      '#required' => TRUE,
      '#tree' => FALSE,
    );
    $form['code']['script'] = array(
      '#type' => 'textarea',
      '#title' => t('Script'),
      '#rows' => 10,
      '#cols' => 45,
      '#required' => TRUE,
      '#description' => '<p>' . t('Enter the code or script you wish to load on the bottom of your page(s).') . '</p>',
      '#default_value' => $this->script['script'],
      '#tree' => FALSE,
    );
    $form['roles'] = array(
      '#type' => 'fieldset',
      '#title' => t('Roles'),
      '#collapsible' => FALSE,
    );
    $form['roles']['visibility'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Remove script for specific roles'),
      '#default_value' => $this->script['roles']['visibility'],
      '#options' => $role_options,
      '#description' => t('Remove script only for the selected role(s). If none of the roles are selected, all roles will have the script. If a user has none of the roles checked, that user will have the script.'),
    );
    $form['pages'] = array(
      '#type' => 'fieldset',
      '#title' => t('Page Visibility'),
      '#collapsible' => FALSE,
    );
    if ($mode == 2 && !$access) {
      $form['pages'] = array();
      $form['pages']['mode'] = array('#type' => 'value', '#value' => 2);
      $form['pages']['list'] = array('#type' => 'value', '#value' => $pages);
    }
    else {
      $options = array(t('Add to every page except the listed pages.'), t('Add to the listed pages only.'));
      $description = t("New line separated paths that must start with a leading slash. Wildcard character is *. E.g. /comment/*/reply. %front is the front page.",
        array('%front' => '<front>')
      );

      if ($access) {
        $options[] = t('Add if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).');
        $description .= ' ' . t('If the PHP-mode is chosen, enter PHP code between %php. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      $form['pages']['mode'] = array(
        '#type' => 'radios',
        '#title' => t('Add script to specific pages'),
        '#options' => $options,
        '#default_value' => $mode,
      );
      $form['pages']['list'] = array(
        '#type' => 'textarea',
        '#title' => t('Pages'),
        '#default_value' => $pages,
        '#description' => $description,
        '#wysiwyg' => FALSE,
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = &$form_state->getValue('name');
    $sid = $form_state->getValue('sid');

    if (!empty($name) && !$this->scriptJunkieStorage->scriptJunkieIsValidNamespace($name)) {
      $form_state->setErrorByName('name', t('Script namespace may only contain lowercase letters, numbers and underscores (a-z, 0-9, and _).'));
    }
    if (empty($sid) && !empty($name) && $this->scriptJunkieStorage->scriptExists($name)) {
      $form_state->setErrorByName('name', t('Script namespace must be unique, the namespace "%name" is in use.', ['%name' => $name]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove unnecessary values.
    $form_state->cleanValues();

    $sid = $form_state->getValue('sid', 0);
    $name = $form_state->getValue('name');
    $general = serialize($form_state->getValue('general'));
    $scope = $form_state->getValue('scope');
    $script = $form_state->getValue('script');
    $roles = serialize($form_state->getValue('roles'));
    $pages = serialize($form_state->getValue('pages'));

    $this->scriptJunkieStorage->save($name, $general, $scope, $script, $roles, $pages, $sid);

    drupal_set_message($this->t('The script has been saved.'));
    $form_state->setRedirect('scriptjunkie.settings');
  }

}
