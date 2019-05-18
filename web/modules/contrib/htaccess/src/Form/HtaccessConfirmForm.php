<?php

/**
 * @file
 * Administration pages.
 */

/**
 * Admin settings.
 */

namespace Drupal\htaccess\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form to confirm Htaccess deployment or deletion
 */
class HtaccessConfirmForm extends ConfirmFormBase {
  /**
      * The ID htaccess configuration to delete or deploy
      *
      * @var string
      *
      * The Name of the htaccess configuration to delete or deploy
      *
      * @var string
      *
      * The action to take on the htaccess configuration.
      *
      * @var string
      */
     protected $id;

     protected $action;

     protected $name;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'htaccess_admin_confirm';
  }

  /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        //the question to display to the user.
        return t('Are you sure you want to %action the htaccess profile %profile_name?', array('%action' => $this->action,'%profile_name' => $this->name));
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl() {
        //this needs to be a valid route otherwise the cancel link won't appear
        return new Url('htaccess.admin_deployment');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription() {
        //a brief desccription
        if ($this->action == 'Deploy') {
          return t('The htaccess %profile_name will be deployed.', array('%profile_name' => $this->name));
        }
        elseif ($this->action == 'Delete') {
          return t('The htaccess %profile_name will be deleted. This action cannot be undone.', array('%profile_name' => $this->name));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
        return $this->t('%action', array('%action' => $this->action));
    }


    /**
     * {@inheritdoc}
     */
    public function getCancelText() {
        return $this->t('Cancel');
    }
/**
 * Admin htaccess confirm form
 */
public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
  $path = $request->getPathInfo();

  $parts = explode('/', $path);

  $this->action = UCFirst($parts[6]);
  $this->id = $parts[7];

  $select = Database::getConnection()->select('htaccess', 'h');
  $select->fields('h');
  $select->condition('id', $this->id);

  $results = $select->execute();
  $result = $results->fetch();

  $this->name = $result->name;

  return parent::buildForm($form,$form_state);
}

/**
 * Submit handler for confirm form
 */
public function submitForm(array &$form, FormStateInterface $form_state) {

    switch ($this->action) {
      case 'Deploy':
        $root_path = \Drupal::root();

        $htaccess_path =  $root_path   . '/.htaccess';

        $select = Database::getConnection()->select('htaccess', 'h');
        $select->fields('h');
        $select->condition('id', $this->id);

        $results = $select->execute();
        $result = $results->fetch();

        $htaccess_content = $result->htaccess;

        // Remove utf8-BOM
        $htaccess_content = str_replace("\xEF\xBB\xBF",'', $htaccess_content);

        // Standardize the EOL.
        $htaccess_content = str_replace("\r\n", PHP_EOL, $htaccess_content);

        // Try to write to the .htaccess file
        if (file_put_contents($htaccess_path, $htaccess_content)) {
          \Drupal::service("file_system")->chmod($htaccess_path, 0644);

          // Get the current htaccess deployed
          $htaccess_current = Database::getConnection()->select('htaccess', 'h');
          $htaccess_current->fields('h');
          $htaccess_current->condition('deployed', 1);
          $results = $htaccess_current->execute();
          $current = $results->fetch();

          // If any, set the status to 0
          if($current){
            $disable = Database::getConnection()->update('htaccess');
            $disable->fields(array(
              'deployed' => 0)
            );
            $disable->condition('id', $current->id);
            $disable->execute();
          }

          // Set the status to 1
          $deploy = Database::getConnection()->update('htaccess');
          $deploy->fields(array(
            'deployed' => 1)
          );
          $deploy->condition('id', $result->id);
          $deploy->execute();

          drupal_set_message(t('Htaccess profile @profile has been deployed.', array('@profile' => $result->name)));

        }
        else {
          $variables = array(
            '%directory' => $root_path,
            '!htaccess' => '<br />' . nl2br(\Drupal\Component\Utility\SafeMarkup::checkPlain($htaccess_content)),
          );

          \Drupal::logger('security')->error("Security warning: Couldn't write .htaccess file.", []);

          drupal_set_message(t('Error during deployment: couldn\'t write .htaccess file. You have to download it and manually put it in the root of your Drupal installation.'), 'error');

        }
        break;
      case 'Delete':
        // Check that the profile is not in use
        $htaccess_check = Database::getConnection()->select('htaccess', 'h');
        $htaccess_check->fields('h');
        $htaccess_check->condition('deployed', 1);
        $htaccess_check->condition('id', $this->id);
        $results = $htaccess_check->execute();

        if (!empty($results->fetchCol())) {
          drupal_set_message(t('This htaccess\'s profile is currently in use'), 'error');
        }
        else{
          $htaccess_get = Database::getConnection()->delete('htaccess');
          $htaccess_get->condition('id', $this->id);
          $htaccess_get->execute();

          drupal_set_message(t('Htaccess profile has been removed.'));
        }
        break;
    }

    $form_state->setRedirect('htaccess.admin_deployment');
  }
}
