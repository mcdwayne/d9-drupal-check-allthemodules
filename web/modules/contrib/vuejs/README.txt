Vue.js Drupal module
--------------------

DESCRIPTION:
The module provides a bridge between Drupal and Vue.js framework.

CONFIGURATION:
Navigate to the admin/config/development/vuejs page and set up desired versions
of the libraries. Note if you prefer installing libraries locally you need to
install them after each version change. The following drush command can be used
to quckly download required Vue.js libraries.
`drush vuejs:download <LIBRARY_NAME>`

HOW TO USE:
1. You can use inside twig templates as usual, example: {{ attach_library('vuejs/vue') }}
2. You can attach it programmatically:

function MYMODULE_page_attachments(array &$attachments) {
  $attachments['#attached']['library'][] = 'vuejs/vue';
}

3. You can add it as dependency inside your *.libraries.yml:

  dependencies:
    - vuejs/vue
    - vuejs/vue_router
    - vuejs/vue_resource


PROJECT PAGE:
https://www.drupal.org/project/vuejs
