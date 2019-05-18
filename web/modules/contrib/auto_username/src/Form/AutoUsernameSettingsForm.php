<?php

namespace Drupal\auto_username\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Case should be left as is in the generated username.
 */
define('AUN_CASE_LEAVE_ASIS', 0);

/**
 * Case should be lowercased in the generated username.
 */
define('AUN_CASE_LOWER', 1);

/**
 * Remove the punctuation from the username.
 */
define('AUN_PUNCTUATION_REMOVE', 0);

/**
 * Replace the punctuation with the separator in the username.
 */
define('AUN_PUNCTUATION_REPLACE', 1);

/**
 * Leave the punctuation as it is in the username.
 */
define('AUN_PUNCTUATION_DO_NOTHING', 2);

/**
 * Class AutoUsernameSettingsForm.
 *
 * @package Drupal\auto_username\Form
 */
class AutoUsernameSettingsForm extends ConfigFormBase {
  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $account, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->account = $account;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_username_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'auto_username.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('auto_username.settings');

    $form = [];

    $form['aun_general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['aun_general_settings']['aun_pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pattern for username'),
      '#description' => $this->t('Enter the pattern for usernames.  You may use any of the tokens listed below.'),
      '#default_value' => $config->get('aun_pattern'),
    ];

    $form['aun_general_settings']['token_help'] = [
      '#title' => $this->t('Replacement patterns'),
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('Note that fields that are not present in the user registration form will get replaced with an empty string when the account is created.  That is rarely desirable.'),
    ];

