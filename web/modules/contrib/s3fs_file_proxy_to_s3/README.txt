This module keep on stage_file_proxy, s3fs_file_proxy philosophy, but instead
download files to use in your local file system, is thought for staging and
preproductions environment that use S3 like production.

This module download the files from your production S3 bucket and then upload
to your pre production S3 bucket.