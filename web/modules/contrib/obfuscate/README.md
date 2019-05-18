# Obfuscate Email

Provides several email obfuscation methods that can be used through Field 
Formatter, text Filter, service container and Twig function.

## Installation

Use Composer.

`composer require drupal/obfuscate`

## Configuration

### System wide

The system wide configuration is used by the text filter, 
service container and the Twig extension.

The obfuscation method can be defined via
<em>Configuration > Content authoring > Obfuscate</em>
(/admin/config/obfuscate).

### Text format

Activate the obfuscation for the desired text formats ('Full HTML', ...).
<em>Configuration > Content authoring > Text formats and editors</em>,
then check <em>Email address obfuscation filter</em>.

### Per field

The field configuration inherits from the system wide configuration
as a default value and can be overridden per field instance.

### View modes

All the view modes (default/full, teaser, search index, ...) that
exposes publicly email addresses should define the Field Formatter to 
<em>Obfuscate</em>.

For some reasons, it should be preferred to leave the Field Formatter 
of several view modes to Plain text or Email. So, the decision of 
obfuscating is left to the discretion of the site builder.

## Service

Obfuscate can also be used from code via a service.

```
// Of course you will use dependency injection to get the service
$obfuscateMail = \Drupal::service('obfuscate_mail');
$mail = 'terry.jones@spam.com';
$build = [
  '#markup' => $obfuscateMail->getObfuscatedLink($mail),
];
```

### Service methods

**getObfuscatedLink**

Returns an obfuscated email link.

```
// Optional link parameters (html attributes) can be defined.
// If not overriden, provides the default rel="nofollow".
$params = ['class' => 'button'];
$obfuscateMail->getObfuscatedLink($mail, $params);
```

**obfuscateEmail**
Returns an obfuscated email link, it is used by the 
getObfuscatedLink method.

## Twig function

`{{ 'terry.jones@spam.com'|obfuscateMail }}`

## Browser support

@todo

## Roadmap

- Implement other methods of obfuscating email addresses
- Provide per field configuration for the obfuscation method
- Provide filter for WYSIWYG

## Related modules

@todo comparison

- [SpamSpan filter](https://www.drupal.org/project/spamspan)
