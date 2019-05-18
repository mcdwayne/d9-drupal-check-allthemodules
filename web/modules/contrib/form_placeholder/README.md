Installation
------------------
1. Install Form Placeholder (form_placeholder) module as usual.
2. Go to configuration page at "admin/config/user-interface/form-placeholder".
3. Specify CSS selectors for textfields you want to add a placeholder.


Usage
------------------
// Convert all children in given form
function MY_MODULE_form_FORM_ID_alter(&$form, &$form_state, $form_id) {
  $form['#form_placeholder'] = TRUE;
}

// Convert single form element
function MY_MODULE_form_FORM_ID_alter(&$form, &$form_state, $form_id) {
  $form['my_element']['#form_placeholder'] = TRUE;
}

It's also possible to convert form elements by classes:
1. form-placeholder-[include/exclude]-children
   Include/exclude all children of given class.
2. form-placeholder-[include/exclude]
   Include/exclude single element.
