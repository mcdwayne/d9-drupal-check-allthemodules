<?php

namespace Drupal\drulma_companion\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class SubthemeGenerator extends BaseGenerator
{
    protected $name = 'd8:theme-drulma';
    protected $description = 'Generates a drulma subtheme.';
    protected $alias = 'drulma';
    protected $templatePath = __DIR__;
    protected $destination = 'themes';

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions['name'] = new Question('Theme name');
        $questions['name']->setValidator([Utils::class, 'validateRequired']);
        $questions['machine_name'] = new Question('Theme machine name');
        $questions['machine_name']->setValidator([Utils::class, 'validateMachineName']);
        $questions['description'] = new Question(
            'Description',
            'A theme using Bulma: a free, open source CSS framework based on Flexbox.'
        );

        $vars = &$this->collectVars($input, $output, $questions);

        $prefix = 'custom/' . $vars['machine_name'] . '/';

        $drulmaPath = DRUPAL_ROOT . '/' . drupal_get_path('theme', 'drulma') . '/';

        $this->addFile()
            ->path($prefix . '{machine_name}.info.yml')
            ->template('subdrulma.info.yml.twig');
        $this->addFile()
            ->path($prefix . '{machine_name}.theme')
            ->template('subdrulma.theme.twig');
        $this->addFile()
            ->path($prefix . '{machine_name}.libraries.yml')
            ->template('subdrulma.libraries.yml.twig');
        $this->addFile()
            ->path($prefix . 'css/overrides.css')
            ->template('css.overrides.css');
        $this->addFile()
            ->path($prefix . 'config/install/{machine_name}.settings.yml')
            ->content(file_get_contents(
                $drulmaPath . 'config/install/drulma.settings.yml'
            ));
        $this->addFile()
            ->path($prefix . 'config/schema/{machine_name}.schema.yml')
            ->content(str_replace('drulma.settings', $vars['machine_name'] . '.settings', file_get_contents(
                $drulmaPath . 'config/schema/drulma.schema.yml'
            )));
        $this->addFile()
            ->path($prefix . 'favicon.ico')
            ->content(file_get_contents(
                $drulmaPath . 'favicon.ico'
            ));
        $this->addFile()
            ->path($prefix . 'logo.svg')
            ->content(file_get_contents(
                $drulmaPath . 'logo.svg'
            ));

        // Copy all the optional configuration.
        $files = glob($drulmaPath . 'config/optional/*.yml');
        foreach ($files as $file) {
            $destinationFile = str_replace('block.block.drulma_', 'block.block.' . $vars['machine_name'] . '_', $file);
            $content = str_replace("drulma\n", $vars['machine_name'] . "\n", file_get_contents(
                $file
            ));
            $content = str_replace('id: drulma', 'id: ' . $vars['machine_name'], $content);
            $this->addFile()
               ->path($prefix . 'config/optional/' . basename($destinationFile))
               ->content($content);
        }

        // Templates directory structure.
        $this->addDirectory()
          ->path($prefix . 'templates/page');
        $this->addDirectory()
          ->path($prefix . 'templates/node');
        $this->addDirectory()
          ->path($prefix . 'templates/field');
        $this->addDirectory()
          ->path($prefix . 'templates/views');
        $this->addDirectory()
          ->path($prefix . 'templates/block');
        $this->addDirectory()
          ->path($prefix . 'templates/menu');

        $this->addDirectory()
          ->path($prefix . 'images');
    }
}
