# CUSTOM CONFIGURATIONS PLUGIN
  
## INTRODUCTION  
  
This module categorized as a developer module, provides a plugin type for  
implementing forms of custom translatable configurations. Also, with it, you  
can centralize your all custom module configurations in one place and in general  
to ease the creation process of such forms.

## REQUIREMENTS

There are no special requirements.
  
## INSTALLATION  
  
Install the module as you would normally install a contributed Drupal module.  
Visit: https://www.drupal.org/node/1897420 for directions for installing.  
  
Visit: https://www.drupal.org/project/custom_configurations/git-instructions  
for cloning the project repository.
  
## CONFIGURATION  
  
To add a custom configuration plugin you can use example placed in:  
*src/Plugin/CustomConfigurations/ExampleConfigPlugin.php*

To retrieve saved data you can use the CustomConfigurationsManager service:

    $cc_manager = \Drupal::service('custom_configurations.manager');
    // To get values saved to the configuration file.
    $cc_manager->getFileConfig($plugin_id, $var_name, $language);
    // To get values saved to the data base.
    $cc_manager->getDbConfig($plugin_id, $var_name, $language);
  
## MORE INFORMATION  
  
The supporting organization is [BUZZWOO!](https://www.drupal.org/buzzwoo)
  
## MAINTAINERS  
  
[nortmas](https://www.drupal.org/u/nortmas)
