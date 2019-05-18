# Eloqua API Usage Examples
## Contact

### Create a New Contact
Example:
```
$contactService = \Drupal::service('eloqua_api_redux.contact');
$newContact = $contactService->dummyContact();

$newContact['title'] = 'Mr';
$newContact['emailAddress'] = 'dakku+singh@example.org';
$newContact['firstName'] = 'Dakku';
$newContact['lastName'] = 'Singh';

$contact = $contactService->createContact($newContact);
```

### Update a Contact
Example:
```
$contactService = \Drupal::service('eloqua_api_redux.contact');

// ID and EMAIL are required fields.
$contact['id'] = 1234;
$contact['emailAddress'] = 'dakku+singh@example.org';
$contact['title'] = 'Mr';
$contact['firstName'] = 'Dakku';
$contact['lastName'] = 'Singh';

$updated = $contactService->updateContact($contact['id'], $contact);
```

### Lookup Contact by Email Address
Example:
```
$contactService = \Drupal::service('eloqua_api_redux.contact');
$email = 'dakku+singh@example.org';
$contact = $contactService->getContactByEmail($email);
```

### Lookup Contact by Contact ID
Example:
```
$contactService = \Drupal::service('eloqua_api_redux.contact');
$id = 1234;
$contact = $contactService->getContactById($id);
```

### Lookup Contacts by Params
Example:
```
$contactService = \Drupal::service('eloqua_api_redux.contact');
$queryParams['search'] = 'dakku+singh@example.org';
$contact = $contactService->getContacts($queryParams);
```

### Delete a Contact
Example:
```
$contactService = \Drupal::service('eloqua_api_redux.contact');
$id = 1234;
$contact = $contactService->deleteContact($id);
```

## Forms

### Create a Form Submission
Example:
```
$contactService = \Drupal::service('eloqua_api_redux.forms');

$formData = [
  'fieldValues' => [
    [
      'type' => 'FormField',
      'id' => '724',
      'value' => 'dakku+singh123@example.org',
    ],
    [
      'type' => 'FormField',
      'id' => '726',
      'value' => 'Dakku 123',
    ],
    [
      'type' => 'FormField',
      'id' => '727',
      'value' => 'Singh 123',
    ],
  ]
];

$submission = $contactService->createFormData(71, $formData);
```
