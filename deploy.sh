#!/bin/bash
# Pulls the latest website from GitHub and copies it into public_html.
# Run by a cPanel cron job.
# Copies every top-level item except the ones listed in SKIP, so new folders
# (like blog/) go live without editing this file.
REPO="$HOME/repositories/xrover5-website"
SKIP=" .git README.md deploy.sh _blog "
cd "$REPO" || exit 1
git fetch -q origin main || exit 1
if [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ] || [ "$1" = "--force" ] || [ ! -e "$HOME/public_html/blog" ]; then
  git reset -q --hard origin/main
  for item in * .htaccess; do
    case "$SKIP" in *" $item "*) continue ;; esac
    [ -e "$item" ] && cp -r "$item" "$HOME/public_html/"
  done
fi
