# Changelog

## Changes in dev

## 8.x-1.0-rc4

- Improved readme [#2994630](https://www.drupal.org/project/contact_tools/issues/2994630)*
- Improved code quality according to Drupal Coding Standards.
- Added help page based on README.md file.
- System messages moved from form alter to AJAX. Because they also shows other messages if set to form by default.


## 8.x-1.0-rc3

**Warning!** This release can break your form theming and AJAX will work incorrectly for multiple instance of the same form on the same page, including called via modal because of new static selector.

This done because current bug is much harder to fix. If there an AJAX element, and it was used, main AJAX for form called, but not replace old form, because build_id_old, on which AJAX previously rely, is changed by AJAX element. e.g. filed filed on AJAX form.

If you need more than one instance of the same form on a single page, clone it, it's faster, better and much reliable. And this can have some other benefits, for example, you can now 100% sure which form was submitted, first, or second and use it e.g. for Google Analytics Events.

TLDR:
- If you form theming is broken. Rollback to rc2 and lock it via composer, or update CSS.
- You you form submitted, but system messages shows on other form, that means you using two instance of the same form. As well, rollback and lock rc2, or create clone of this contact form.

## 8.x-1.0-rc2

- Improved code quality.
- Added ability to pass data to form for `getForm()`, `getFormAjax()` and their Twig functions. Also added example how to use it.
- Fix documentation example for readthedoc.

## 8.x-1.0-rc1

- Fix: TypeError: Argument 2 passed to HOOK_contact_tools_modal_link_options_alter() must be of the type array, null given.
- Replaced deprecated drupal messages functions with object.
- AJAX wrapper container now has suffix `-ajax-wrapper` for all inherited classes. It will make theming easier without class duplicates.
