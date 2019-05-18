output "iam_policy_arn" {
  value = "${aws_iam_policy.kms.arn}"
}

output "kms_key_arn" {
  value = "${aws_kms_key.key.arn}"
}
