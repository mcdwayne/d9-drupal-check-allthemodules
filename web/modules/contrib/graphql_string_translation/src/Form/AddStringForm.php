<?php

namespace Drupal\graphql_string_translation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\locale\SourceString;
use Drupal\locale\StringStorageException;
use Drupal\locale\StringStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddStringForm extends FormBase {

  /**
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * awaiting for drupal 9:
   * @var \Drupal\Core\Messenger\MessengerInterface;
   */
//  protected $messenger;
//  public function __construct(StringStorageInterface $localeStorage, MessengerInterface $messenger) {
//    $this->messenger = $messenger;
  //      $container->get('messenger')

  /**
   * AddStringForm constructor.
   *
   * @param \Drupal\locale\StringStorageInterface $localeStorage
   */
  public function __construct(StringStorageInterface $localeStorage) {
    $this->localeStorage = $localeStorage;
  }

  /**
   * Creates the form object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Form\FormBase|\Drupal\graphql_string_translation\Form\AddStringForm
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('locale.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphql_string_translation_add_string_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['string'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $string = $form_state->getValue('string');
    $sourceString = $this->localeStorage->findString([
      'source' => $string,
      'context' => $this->getContext(),
    ]);

    if (is_null($sourceString)) {
      try {
        $sourceString = new SourceString();
        $sourceString->setString($string);
        $sourceString->setStorage($this->localeStorage);
        $sourceString->context = $this->getContext();
        $sourceString->save();
        $this->setMessage($this->t('Source string saved successfully.'));
      } catch (StringStorageException $e) {
        $msg = $this->t('String storage error: %m', ['%m' => $e->getMessage()]);
        $this->setMessage($msg, 'error');
      }
    } else {
      $this->setMessage($this->t('The source string already exists in given context.'), 'warning');
    }
  }

  /**
   * Returns the translation context.
   *
   * @return string
   */
  protected function getContext() {
    return 'graphql';
  }

  /**
   * Sets drupal message.
   *
   * Once drupal_set_message is removed this will need to be changed to use
   * the messenger service.
   *
   * @param $message
   * @param string $type
   */
  protected function setMessage($message, $type = 'status') {
    drupal_set_message($message, $type);
  }

}
