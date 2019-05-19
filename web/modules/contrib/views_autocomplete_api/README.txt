This module "Views autocomplete Api" allow to view how we can display list of  views via autocompleate textfield.

NB:
When creating a view which pointing to a textfield with autocomplete,if number of fields in view is greater strict than 1,
So , the last must be the value(the result of the search), and the last last must be the key(the word which we search for).

*** There is 2 steps to create an autocomplete field:

1/ Create a view via Structure->views->Add new view.
2/ Alter a field via  hook_form_alter in .module.
or
2/ Create an custom field with autocompletion.

// To make it autocomplete, here is an example.

  $form['example_autocomplete'] = array(
    '#type' => 'textfield',
    '#autocomplete_route_name' => 'views_autocomplete_api',
    '#autocomplete_route_parameters' => array('view_name' => $view_name)
    );

 Optional :

 You can add one or multiple views, then you an set display and views arguments for each views. You must send in the
 same order of views list.

 For example :
    $view_name='my_view_1,my_view2';
    $display_id='default,block_1';
    $views_arguments='args1+arg2,arg3';

   $form['example_autocomplete'] = array(
     '#type' => 'textfield',
     '#autocomplete_route_name' => 'views_autocomplete_api',
     '#autocomplete_route_parameters' => array('view_name' => $view_name,'display_id'=>$display_id,'views_arguments'=>$views_arguments)
     );

Note :

If you use ad display block for views, you need to activate ajax option to be able to filter on exposed filter (like in normal views display block).
