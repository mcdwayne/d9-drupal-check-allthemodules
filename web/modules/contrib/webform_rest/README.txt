Webform REST 2.x

Retrieve and submit webforms via REST.

1. Enable module
2. Enable REST resource "Webform Submit"
3. Enable REST resource "Webform Elements"
4. Enable REST resource "Webform Fields"
5. Enable REST resource "Webform Submission"

Retrieve Webform Elements
-------------------------

Returns all form elements including render array.

GET /webform_rest/{webform_id}/elements?_format=json

Retrieve Webform Fields
-----------------------

Returns form fields.

GET /webform_rest/{webform_id}/fields?_format=json

Submit Webform
--------------

POST /webform_rest/submit

Example POST data:

{
  "webform_id": "my_webform",
  "checkboxes_field": [
    "Option 3",
    "Option 5"
   ],
   "integer_field": 3,
   "radio_field": "Mail",
   "email": "myemail@mydomain.com.au"
}

Update Webform Submission
-------------------------

PATCH /webform_rest/{webform_id}/submission/{sid}?_format=json

Example PATCH data:

{
  "checkboxes_field": [
    "Option 3",
    "Option 5"
   ],
   "integer_field": 3,
   "radio_field": "Mail",
   "email": "myemail@mydomain.com.au"
}

Retrieve Webform Submission
---------------------------

GET /webform_rest/{webform_id}/submission/{sid}?_format=json
