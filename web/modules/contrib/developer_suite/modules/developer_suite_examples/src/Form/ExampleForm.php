<?php

namespace Drupal\developer_suite_examples\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\developer_suite\BatchManagerInterface;
use Drupal\developer_suite\Collection\ViolationCollectionInterface;
use Drupal\developer_suite\Utils;
use Drupal\developer_suite_examples\Collection\ExampleFileCollection;
use Drupal\developer_suite_examples\Command\ExampleCommand;
use Drupal\developer_suite_examples\Validate\ExampleFormValidate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExampleForm.
 *
 * @package Drupal\developer_suite_examples\Form
 */
class ExampleForm extends FormBase {

  /**
   * The violation collection.
   *
   * @var \Drupal\developer_suite\Collection\ViolationCollectionInterface
   */
  private $violationCollection;

  /**
   * The batch manager.
   *
   * @var \Drupal\developer_suite\BatchManagerInterface
   */
  private $batchManager;

  /**
   * The example file collection.
   *
   * @var \Drupal\developer_suite_examples\Collection\ExampleFileCollection
   */
  private $exampleFileCollection;

  /**
   * ExampleForm constructor.
   *
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violation collection.
   * @param \Drupal\developer_suite\BatchManagerInterface $batchManager
   *   The batch manager.
   * @param \Drupal\developer_suite_examples\Collection\ExampleFileCollection $exampleFileCollection
   *   The example file collection.
   */
  public function __construct(
    ViolationCollectionInterface $violationCollection,
    BatchManagerInterface $batchManager,
    ExampleFileCollection $exampleFileCollection
  ) {
    $this->violationCollection = $violationCollection;
    $this->batchManager = $batchManager;
    $this->exampleFileCollection = $exampleFileCollection;
  }

  /**
   * {@inheritdoc}
   *
   * Retrieve the violation collection and the batch manager via the service
   * container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('developer_suite.violation_collection'),
      $container->get('developer_suite.batch_manager'),
      $container->get('developer_suite_examples.example_node_collection')
    );
  }

  /**
   * Example static finished callback.
   *
   * @param bool $success
   *   Indicates if the batch succeeded.
   * @param array $results
   *   The batch results.
   * @param array $operations
   *   The unprocessed batch operations.
   */
  public static function myStaticFinishedCallback($success, array $results, array $operations) {

  }

  /**
   * Example static operation.
   *
   * @param mixed $param1
   *   Parameter 1.
   * @param mixed $param2
   *   Parameter 2.
   */
  public static function myStaticOperation($param1, $param2) {

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve all the files in the collection.
    $files = $this->exampleFileCollection->loadAllFilesExample();

    /** @var \Drupal\developer_suite_examples\Entity\ExampleFile $file */
    foreach ($files as $file) {
      // Do something with the files.
      $file->someOperation();
    }

    $form = [
      'example_value_a' => [
        '#type' => 'textfield',
      ],
      'example_value_b' => [
        '#type' => 'textfield',
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Example submit'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return Utils::getClassId(self::class);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Instantiate your command and optionally pass some data.
    $command = new ExampleCommand(['foo' => 'bar']);

    // Retrieve the command bus service and pass your command in the execute()
    // method.
    Drupal::service('developer_suite.command_bus')->execute($command);

    // Initialize the batch manager and execute the attached commands.
    $this->batchManager
      // (optional) Set the batch page title.
      ->setTitle($this->t('My batch title'))
      // (optional) Set the initial message.
      ->setInitialMessage($this->t('Initializing my batch...'))
      // (optional) Set the progress message. Placeholders are supported.
      ->setProgressMessage($this->t('Processing @current of @total (@remaining remaining) (@percentage%).'))
      // (optional) Set the error message.
      ->setErrorMessage($this->t('An error occurred while processing my batch.'))
      // (optional) Set a static method finished callback.
      ->setFinishedCallback(static::class . '::myStaticFinishedCallback')
      // (optional) Or set a instance method finished callback.
      ->setFinishedCallback([$this, 'myFinishedCallback'])
      // Add static operations.
      ->addOperation(
        static::class . '::myStaticOperation', [
          'param1',
          'param2',
        ]
      )
      ->addOperation(
        static::class . '::myStaticOperation', [
          'param1',
          'param2',
        ]
      )
      ->addOperation(
        static::class . '::myStaticOperation', [
          'param1',
          'param2',
        ]
      )
      // Add operations.
      ->addOperation([$this, 'myOperation'], ['param1', 'param2'])
      ->addOperation([$this, 'myOperation'], ['param1', 'param2'])
      ->addOperation([$this, 'myOperation'], ['param1', 'param2']);

    try {
      // Execute the batch.
      $this->batchManager->execute();
    }
    catch (\Exception $exception) {
      drupal_set_message($exception->getMessage());
    }
  }

  /**
   * Example finished callback.
   *
   * @param bool $success
   *   Indicates if the batch succeeded.
   * @param array $results
   *   The batch results.
   * @param array $operations
   *   The unprocessed batch operations.
   */
  public function myFinishedCallback($success, array $results, array $operations) {

  }

  /**
   * Example operation.
   *
   * @param mixed $param1
   *   Parameter 1.
   * @param mixed $param2
   *   Parameter 2.
   */
  public function myOperation($param1, $param2) {

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Create a new validator passing the violation message, the form element
    // name and the form state.
    $validExampleInputA = new ExampleFormValidate('Error message!', 'example_value_a', $form_state);
    $validExampleInputB = new ExampleFormValidate('Error message!', 'example_value_b', $form_state);
    // Validate the validator passing the desired value and the violation
    // collection.
    $validExampleInputA->validate($form_state->getValue('example_value1'), $this->violationCollection);
    $validExampleInputB->validate($form_state->getValue('example_value1'), $this->violationCollection);

    // Retrieve the violations, loop through them and display the form errors.
    foreach ($this->violationCollection->getViolations() as $violation) {
      $form_state->setErrorByName($violation->getElement(), $violation->getMessage());
    }
  }

}
