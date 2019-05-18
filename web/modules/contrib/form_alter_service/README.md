# Form Alter Service

Would you like to define well-known form alters as services? This project for you. From ID, base ID and prioritization supported.

## General recommendations

- [Install this module only via Composer](https://www.drupal.org/node/2718229#managing-contributed) since it has [a third-party library](https://github.com/BR0kEN-/reflection-validator) in dependencies which will not be downloaded in any other way and you will get not working solution.
- Try to avoid usage of this utility in your contributed projects since it may discourage and/or break the behavior of Drupal. The most suitable area for usage - custom code.

## Usage

### How to deny particular service from being used?

Implement the `hasMatch()` method in your service and decide there whether service is applicable.

### How to define validation/submission handlers?

This is achievable with annotations: `@FormValidate` and `@FormSubmit`. Using one of them you can declare a **public** method of your service to be a handler.

Please note, that every handler can be configured via annotation properties. If you want to add it to the beginning of the handlers list, set the `strategy` to `prepend` (`append` by default). Moreover, you can define a `priority` using self-titled property.

### Isolated calculation of priorities

**IMPORTANT:** Remember that priority calculation of handlers occurs for every service separately. This means that you cannot put your handler before/after one from another service in case its priority is higher/lower.

### Handlers invocation

**IMPORTANT:** Sometimes you might found out that validate/submit handlers are not executing. The reason of this is an architecture of Drupal. If a clicked button within the form contains its own `#validate` and/or `#submit` list of handlers it will mean that all global will be ignored. To deal with such cases you need to put your own handlers programmatically (in `alterForm()` method) for necessary buttons.

### Programmatic definition of a service

`my_module.services.yml`

```yml
services:
  form_alter.node_form:
    class: Drupal\my_module\NodeFormAlter
    arguments:
      # Base form ID. An exact form ID could be used as well. To compute availability in runtime use "match" special keyword.
      - 'node_form'
    tags:
      - name: form_alter
        # Non-required property (0 by default).
        priority: 4
```

```php
namespace Drupal\my_module;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_alter_service\FormAlterBase;

/**
 * {@inheritdoc}
 */
class NodeFormAlter extends FormAlterBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * @FormValidate(
   *   priority = -5,
   *   strategy = "append",
   * )
   */
  public function nameOfValidateHandler1(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * @FormValidate(
   *   priority = 1,
   *   strategy = "append",
   * )
   */
  public function nameOfValidateHandler2(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * @FormSubmit(
   *   strategy = "prepend",
   * )
   */
  public function nameOfSubmitHandler1(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * @FormSubmit()
   */
  public function nameOfSubmitHandler2(array $form, FormStateInterface $form_state) {
  }

}
```
