# File Encrypt

## Contents of This File

- [Introduction](#introduction)
- [Installation](#installation)
- [Encrypting file metadata](#encrypting-file-metadata)
- [Architecture](#architecture)


## Introduction

The file encrypt module allows you to encrypt files uploaded via Drupal using
the [Encrypt](https://www.drupal.org/project/encrypt) and
[Key](https://www.drupal.org/project/key) modules. When encrypted files are
requested, they will be decrypted automatically.


## Installation

Optionally provide a different path in `settings.php` where encrypted files
should be stored:

```
$settings['encrypted_file_path'] = 'sites/default/files-encrypted';
```

Enable file encryption on a field-by-field basis on their respective "Field
settings" pages by selecting "Encrypted files" as the upload destination.


## Encrypting file metadata

To encrypt metadata like title and description use the
[Field Encryption](https://www.drupal.org/project/field_encrypt) module.


## Architecture

The module architecture revolves around a stream wrapper that provides an
`encrypt://` stream and applies filters that encrypt/decrypt the data that
passes through it. URLs take the form
`encrypt://{encryption_profile}/{path/to/file.ext}`, e.g.,
`encrypt://my_profile/images/druplicon.png`. The module also provides a route at
which decrypted files can be accessed according to field permissions, much like
private files.