    $form['aun_general_settings']['token_help']['help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user'],
      '#global_types' => NULL,
    ];

    // Other module configuration.
    $form['aun_other_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Other settings'),
      '#open' => TRUE,
      '#collapsed' => FALSE,
    ];

    if ($this->account->hasPermission('use PHP for username patterns')) {
      $form['aun_other_settings']['aun_php'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Evaluate PHP in pattern.'),
        '#description' => $this->t('If this box is checked, the pattern will be executed as PHP code after token substitution has taken place.  You must surround the PHP code in &lt;?php and ?&gt; tags.  Token replacement will take place before PHP code execution.  Note that $account is available and can be used by your code.'),
        '#default_value' => $config->get('aun_php'),
      ];
    }

    $form['aun_other_settings']['aun_update_on_edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update on user edit'),
      '#description' => $this->t("If this box is checked, the username will be reset any time the user's profile is updated.  That can help to enforce a username format, but may result in a user's login name changing unexpectedly.  It is best used in conjunction with an alternative login mechanism, such as OpenID or an e-mail address."),
      '#default_value' => $config->get('aun_update_on_edit'),
    ];

    $form['aun_other_settings']['aun_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#size' => 1,
      '#maxlength' => 1,
      '#default_value' => $config->get('aun_separator'),
      '#description' => $this->t('Character used to separate words in titles. This will replace any spaces and punctuation characters.'),
    ];

    $form['aun_other_settings']['aun_case'] = [
      '#type' => 'radios',
      '#title' => $this->t('Character case'),
      '#default_value' => $config->get('aun_case'),
      '#options' => [
        AUN_CASE_LEAVE_ASIS => $this->t('Leave case the same as source token values.'),
        AUN_CASE_LOWER => $this->t('Change to lower case'),
      ],
    ];

    $max_length = $this->autoUsernameGetSchemaNameMaxlength();

    $form['aun_other_settings']['aun_max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum alias length'),
      '#size' => 3,
      '#default_value' => $config->get('aun_max_length'),
      '#min' => 1,
      '#max' => $max_length,
      '#description' => $this->t('Maximum length of aliases to generate. @max is the maximum possible length.', ['@max' => $max_length]),
    ];
    $form['aun_other_settings']['aun_max_component_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum component length'),
      '#size' => 3,
      '#default_value' => $config->get('aun_max_component_length'),
      '#min' => 1,
      '#max' => $max_length,
      '#description' => $this->t('Maximum text length of any component in the username (e.g., [user:mail]). @max is the maximum possible length.', ['@max' => $max_length]),
    ];

    $form['aun_other_settings']['aun_transliterate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transliterate prior to creating username'),
      '#default_value' => $config->get('aun_transliterate') && $this->moduleHandler->moduleExists('transliteration'),
      '#description' => $this->t('When a pattern includes certain characters (such as those with accents) should auto_username attempt to transliterate them into the ASCII-96 alphabet? Transliteration is handled by the Transliteration module.'),
      '#access' => $this->moduleHandler->moduleExists('transliteration'),
    ];

    $form['aun_other_settings']['aun_reduce_ascii'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reduce strings to letters and numbers'),
      '#default_value' => $config->get('aun_reduce_ascii'),
      '#description' => $this->t('Filters the new username to only letters and numbers found in the ASCII-96 set.'),
    ];

    $form['aun_other_settings']['aun_replace_whitespace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace whitespace with separator.'),
      '#default_value' => $config->get('aun_replace_whitespace'),
      '#description' => $this->t('Replace all whitespace in tokens with the separator character specified below.  Note that this will affect the tokens themselves, not the pattern specified above.  To avoid spaces entirely, ensure that the pattern above contains no spaces.'),
    ];

    $form['aun_other_settings']['aun_ignore_words'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Strings to Remove'),
      '#default_value' => $config->get('aun_ignore_words'),
      '#description' => $this->t('Words to strip out of the username, separated by commas. Do not use this to remove punctuation.'),
      '#wysiwyg' => FALSE,
    ];

    $form['punctuation'] = [
      '#type' => 'details',
      '#title' => $this->t('Punctuation'),
      '#open' => TRUE,
    ];

    $punctuation = $this->autoUsernamePunctuationChars();
    foreach ($punctuation as $name => $details) {
      $details['default'] = AUN_PUNCTUATION_REMOVE;
      if ($details['value'] == $config->get('aun_separator')) {
        $details['default'] = AUN_PUNCTUATION_REPLACE;
      }
      $form['punctuation']['aun_punctuation_' . $name] = [
        '#type' => 'select',
        '#title' => $details['name'] . ' (<code>' . Html::escape($details['value']) . '</code>)',
        '#default_value' => $config->get('aun_punctuation_' . $name, $details['default']),
        '#options' => [
          AUN_PUNCTUATION_REMOVE => $this->t('Remove'),
          AUN_PUNCTUATION_REPLACE => $this->t('Replace by separator'),
          AUN_PUNCTUATION_DO_NOTHING => $this->t('No action (do not replace)'),
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate auto_username_settings_form form submissions.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Perform a basic check for HTML characters in the strings to remove field.
    if (strip_tags($form_state->getValue('aun_ignore_words')) != $form_state->getValue('aun_ignore_words')) {
      $form_state->setErrorByName('aun_ignore_words', $this->t('The <em>Strings to remove</em> field must not contain HTML. Make sure to disable any WYSIWYG editors for this field.'));
    }

    // Validate that the separator is not set to be removed.
    // This isn't really all that bad so warn, but still allow them to save the
    // value.
    $separator = $form_state->getValue('aun_separator');
    $punctuation = $this->autoUsernamePunctuationChars();
    foreach ($punctuation as $name => $details) {
      if ($details['value'] == $separator) {
        $action = $form_state->getValue('aun_punctuation_' . $name);
        if ($action == AUN_PUNCTUATION_REMOVE) {
          drupal_set_message($this->t('You have configured the @name to be the separator and to be removed when encountered in strings. You should probably set the action for @name to be "replace by separator".', ['@name' => $details['name']]), 'error');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('auto_username.settings');
    // Set values in variables.
    $config->set('aun_pattern', $form_state->getValues()['aun_pattern']);
    $config->set('aun_php', $form_state->getValues()['aun_php']);
    $config->set('aun_update_on_edit', $form_state->getValues()['aun_update_on_edit']);
    $config->set('aun_separator', $form_state->getValues()['aun_separator']);
    $config->set('aun_case', $form_state->getValues()['aun_case']);
    $config->set('aun_max_length', $form_state->getValues()['aun_max_length']);
    $config->set('aun_max_component_length', $form_state->getValues()['aun_max_component_length']);
    $config->set('aun_transliterate', $form_state->getValues()['aun_transliterate']);
    $config->set('aun_reduce_ascii', $form_state->getValues()['aun_reduce_ascii']);
    $config->set('aun_replace_whitespace', $form_state->getValues()['aun_replace_whitespace']);
    $config->set('aun_ignore_words', $form_state->getValues()['aun_ignore_words']);
    $punctuation = $this->autoUsernamePunctuationChars();
    foreach ($punctuation as $name => $details) {
      $config->set('aun_punctuation_' . $name, $form_state->getValues()['aun_punctuation_' . $name]);
    }
    $config->save();
  }

  /**
   * Fetch the maximum length of the {users}.name field from the schema.
   *
   * @return array
   *   An integer of the maximum username length allowed by the database.
   */
  public static function autoUsernameGetSchemaNameMaxlength() {
    $maxlength = &drupal_static(__FUNCTION__);
    if (!isset($maxlength)) {
      $schema = drupal_get_module_schema('user');
      $maxlength = $schema['users_data']['fields']['name']['length'];
    }
    return $maxlength;
  }

  /**
   * Return an array of arrays for punctuation values.
   *
   * Returns an array of arrays for punctuation values keyed by a name,
   * including the value and a textual description.
   * Can and should be expanded to include "all" non text punctuation values.
   *
   * @return array
   *   An array of arrays for punctuation values keyed by a name, including the
   *   value and a textual description.
   */
  public static function autoUsernamePunctuationChars() {
    $punctuation = &drupal_static(__FUNCTION__);
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    if (!isset($punctuation)) {
      $cid = 'auto_username:punctuation:' . $language;
      if ($cache = \Drupal::cache()->get($cid)) {
        $punctuation = $cache->data;
      }
      else {
        $punctuation                      = [];
        $punctuation['double_quotes']     = ['value' => '"', 'name' => t('Double quotation marks')];
        $punctuation['quotes']            = ['value' => '\'', 'name' => t("Single quotation marks (apostrophe)")];
        $punctuation['backtick']          = ['value' => '`', 'name' => t('Back tick')];
        $punctuation['comma']             = ['value' => ',', 'name' => t('Comma')];
        $punctuation['period']            = ['value' => '.', 'name' => t('Period')];
        $punctuation['hyphen']            = ['value' => '-', 'name' => t('Hyphen')];
        $punctuation['underscore']        = ['value' => '_', 'name' => t('Underscore')];
        $punctuation['colon']             = ['value' => ':', 'name' => t('Colon')];
        $punctuation['semicolon']         = ['value' => ';', 'name' => t('Semicolon')];
        $punctuation['pipe']              = ['value' => '|', 'name' => t('Vertical bar (pipe)')];
        $punctuation['left_curly']        = ['value' => '{', 'name' => t('Left curly bracket')];
        $punctuation['left_square']       = ['value' => '[', 'name' => t('Left square bracket')];
        $punctuation['right_curly']       = ['value' => '}', 'name' => t('Right curly bracket')];
        $punctuation['right_square']      = ['value' => ']', 'name' => t('Right square bracket')];
        $punctuation['plus']              = ['value' => '+', 'name' => t('Plus sign')];
        $punctuation['equal']             = ['value' => '=', 'name' => t('Equal sign')];
        $punctuation['asterisk']          = ['value' => '*', 'name' => t('Asterisk')];
        $punctuation['ampersand']         = ['value' => '&', 'name' => t('Ampersand')];
        $punctuation['percent']           = ['value' => '%', 'name' => t('Percent sign')];
        $punctuation['caret']             = ['value' => '^', 'name' => t('Caret')];
        $punctuation['dollar']            = ['value' => '$', 'name' => t('Dollar sign')];
        $punctuation['hash']              = ['value' => '#', 'name' => t('Number sign (pound sign, hash)')];
        $punctuation['at']                = ['value' => '@', 'name' => t('At sign')];
        $punctuation['exclamation']       = ['value' => '!', 'name' => t('Exclamation mark')];
        $punctuation['tilde']             = ['value' => '~', 'name' => t('Tilde')];
        $punctuation['left_parenthesis']  = ['value' => '(', 'name' => t('Left parenthesis')];
        $punctuation['right_parenthesis'] = ['value' => ')', 'name' => t('Right parenthesis')];
        $punctuation['question_mark']     = ['value' => '?', 'name' => t('Question mark')];
        $punctuation['less_than']         = ['value' => '<', 'name' => t('Less-than sign')];
        $punctuation['greater_than']      = ['value' => '>', 'name' => t('Greater-than sign')];
        $punctuation['slash']             = ['value' => '/', 'name' => t('Slash')];
        $punctuation['back_slash']        = ['value' => '\\', 'name' => t('Backslash')];

        // Allow modules to alter the punctuation list and cache the result.
        \Drupal::moduleHandler()->alter('autoUsernamePunctuationChars', $punctuation);
        \Drupal::cache()->set($cid, $punctuation);
      }
    }

    return $punctuation;
  }

  /**
   * Process an account and return its new username according to currentpattern.
   *
   * @param object $account
   *   The user object to process.
   *
   * @return string
   *   The new name for the user object.
   */
  public static function autoUsernamePatternprocessor($account) {
    $output = '';
    $pattern = \Drupal::state()->get('aun_pattern', '');
    if (trim($pattern)) {
      $pattern_array = explode('\n', trim($pattern));
      $token_service = \Drupal::token();
      // Replace any tokens in the pattern. Uses callback option to clean
      // replacements. No sanitization.
      $output = $token_service->replace($pattern, ['user' => $account], [
        'clear' => TRUE,
        'callback' => [self::class, 'autoUsernameCleanTokenValues'],
      ]);
      // Check if the token replacement has not actually replaced any values. If
      // that is the case, then stop because we should not generate a name.
      // @see token_scan()
      $pattern_tokens_removed = preg_replace('/\[[^\s\]:]*:[^\s\]]*\]/', '', implode('\n', $pattern_array));
      if ($output === $pattern_tokens_removed) {
        return '';
      }
      if (\Drupal::state()->get('aun_php', 0)) {
        $output = self::autoUsernameEval($output, $account);
      }
    }
    return trim($output);
  }

  /**
   * Evaluate php code and pass $account to it.
   */
  public static function autoUsernameEval($code, $account) {
    ob_start();
    print eval('?>' . $code);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
  }

  /**
   * Clean token values.
   *
   * @param array $replacements
   *   An array of token replacements that need to be "cleaned".
   */
  public static function autoUsernameCleanTokenValues(&$replacements) {
    foreach ($replacements as $token => $value) {
      $replacements[$token] = self::autoUsernameCleanstring($value);
    }
  }

  /**
   * Generating Username value.
   *
   * Work out what the new username could be, calling api hooks where
   * applicable, and adding a number suffix if necccessary.
   */
  public static function autoUsernameGenerateUsername(&$account) {
    // Other modules may implement hook_auto_username_name($edit, $account) to
    // generate a username (return a string to be used as the username, NULL to
    // have auto_username generate it).
    $names = \Drupal::moduleHandler()->invokeAll('auto_username_name', [$account]);

    // Remove any empty entries.
    $names = array_filter($names);

    if (empty($names)) {
      // Default implementation of name generation.
      $new_name = AutoUsernameSettingsForm::autoUsernamePatternprocessor($account);
    }
    else {
      // One would expect a single implementation of the hook, but if there
      // are multiples out there use the last one.
      $new_name = array_pop($names);
    }

    // If no new name was found, then either the hook hasn't been implemented,
    // or the aun_pattern hasn't been set yet. Therefore leave the username as
    // it is.
    if (empty($new_name)) {
      return $account->getUsername();
    }

    // Lets check if our name is used somewhere else, and append _1 if it is
    // eg:(chris_123). We do this regardless of whether hook has run, as we
    // can't assume the hook implementation will do this santity check.
    $i = 0;
    do {
      $new_name = empty($i) ? $new_name : $new_name . '_' . $i;
      $found = Database::getConnection()
        ->select('users_field_data', 'u')
        ->fields('u')
        ->condition('uid', $account->id(), '!=')
        ->condition('name', $new_name)
        ->execute()
        ->fetchAll();
      $i++;
    } while (!empty($found));

    return $new_name;
  }

  /**
   * Clean up a string segment to be used in a username.
   *
   * Performs the following possible alterations:
   * - Remove all HTML tags.
   * - Process the string through the transliteration module.
   * - Replace or remove punctuation with the separator character.
   * - Remove back-slashes.
   * - Replace non-ascii and non-numeric characters with the separator.
   * - Remove common words.
   * - Replace whitespace with the separator character.
   * - Trim duplicate, leading, and trailing separators.
   * - Convert to lower-case.
   * - Shorten to a desired length and logical position based on word
   * boundaries.
   *
   * @param string $string
   *   A string to clean.
   *
   * @return mixed|string
   *   The cleaned string.
   */
  public static function autoUsernameCleanstring($string) {
    // Use the advanced drupal_static()pattern, since this is called very often.
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['cache'] = &drupal_static(__FUNCTION__);
    }
    $cache = &$drupal_static_fast['cache'];

    // Generate and cache variables used in this function so that on the second
    // call to autoUsernameCleanstring() we focus on processing.
    if (!isset($cache)) {
      $cache = [
        'separator' => \Drupal::state()->get('aun_separator', '-'),
        'transliterate' => \Drupal::state()->get('aun_transliterate', FALSE) && \Drupal::moduleHandler()->moduleExists('transliteration'),
        'punctuation' => [],
        'reduce_ascii' => (bool) \Drupal::state()->get('aun_reduce_ascii', FALSE),
        'ignore_words_regex' => FALSE,
        'replace_whitespace' => (bool) \Drupal::state()->get('aun_replace_whitespace', FALSE),
        'lowercase' => (bool) \Drupal::state()->get('aun_case', AUN_CASE_LOWER),
        'maxlength' => min(\Drupal::state()->get('aun_max_component_length', 60), self::autoUsernameGetSchemaNameMaxlength()),
      ];

      // Generate and cache the punctuation replacements for strtr().
      $punctuation = self::autoUsernamePunctuationChars();
      foreach ($punctuation as $name => $details) {
        $action = \Drupal::state()->get('aun_punctuation_' . $name, AUN_PUNCTUATION_REMOVE);
        switch ($action) {
          case AUN_PUNCTUATION_REMOVE:
            $cache['punctuation'][$details['value']] = '';
            break;

          case AUN_PUNCTUATION_REPLACE:
            $cache['punctuation'][$details['value']] = $cache['separator'];
            break;

          case AUN_PUNCTUATION_DO_NOTHING:
            // Literally do nothing.
            break;
        }
      }

      // Generate and cache the ignored words regular expression.
      $ignore_words = \Drupal::state()->get('aun_ignore_words', '');
      $ignore_words_regex = preg_replace(['/^[,\s]+|[,\s]+$/', '/[,\s]+/'], ['', '\b|\b'], $ignore_words);
      if ($ignore_words_regex) {
        $cache['ignore_words_regex'] = '\b' . $ignore_words_regex . '\b';
        if (function_exists('mb_eregi_replace')) {
          $cache['ignore_words_callback'] = 'mb_eregi_replace';
        }
        else {
          $cache['ignore_words_callback'] = 'preg_replace';
          $cache['ignore_words_regex'] = '/' . $cache['ignore_words_regex'] . '/i';
        }
      }
    }

    // Empty strings do not need any processing.
    if ($string === '' || $string === NULL) {
      return '';
    }

    // Remove all HTML tags from the string.
    $output = strip_tags(Html::decodeEntities($string));

    // Replace or drop punctuation based on user settings.
    $output = strtr($output, $cache['punctuation']);

    // Reduce strings to letters and numbers.
    if ($cache['reduce_ascii']) {
      $output = preg_replace('/[^a-zA-Z0-9\/]+/', $cache['separator'], $output);
    }

    // Get rid of words that are on the ignore list.
    if ($cache['ignore_words_regex']) {
      $words_removed = $cache['ignore_words_callback']($cache['ignore_words_regex'], '', $output);
      if (Unicode::strlen(trim($words_removed)) > 0) {
        $output = $words_removed;
      }
    }

    // Replace whitespace with the separator.
    if ($cache['replace_whitespace']) {
      $output = preg_replace('/\s+/', $cache['separator'], $output);
    }

    // Trim duplicates and remove trailing and leading separators.
    $output = self::autoUsernameCleanSeparators($output, $cache['separator']);

    // Optionally convert to lower case.
    if ($cache['lowercase']) {
      $output = Unicode::strtolower($output);
    }

    // Shorten to a logical place based on word boundaries.
    $output = Unicode::truncate($output, $cache['maxlength'], TRUE);
    return $output;
  }

  /**
   * Trims duplicate, leading, and trailing separators from a string.
   *
   * @param string $string
   *   The string to clean separators from.
   * @param string $separator
   *   The separator to use when cleaning.
   *
   * @return mixed
   *   The cleaned version of the string.
   *
   * @see autoUsernameCleanSeparators()
   */
  public static function autoUsernameCleanSeparators($string, $separator = NULL) {
    static $default_separator;

    if (!isset($separator)) {
      if (!isset($default_separator)) {
        $default_separator = \Drupal::state()->get('aun_separator', '-');
      }
      $separator = $default_separator;
    }

    $output = $string;

    // Clean duplicate or trailing separators.
    if (strlen($separator)) {
      // Escape the separator.
      $seppattern = preg_quote($separator, '/');

      // Trim any leading or trailing separators.
      $output = preg_replace("/^$seppattern+|$seppattern+$/", '', $output);

      // Replace trailing separators around slashes.
      if ($separator !== '/') {
        $output = preg_replace("/$seppattern+\/|\/$seppattern+/", "/", $output);
      }

      // Replace multiple separators with a single one.
      $output = preg_replace("/$seppattern+/", $separator, $output);
    }

    return $output;
  }

}
