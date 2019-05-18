<?php
namespace Drupal\pagedesigner_webform\Plugin\pagedesigner\Handler;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\pagedesigner\Definition\PatternDefinition;
use Drupal\pagedesigner\Entity\Element;
use Drupal\pagedesigner\Plugin\HandlerPluginBase;
use Drupal\webform\Entity\Webform as Form;

/**
 * @PagedesignerHandler(
 *   id = "webform",
 *   name = @Translation("Webform handler"),
 *   types = {
 *      "webform",
 *      "form"
 *   },
 * )
 */
class Webform extends HandlerPluginBase
{
    protected $definition = [
        "id" => 'webform:',
        "pagedesigner" => 1,
        "icon" => "far fa-edit",
        "type" => "webform",
        "category" => 'Forms',
        "label" => '',
        "description" => '',
    ];

    public function collectAttachments(&$attachments)
    {
        parent::collectAttachments($attachments);
        $attachments['library'][] = 'pagedesigner_webform/pagedesigner';
    }

    /**
     * {@inheritDoc}
     */
    public function collectPatterns(&$patterns)
    {
        $formIds = \Drupal::entityQuery('webform')->execute();
        foreach ($formIds as $formId) {
            if (strpos($formId, 'example_') !== 0 && strpos($formId, 'template_') !== 0) {
                $form = Form::load($formId);
                // print_r($form->toArray());
                if ($form != null) {
                    $definition = $this->definition;
                    $definition['id'] .= $formId;
                    $definition['webform'] = $formId;
                    $build = [
                        $formId => [
                            '#type' => 'webform',
                            '#webform' => $formId,
                        ],
                    ];
                    $markup = \Drupal::service('renderer')->renderPlain($build);
                    $markup = '<div data-gjs-type="webform">' . $markup . '<div>';
                    $definition['markup'] = $markup;
                    $definition['label'] = (string) $form->label();
                    $patterns[$definition['id']] = new PatternDefinition($definition);
                }
            }

        }
    }

    public function render(Element $entity)
    {
        return [
            '#type' => 'webform',
            '#webform' => $entity->field_webform->target_id,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function renderForEdit(Element $entity)
    {
        $build = $this->render($entity);

        $markup = \Drupal::service('renderer')->render($build);

        return [
            '#type' => 'inline_template',
            '#template' => '<div data-gjs-type="webform" data-entity-id="{{id}}" id="{{html_id}}">{{markup}}</div>',
            '#context' => ['markup' => ['#markup' => Markup::create($markup)], 'id' => $entity->id(), 'html_id' => 'pd-cp-' . $entity->id()],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function generate($patternDefinition, $data)
    {
        $build = [];
        $build['type'] = $type = $patternDefinition->getAdditional()['type'];

        $formId = $patternDefinition->getAdditional()['webform'];
        $form = Form::load($formId);
        if ($form == null) {
            throw new BadRequestHttpException('The given form does not exist.');
        }
        $element = Element::create(['type' => 'webform', 'name' => 'webform']);
        $element->field_webform->entity = $form;
        $element->parent->target_id = $data['parent'];
        $element->container->target_id = $data['container'];
        $element->saveEdit();

        // Collect return data
        $build['id'] = $element->id();

        // Adding entity to parent cell if provided
        $cell = Element::load($element->parent->target_id);
        $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $cell = $cell->getTranslation($language);
        if ($cell != null) {
            $cell->children->appendItem($element);
            $cell->saveEdit();
        }

        return $build;

    }
}
