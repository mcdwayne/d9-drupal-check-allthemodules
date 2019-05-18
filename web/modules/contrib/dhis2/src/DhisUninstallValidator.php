<?php

namespace Drupal\dhis;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class DhisUninstallValidator implements ModuleUninstallValidatorInterface
{
    use StringTranslationTrait;
    private $entityTypeManager;

    public function __construct(EntityTypeManager $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    public function validate($module)
    {
        $reasons = [];
        if ($module == 'dhis') {
            print('Uninstall DHIS2 in Progress');
            //$reasons[] = $this->t('To uninstall Book, delete all content that has the Book content type');
        }

        return $reasons;
    }

}