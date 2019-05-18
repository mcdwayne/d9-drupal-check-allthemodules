# EBoks integration Drupal 8 module

Module setting should be defined depends on sender in settings.php file.

NETS Share Example:
```
$config['eboks.nets'] = [
  // Sender corporate id.
  'corporateId' => '32342280',
  'country' => 'DK',
  'eBoksId' => '15723',
  // Shipment type id.
  'documentType' => '163057',
  // Connection settings to NETs share sFTP server.
  'sftp_host' => '91.102.24.144',
  'sftp_username' => 'tBELLCOM',
  'sftp_private_key' => '/var/www/eboks/id_rsa',
  'sftp_passphrase' => 'passphrase',
  // Test receviver id.
  'test_receiver' => '12345678',
  // Test ercevier type 'CPR' or 'CVR'.
  'test_receiver_type' => 'CPR',
];
```

MSOutlook Example:
```
$config['eboks.msoutlook'] = [
  'to' => 'indgaaende@prod.e-boks.dk',
  'from' => 'sender@example.com',
  // Test receviver id.
  'test_receiver' => '12345678',
  // Test ercevier type 'CPR' or 'CVR'.
  'test_receiver_type' => 'CPR',
];
```
