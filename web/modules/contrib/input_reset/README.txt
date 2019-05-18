INTRODUCTION
------------
This module enables input reset functionality on fields (Textfield, Email, 
Search and Password).

INSTALLATION
------------
Install as you would normally install a contributed Drupal module.

REQUIREMENTS
------------
There is no requirement for this module.

CONFIGURATION
-------------
The module has one configuration page admin/config/input_reset/inputrestsetting.
which is accessiable with permission "access administration pages".

To enable input reset, need to add from_id and field id combination with | 
separator.

    Example: 

    * To enable input reset on username field of user login form then
    combination will be user_login_form|edit-name
    * For reset your password form combination will be user_pass|edit-name

You can also override css of cross icon using below css

.clearable-input_div {
  position: relative;
  display: inline-block;
}
.clearable-input_div > input {
  padding-right: 1.4em;
}
.clearable-input_div > span.data-clear-input {
  position: absolute;
  top: 5px;
  right: 5px;
  line-height: 1em;
  cursor: pointer;
}
.clearable-input_div > input::-ms-clear {
  display: none;
}
span.data-clear-input {
  color: #fff;
  background: #ccc;
  border-radius: 50%;
  width: 7px;
  text-align: center;
  padding: 0px 5px 4px 5px;
}

TRUBLESHOOTING
--------------
* If this module don't work
    Please clear drupal cache.
    Please check browser console for any javascript error and fix it.

Author
------------------
Name: Rajveer gangwar
Email: rajveer.gang@gmail.com
Profile: https://www.drupal.org/u/rajveergang
