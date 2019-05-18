**Formazing**

Formazing is a light module that gives you the possibility to add quickly custom forms in your content as field and save all submitted forms.

_Setup_

1. Enable the formazing module
2. Launch the entity update (drush entup -y)
3. Go to /admin/structure/formazing_entity and create your forms
4. Add the formazing field into your content type, paragraph type or whatever you want
5. Create/Edit your content and select your form
6. Enjoy

`If you want to do some more things on submitted forms, you can handle it with a hook_formazing OR hook_formazing_FORM-ID`


Front-end:

If you use a framework like React, Vue, ... You can get the JSON of the form's structure in the formazing list, in 
the operations column, select the "Json export".

On form's submit, send data to this endpoint:

`/formazing/{FORMAZING_ID}/json/post`

The body must respect this structure, here is a sample:

```
{
	"data": {
		"fields": {
			"0": {
			    "label": "Textfield - Firstname",
				"value": "Theodoros",
				"type": "textfield"
			},
			"1": {
				"label": "Textield - Lastname",
				"value": "Suliotis",
				"type": "textfield"
			},
			"2": {
				"label": "Checkbox - I am cool ?",
				"value": "1",
				"type": "checkbox"
			},
			"3": {
				"label": "Select - hobbys",
				"value": "Football",
				"type": "textfield"
			},
			"4": {
				"label": "Checkboxes - hobbys",
				"value": "Football || Badminton",
				"type": "textfield"
			},
			"5": {
				"label": "Textarea - message",
				"value": "I think it's amazing to use formazing",
				"type": "textfield"
			}
		},
		"form_id": 5
	}
}
```