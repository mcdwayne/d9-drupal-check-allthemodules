<?php

namespace Drupal\formazing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\formazing\Entity\FieldFormazingEntity;
use Drupal\formazing\Entity\FormazingEntity;
use Drupal\formazing\Entity\ResultFormazingEntity;
use Drupal\formazing\FieldSettings\FieldInterface;
use Drupal\formazing\FieldSettings\TextField;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormazingController extends ControllerBase {

  /**
   * @param $formazing_id
   *
   * @return JsonResponse
   */
  public function export($formazing_id) {
    $results = \Drupal::entityQuery('field_formazing_entity')
      ->execute();

    $results = FieldFormazingEntity::loadMultiple($results);

    $fields = array_filter($results, function (FieldFormazingEntity $field) use ($formazing_id) {
      return $field->getFormId() === $formazing_id;
    });

    $fields = array_map(function (FieldFormazingEntity $field) {
      return $field;
    }, $fields);

    $fields = array_map(function (FieldFormazingEntity $field) {
      /** @var TextField $type */
      $type = $field->getFieldType();
      return [
        'id' => $field->id(),
        'langcode' => $field->language()->getId(),
        'name' => $field->getName(),
        'machine_name' => $field->getMachineName(),
        'description' => $field->getDescription(),
        'default_value' => $field->getFieldValue(),
        'placeholder' => $field->getPlaceholder(),
        'weight' => $field->getWeight(),
        'type' => $type::getMachineTypeName(),
        'form_id' => $field->getFormId(),
        'required' => $field->isRequired(),
        'show_label' => $field->isShowingLabel(),
        'options' => $field->get('field_options')->getValue(),
        'options_title' => $field->get('field_options_title')->getValue(),
      ];
    }, $fields);

    return new JsonResponse([
      'data' => [
        'fields' => $fields,
      ],
    ]);
  }

  /**
   * @param Request $request
   *
   * @return JsonResponse|Response
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function postForm(Request $request) {
    $data = json_decode(\Drupal::request()->getContent());

    if (!$data->data) {
      return new JsonResponse([
        'data' => [
          'message' => t('There are no datas'),
          'success' => FALSE,
        ],
      ]);
    }
    $data = $data->data;
    $formazing_id = $data->form_id;
    $formazing = FormazingEntity::load($formazing_id);

    if (!$formazing) {
      return new JsonResponse([
        'data' => [
          'message' => t('Cannot find formazing with id: @formazing_id', ['@formazing_id' => $formazing_id]),
          'success' => FALSE,
        ],
      ]);
    }

    $entity = ResultFormazingEntity::create([
      'form_type' => $formazing->id(),
      'name' => $formazing->getName(),
      'data' => json_encode($data->fields),
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
    ]);

    $entity->save();

    return new JsonResponse([
      'data' => [
        'message' => t('Form has been sent with success.'),
        'success' => TRUE,
      ],
    ]);
  }
}
