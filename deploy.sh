#!/bin/bash
# Pulls the latest website from GitHub and copies it into public_html.
# Run by a cPanel cron job every few minutes.
REPO="$HOME/repositories/xrover5-website"
cd "$REPO" || exit 1
git fetch -q origin main || exit 1
if [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ] || [ "$1" = "--force" ]; then
  git reset -q --hard origin/main
  cp -r index.html send.php .htaccess assets "$HOME/public_html/"
fi
