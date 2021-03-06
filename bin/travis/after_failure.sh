#!/usr/bin/env bash

# NAME
#     after_failure.sh - Display debugging information in case of build failure.
#
# SYNOPSIS
#     after_failure.sh
#
# DESCRIPTION
#     Displays Drupal error log.

cd "$(dirname "$0")" || exit; source _includes.sh

if [[ -f "$ORCA_FIXTURE_DIR/vendor/bin/drush" && "$(drush core-status --field=bootstrap)" ]]; then
  drush watchdog:show --count=100 --severity=Error --extended
fi
