# Terraform Module - KMS Key

This terraform module provisions the following resources:

- KMS key with a specified alias.
- IAM policy which allows following permissions with this key:
  - `kms:Encrypt`
  - `kms:Decrypt`
  - `kms:ReEncrypt*`
  - `kms:GenerateDataKey*`
  - `kms:DescribeKey`

## Usage

Use this module in your own project.

```hcl
module "kms" {
  source = "github.com/nicksantamaria/drupal-encrypt_kms//infracode/terraform"
  key_name = "your-key-alias"
  account_id = "00000000"
}
```
