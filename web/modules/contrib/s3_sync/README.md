# S3 Sync

## Description
This module is used to keep the local public files directory in sync with an S3 bucket. It will compare the local public files directory with the files in the S3 bucket, and it will make sure the S3 bucket is the same as the local directory. When enabled, it will update the bucket every time an entity is created, updated, or deleted.

## Set Up
First, you will need an S3 bucket. You can leave all the default settings, unless you intend to use the bucket's url as the public base url for your site's public files, then you'll need to make sure the bucket and the objects inside are public.

In order for this module to work, you need to make sure that you have an IAM user with the permissions to do ListBucket, DeleteObject, GetObject, and PutObject on the S3 bucket you wish to sync your public files to. Make sure the user has programmatic access, since you will need to specify your AWS Access Key and Secret Key in the settings.php file.

The keys are entered into the settings.php file like this:
```php
$settings['aws_access_key'] = '(Your access key here)';
$settings['aws_secret_key'] = '(Your secret key here)';
```

After that's done, you will need to go to the module's configuration under Configuration > Media > S3 Sync Settings, and put in the name of your bucket, and the region your bucket is in. 

You will also need to make sure you set the correct sync mode for your site. If no mode is specified, you won't be able to sync any of your files.
- **Single Instance Mode** - This mode will keep the S3 bucket up to date with your local public files directory. This means it will copy all files over that your S3 bucket doesn't have, and then it will delete all the files in the bucket that aren't in the local public files directory.
- **Multi Instance Mode** - This mode is useful if you have multiple instances of your site running at once, for example if you're load balancing between multiple EC2 instances. What this does is that every time the module transfers files, it will copy all the files in your public files directory to S3. Then it will copy everything from the S3 bucket back to the local directory. This is done so that if you have multiple site editors working at the same time on a site, one editor won't overwrite the files of another editor.

Once that's  all done, you're all set to go.

## Usage
Most of this module will be automatic. It will sync your public files to S3 on entity creates, updates, and deletes. You can however sync files immediately from the S3 Sync config menu under Configuration > Media > S3 Sync Settings. There should be a button that says 'Sync Files Now.' Make sure you have a sync mode set, otherwise this won't work. This way, you can sync your files without having to wait for the next entity update. **Note:** If you change your sync mode, make sure you save the configuration before pushing the 'Sync Files Now' button, otherwise it will sync files with the previous mode.

#### Drush Commands
This module comes with a few drush commands to help out when working from the console.
- `drush s3_sync:init (ss-init)` - This command is used to initialize the configuration of the module. It takes the options `bucket-name` `aws-region` and `mode`. Here's how you use them:
  - `--bucket-name=<bucket-name>` Sets the name of the S3 Bucket you'll be using. It's just the name, it doesn't need to be an ARN.
  - `--aws-region=<aws-region>` Sets the region that you're bucket is in. Use the format `us-west-2` for your regions. See the AWS documentation for the names of each of their regions.
  - `--mode=<mode>` Specify either `SINGLE` or `MUTLI`. This will set the sync mode to either be single instance mode or multi instance mode respectively.
- `drush s3_sync:get (ss-get)` - This will fetch a specified configuration item. You can specify `bucket_name` `aws_region` `mode` or `all`. Pass it in as an argument: `drush ss-get <config-item>`
- `drush s3_sync:set (ss-set)` - This will set a specified configuration item to a specified value. It takes two options, `config-item` and `value`. Pass the values in as arguments like this: `drush ss-set <config-item> <value>`
- `drush s3_sync:sync (ss-sync)` - This command will do the same thing as the "Sync Files Now" button in the config menu, and sync your files with your S3 bucket. Make sure the configuration is set, otherwise this will return an error.

#### Warning
This is a warning that applies if you are using the S3 bucket as the base public url for site's public files. When you flush your drupal caches, you may end up with new files in your public files directory on the next page reload, so it may be a good idea to go to the S3 File Proxy configuration to sync the files with your S3 bucket so your site doesn't break.

## Logs
This module will log most errors to the recent logs under the configuration menu. If you are having troubles with getting the module to work, make sure to check the logs.

## Future Development
- If you use the S3 url, or perhaps a CloudFront domain name as your public files base URL, it will break any other module that accesses and modifies the filesystem, specifically in the public files directory. This is because S3 isn't really a filesystem, since it's object based storage, and it doesn't have directories, just object keys. So what we need to do is find a way to give a way for those modules to interpret the object keys as file paths. This may be possible with module hooks.
- Currently this updates S3 on all entity creates, updates, and deletes, except configuration updates. We need to find out when it's actually necessary to update S3, since it's probably a little bit of overkill to update on every entity create, update, and delete.
