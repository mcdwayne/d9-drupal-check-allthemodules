<?php

namespace Drupal\captcha_questions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;

/**
 * Displays the captcha_questions settings form.
 */
class CaptchaQuestionsSettingsForm extends ConfigFormBase {

  /**
   * ModuleHandler services object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleManager;

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandler $moduleManager, Connection $database) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleManager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['captcha_questions.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'captcha_questions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t("This is just a very simple mechanism to protect from automated bots. To make it as easy for real humans, consider providing the answer with the question. Example '<em>What is Mickeys last name? It's \"Mouse\"</em>'. You could also do simply 'What is 1+1?', but some bots may figure that out."),
    ];

    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Logging options'),
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#open' => TRUE,
    ];

    // Disables checkbox if dblog module is not enabled.
    $form['configuration']['captcha_questions_watchdog'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log to watchdog'),
      '#description' => $this->t('Check this box to log all failed submissions to watchdog'),
      '#default_value' => $this->config('captcha_questions.settings')->get('captcha_questions_watchdog'),
      '#disabled' => $this->moduleHandler->moduleExists('dblog') ? FALSE : TRUE,
    ];

    // Disables checkbox if captcha_questions_dblog module is not enabled.
    $form['configuration']['captcha_questions_dblog'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable logging internal database table'),
      '#description' => $this->t('Check this box to log all failed submissions in separate database table'),
      '#default_value' => $this->config('captcha_questions.settings')->get('captcha_questions_dblog'),
      '#disabled' => $this->moduleHandler->moduleExists('captcha_questions_dblog') ? FALSE : TRUE,
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('CAPTCHA'),
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#open' => TRUE,
    ];

    $question = $this->config('captcha_questions.settings')->get('captcha_questions_question');
    $form['settings']['captcha_questions_question'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Question to ask'),
      '#default_value' => $question,
      '#maxlength' => 256,
      '#required' => TRUE,
    ];

    $answers = $this->config('captcha_questions.settings')->get('captcha_questions_answers');
    $form['settings']['captcha_questions_answers'] = [
      '#type' => 'textarea',
      '#title' => 'Accepted answer(s)',
      '#default_value' => implode("\n", $answers),
      '#description' => $this->t('Please provide one or more accepted answers, one per line. Answers are case-insensitive'),
      '#format' => NULL,
      '#required' => TRUE,
    ];

    $description = $this->config('captcha_questions.settings')->get('captcha_questions_description');
    $form['settings']['captcha_questions_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $description,
      '#maxlength' => 256,
    ];

    $form['forms'] = [
      '#type' => 'details',
      '#title' => $this->t('Form protection'),
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#open' => TRUE,
      '#prefix' => '<div id="captcha-questions-form-id-wrapper">',
      '#suffix' => '</div>',
    ];

    // Getting form_ids.
    if (!$form_state->getValue('form_ids')) {
      $form_state->setValue('form_ids', $this->captchaQuestionsGetFormIds());
    }

    // Adding new custom form_ids.
    if ($form_state->getValue('add_form_name') != '') {
      $form_ids = $form_state->getValue('form_ids');
      $selected_all_form_ids = $form_state->getValue('captcha_questions_form_ids');
      $selected_all_form_ids = array_flip($selected_all_form_ids);
      $all_form_ids = array_merge($form_ids, $selected_all_form_ids);
      $all_form_ids[] = $form_state->getValue('add_form_name');
      $form_state->setValue('form_ids', $all_form_ids);
    }

    // Getting previously selected form_ids.
    $form_state->setValue('selected_form_ids', $this->config('captcha_questions.settings')->get('captcha_questions_form_ids'));

    // Create select list with previously selected form_ids as selected.
    $form['forms']['captcha_questions_form_ids'] = [
      '#type' => 'checkboxes',
      '#options' => array_combine($form_state->getValue('form_ids'), $form_state->getValue('form_ids')),
      '#default_value' => array_intersect($form_state->getValue('selected_form_ids'), $form_state->getValue('form_ids')),
      '#title' => $this->t('What forms should be protected?'),
    ];

    // Adding node title and link to webform_ids options text.
    if ($this->moduleHandler->moduleExists('webform')) {
      $webform_forms = $this->captchaQuestionsGetWebforms();
      foreach ($webform_forms as $webform_id => $webform) {
        $url = Url::fromUri('base://form/' . $webform['id']);
        $webform_node_link = Link::fromTextAndUrl($webform['title'], $url)->toString();
        $webform_form_id_option_text = $webform_id . ' (' . $webform_node_link . ')';
        $form['forms']['captcha_questions_form_ids']['#options'][$webform_id] = $webform_form_id_option_text;
      }
    }

    $form['forms']['add_form_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom form_id'),
      '#default_value' => '',
      '#maxlength' => 256,
    ];

    $form['forms']['add_form'] = [
      '#type' => 'button',
      '#value' => $this->t('Add custom form_id'),
      '#submit' => ['captchaQuestionsAddForm'],
      '#ajax' => [
        'callback' => '::captchaQuestionsAddFormCallback',
        'wrapper' => 'captcha-questions-form-id-wrapper',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If question is specified without answer, ask for answer.
    if (!empty($form_state->getValue('captcha_questions_question')) && empty($form_state->getValue('captcha_questions_answers'))) {
      $form_state->setErrorByName('captcha_questions_answer', $this->t('Please provide an answer'));
    }

    // If answer given, but no question, ask for question.
    if (empty($form_state->getValue('captcha_questions_question')) && !empty($form_state->getValue('captcha_questions_answers'))) {
      $form_state->setErrorByName('captcha_questions_question', $this->t('Please add a question'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If question, answer and description are all empty, delete the variables.
    // The last variable, captcha_questions_form_ids are removed on uninstall.
    if (empty($form_state->getValue('captcha_questions_question')) && empty($form_state->getValue('captcha_questions_answer'))) {
      $this->config('captcha_questions.settings')->delete('captcha_questions_question');
      $this->config('captcha_questions.settings')->delete('captcha_questions_answer');
      $this->config('captcha_questions.settings')->delete('captcha_questions_description');
    }
    if (empty($form_state->getValue('captcha_questions_question')) && empty($form_state->getValue('captcha_questions_answers'))) {
      $this->config('captcha_questions.settings')->delete('captcha_questions_question');
      $this->config('captcha_questions.settings')->delete('captcha_questions_answers');
      $this->config('captcha_questions.settings')->delete('captcha_questions_description');
    }

    // Split answers into arrays and set variable manually.
    $answers = explode("\n", $form_state->getValue('captcha_questions_answers'));
    $answers = array_map('trim', $answers);
    asort($answers);
    $this->config('captcha_questions.settings')->set('captcha_questions_answers', $answers);
    $form_state->unsetValue('captcha_questions_answers');

    $count_protected_form_ids = 0;

    // Counting number of selected form_ids, removing unselected form_ids.
    foreach ($form_state->getValue('captcha_questions_form_ids') as $form_id => $value) {
      if ($form_id === $value) {
        $count_protected_form_ids++;
      }
      else {
        $captcha_questions_form_ids = $form_state->getValue('captcha_questions_form_ids');
        unset($captcha_questions_form_ids[$form_id]);
        $form_state->setValue('captcha_questions_form_ids', $captcha_questions_form_ids);
      }
    }

    if ($count_protected_form_ids == 0) {
      drupal_set_message($this->t('No forms selected'));
    }
    else {
      $message = $this->formatPlural($count_protected_form_ids, '1 form protected', '@count forms protected');
      drupal_set_message($message);
    }
    $this->config('captcha_questions.settings')
      ->set('captcha_questions_watchdog', $form_state->getValue('captcha_questions_watchdog'))
      ->set('captcha_questions_dblog', $form_state->getValue('captcha_questions_dblog'))
      ->set('captcha_questions_question', $form_state->getValue('captcha_questions_question'))
      ->set('captcha_questions_description', $form_state->getValue('captcha_questions_description'))
      ->set('captcha_questions_form_ids', $form_state->getValue('captcha_questions_form_ids'))
      ->set('add_form_name', $form_state->getValue('add_form_name'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to make a list of candidates of form ids.
   */
  public function captchaQuestionsGetFormIds() {

    // Some form nids that might exist on the site.
    $form_ids = [
      'contact_site_form',
      'contact_personal_form',
      'user_register_form',
      'user_pass',
      'user_login_form',
      'user_login_block',
      'forum_node_form',
    ];

    // Fetching form ids for all node types.
    foreach (node_type_get_names() as $type => $name) {
      $form_ids[] = 'comment_node_' . $type . '_form';
    }

    // Fetching webform form_ids.
    if ($this->moduleHandler->moduleExists('webform')) {
      $webform_forms = $this->captchaQuestionsGetWebforms();
      foreach ($webform_forms as $webform_id => $webform) {
        $form_ids[] = $webform_id;
      }
    }

    // Adding custom form_ids. This will add all selected forms.
    $custom_form_ids = $this->config('captcha_questions.settings')->get('captcha_questions_form_ids');
    foreach ($custom_form_ids as $custom_form_id) {
      $form_ids[] = $custom_form_id;
    }

    // Removing possible duplicates from last step.
    $form_ids = array_unique($form_ids);

    return $form_ids;
  }

  /**
   * Helper function to find webform form_ids.
   *
   * Adapted from the function webform_mollom_form_list() in webform.module.
   * Returns array keyed by webform form_ids and title, id of the webform.
   */
  public function captchaQuestionsGetWebforms() {
    $forms = [];

    foreach (Webform::loadMultiple() as $webform_obj) {
      $form_id = 'webform_submission_' . $webform_obj->id() . '_form';
      $forms[$form_id] = [
        'title' => $this->t('@name form', ['@name' => $webform_obj->label()]),
        'id' => $webform_obj->id(),
      ];
    }
    return $forms;
  }

  /**
   * Callback for ajax-enabled button.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function captchaQuestionsAddFormCallback(array &$form, FormStateInterface $form_state) {
    return $form['forms'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Causes a rebuild.
   */
  public function captchaQuestionsAddForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
