# sendwithus
[![Build Status](https://travis-ci.org/tuutti/sendwithus.svg?branch=8.x-1.x)](https://travis-ci.org/tuutti/sendwithus)

## Description

This modules integrates [Sendwithus](https://www.sendwithus.com/) with Drupal 8.

## Installation

`$ composer require drupal/sendwithus`

## Requirements

- php 7.1+
- Key (https://www.drupal.org/project/key)


## Usage

Configuration can be found from: Configuration -> Sendwithus (/admin/config/services/sendwithus)

This module provides only a minimal functionality by itself and you will most likely need custom code to actually benefit from this module.

### How to map sendwithus template to mail

This module provides two different ways to map template to a mail:

#### 1. Through the UI (/admin/config/services/sendwithus)

This should cover most cases.

See [examples](examples/) for examples.

#### 2. With custom template resolver

See `\Drupal\sendwithus\Resolver\Template\DefaultTemplateResolver` and `\Drupal\sendwithus\Resolver\Template\TemplateResolverInterface` for an example how to implement template resolver.

The idea behind this is that `::resolve` method will return `\Drupal\sendwithus\Template` object that contains used template and provides available variable replacements.

For example:

Define a collector service:

**yourmodule.services.yml**:
```yaml
yourmodule.template.your_custom_resolver:
  class: Drupal\yourmodule\Resolver\Template\YourTemplateResolver
  arguments: []
  tags:
    - { name: sendwithus.template.resolver, priority: 400 }
```

`YourTemplateResolver` must implement `\Drupal\sendwithus\Resolver\Template\TemplateResolverInterface` or extend the `\Drupal\sendwithus\Resolver\Template\BaseTemplateResolver` class.

Add required methods:

```php
public function resolve(Context $context) : ? Template {
  if (my_condition) {
    $template = new Template('my_template_id');
    // You can add custom variable replacements here as well.
    // This will be available as {{ my_custom_variable }} in your template.
    $template->setTemplateVariable('my_custom_variable', 'value for my custom variable');

    // No variable collection will be done by default, if you wish to use
    // variable collector, set your class to extend the BaseTemplateResolver
    // class and call parent::doCollectVariables($template, $context) method.
    return $template;
  }
}
```

## Variable replacement

This module provides the following variable replacements by default:

| Variable | Description | Examples |
|----------|-------------|----------|
| {{ mail.subject }} | Subject of the email to be sent | |
| {{ mail.body }} | Message to be sent | |
| {{ mail.key }} | A key to identify the email sent | password_reset |
| {{ mail.module }} | The module | user |
| {{ mail.id }} | The final message ID (`{{ mail.module }}_{{ mail.key}}`) | user_password_reset |
| {{ mail.langcode }} | Language code to use to compose the email | en |
| {{ site.name }} | Site name | Drupal |
| {{ site.slogan }} | Site slogan | Some slogan |
| {{ site.mail }} | Default email address | admin@example.com |
| {{ site.login_url }} | Login URL | http://example.com/user |
| {{ site.url }} | Site URL | http://example.com |
| {{ user.name }} | The unaltered login name of the account | admin |
| {{ user.display_name }} | The display name of the account |  |
| {{ user.mail }} | The email address of the account | admin@example.com |
| {{ user.edit_url }} |  | http://example.com/user/1/edit |
| {{ user.cancel_url }} |  | http://example.com/user/1/cancel |
| {{ user.reset_url }} | A unique URL that provides a one-time log in for the user, from which they can change their password | http://example.com/user/reset/1/xx/xx |

### Custom variable replacements

See `\Drupal\sendwithus\Resolver\Variable\SystemVariableCollector` and `\Drupal\sendwithus\Resolver\Variable\UserVariableCollector`.

To define a custom variable replacements, create a new `sendwithus.variable.collector` service:

**yourmodule.services.yml**:
```yaml
yourmodule.variable.your_custom_collector:
  class: Drupal\yourmodule\Resolver\Variable\CustomVariableCollector
  arguments: []
  tags:
    - { name: sendwithus.variable.collector, priority: 400 }
```

**src/Resolver/Variable/CustomVariableCollector.php**:

Must implement `\Drupal\sendwithus\Resolver\Variable\VariableCollectorInterface`.

Add required methods:

```php
public function collect(Template $template, Context $context) : void {
  // These will be available for every template.
  if (isset($data->get('params')['my_custom_entity'])) {
    // {{ custom_variable }}
    $template->setTemplateVariable('custom_variable', 'custom variable value');
    $template->setTemplateVariable('my_custom_entity', [
       // {{ my_custom_entity.id }}
      'id' => $entity->id(),
       // {{ my_custom_entity.label }}
      'label' => $entity->label(),
    ]);
  }
}
```
