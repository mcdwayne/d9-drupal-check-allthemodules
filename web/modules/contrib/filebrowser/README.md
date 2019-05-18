This module provides an interface for administrators to expose directories on the file system to users through a file listing as in a FTP application.

This module is useful when Drupal is an entire website and sits in the root
directory. All requests to the website are therefore passed to Drupal's
index.php and directory listings cannot be found even if they're enabled.
This module alleviates those problems by passing those directory listings
through Drupal, effectively.

This module offers the following features:
 - Private downloads so that files (such as PHP files) can be downloaded.
 - File blacklists so that specific files can be removed from directory listings.
 - Node-based. All features available to nodes, such as path aliasing and access control can now be applied to directory listings.
 - Settings to limit exploration of subdirectories.

## INSTALLING ##
There are no special requirements to install Filebrowser.

## CONFIGURATION ##
After installing and enabling the module:
1. Set the permission for the user roles. Per default there is no permission set.
2. The use of a private file system is recommended. You have to configure this in your setting.php file like explained [here](https://www.drupal.org/documentation/modules/file)
3. If you want to use remote file system such as AWS s3, you have to download and enable Flysystem module.
   This module is a drupal integration for the Symfony / The League abstracted file system component. You will also need to install an adapter for each filesystem you want to use. See further below and also the Flysytem project page.

## CHANGES COMPARED TO D7 VERSION ##
 - Multiple file upload
 - Folder renaming not allowed.
 - Different caching, see below

## CACHING ##
The file listing is cached. The cache will refresh automatically when the node is edited or an action (upload, rename etc) is performed on the listing. If you edit the directory (add, remove, rename files) directly on the file location, after you have created a node for this directory, you have to manually rebuild the cache to enable Filebrowser to rebuild the data.

## FILE PRIVACY ##
your file privacy, as far as Filebrowser is concerned, depends on two things:
1. File location
2. Download method

### File location ###
A public location is accessible by the web browser. Your files can be accessed outside of filebrowser if the user knows the location.
Please take note of Public Security Announcement 2016-03 that advises against granting upload permission for anonymous or non-trusted users to the public file directory: https://www.drupal.org/psa-2016-003
A private location is defined in your settings.php file. Files will be served by Drupal/PHP. Preferably locate your private directory outside the web root so it can not be accessed by the user outside of Filebrowser.

### Download method ###
Public download method directs the browser to the file location for download. PHP (Filebrowser) is not involved.
In case of Private download method, the files are served by filebrowser.

For more information consult the Filebrowser documentation [link]

## USING REMOTE FILE SYSTEMS. ##

Filebrowser by default uses the local filesystem.
Access to remote file systems provided by the drupal module flysytem. This module provides integration with Symfony FlySystem component.
You must also download the specific adapter of the service you want to use.

So to use AWS s3 and dropbox the following is needed:
1. download/install module flysystem
2. download/install module flysystem_s3
3. download/install module flysystem_dropbox

To be able to install the flysystem module you have to have access to the command line and composer.
Flysystem requires you to configure your clients in settings.php
Detailed instructions can be found on the flysytem project page, but here is a sample code to enable s3 and dropbox:

```
$schemes['s3'] = [
  'driver' => 's3',
  'config' => [
    'key'    => 'your_key',
    'secret' => 'your_secret',
    'region' => 'eu-west-1',
    'bucket' => 'your_bucket',

    #Optional configuration settings.
    #'options' => [
    #  'ACL' => 'public-read',
    #  'StorageClass' => 'REDUCED_REDUNDANCY',
    #],

  #'protocol' => 'https',
  #'prefix' => 'prefix',
  #'cname' => 'static.example.com',   // A cname that resolves to your bucket. Used for URL generation.
  ],

  'cache' => TRUE, // Creates a metadata cache to speed up lookups.
];

$schemes['dropbox'] = [
  'driver' => 'dropbox',
  'config' => [
    'token' => 'your token',
    'client_id' => 'me@example.com',
    # Optional.
    #'prefix' => 'a_directory'
    #'public' => TRUE,
  ],
];

# dont't forget this line
$settings['flysystem'] = $schemes;
```
After doing so you can point your Filebrowser node to your AWS s3 bucket by using the uri: s3://
And your dropbox by using the uri: dropbox://

## ICONS ##
 Icons provided by [Material Design Icons](https://materialdesignicons.com/) in .svg format.

 You can customize the icons by overriding the template file `filebrowser-icon-svg.html.twig` and/or
 overriding css:
 ```
 div.filebrowser-svg svg{
     fill: gray;
 }
 ```
 You can use your own icons by putting them in a directory named "filebrowser/icons" in your active theme. Rename the icons so they match the ones provided by Filebrowser.

## Developers D8 ##
### Removed hooks ###
Deleted hook to add form actions.
