<?php
/**
 * @file
 *
 * Contains Drupal\wisski_pathbuilder\FOrm\ExporterForm
 */
    
namespace Drupal\wisski_pathbuilder\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

use Symfony\Component\HttpFoundation\Response;

use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity;
use Drupal\wisski_pathbuilder\Entity\WisskiPathEntity;
    
class ImporterForm extends FormBase {
  
  protected $configManager = NULL;

  public function getFormId() {
    return 'wisski_pathbuilder_importer_form';
  }
  


  public function buildForm(array $form, FormStateInterface $form_state, $pbid = NULL) {

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File URL'),
    ];
    $form['actions'] = [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Import'),
      ],
    ];
    return $form;

  }
  
  

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // get the file and decode the yaml
    $url = $form_state->getValue('url');
    $yaml = file_get_contents($url);
    $config_assemblage = Yaml::decode($yaml);
    // create a new config entity for each entry
    $configManager = \Drupal::service('config.manager');
    $factory = $configManager->getConfigFactory();
    foreach ($config_assemblage as $config_name => $data) {
      $config = $factory->getEditable($config_name);
      $config->setData($data);
      $config->save();
    }
  }
  
}
