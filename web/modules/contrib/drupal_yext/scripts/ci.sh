#!/bin/bash
#
# Run tests on Circle CI.
#
set -e

./scripts/test.sh
./scripts/deploy.sh
