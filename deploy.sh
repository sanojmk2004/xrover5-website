#!/bin/bash
# Pulls the latest website from GitHub and copies it into public_html.
# Run by a cPanel cron job.
REPO="$HOME/repositories/xrover5-website"
cd "$REPO" || exit 1
git fetch -q origin main || exit 1
if [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ] || [ "$1" = "--force" ]; then
  git reset -q --hard origin/main
  for item in index.html products services contact assets send.php .htaccess sitemap.xml robots.txt; do
    [ -e "$item" ] && cp -r "$item" "$HOME/public_html/"
  done
fi
