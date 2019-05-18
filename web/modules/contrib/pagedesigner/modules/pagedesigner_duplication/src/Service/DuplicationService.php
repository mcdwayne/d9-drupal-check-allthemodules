<?php

namespace Drupal\pagedesigner_duplication\Service;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\pagedesigner\PagedesignerService;

class DuplicationService extends PagedesignerService
{

    public function duplicate(ContentEntityBase $source, ContentEntityBase $target)
    {
        $sourceContainer = $this->_getContainer($source);
        $targetContainer = $this->_getContainer($target);

        foreach ($sourceContainer->children as $item) {
            $clone = \Drupal::service('pagedesigner.service.statechanger')->copy($item->entity, $target)->getOutput();
            $clone->parent->entity = $targetContainer;
            $clone->langcode->value = $target->langcode->value;
            $targetContainer->children->appendItem($clone);
            $clone->save();
        }
        $targetContainer->save();

    }

}
