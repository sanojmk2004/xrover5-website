#!/bin/bash
# Pulls the latest website from GitHub and copies it into public_html.
# Run by a cPanel cron job.
# Copies every top-level item except the ones listed in SKIP, so new folders
# (like blog/) go live without editing this file.
# After a deploy, tells IndexNow search engines (Bing, Yandex, Seznam, Naver…) which pages changed.
# Everything is inside main() so bash reads the whole script before git replaces this file.
main() {
  REPO="$HOME/repositories/xrover5-website"
  SITE="https://xrover5.com"
  SKIP=" .git README.md deploy.sh _blog "
  cd "$REPO" || exit 1
  git fetch -q origin main || exit 1
  OLD=$(git rev-parse HEAD)
  if [ "$OLD" != "$(git rev-parse origin/main)" ] || [ "$1" = "--force" ] || [ ! -e "$HOME/public_html/blog" ]; then
    git reset -q --hard origin/main
    for item in * .htaccess; do
      case "$SKIP" in *" $item "*) continue ;; esac
      [ -e "$item" ] && cp -r "$item" "$HOME/public_html/"
    done
    indexnow "$OLD"
  fi
}

# Submit changed public pages to IndexNow (no-op if nothing changed or curl is missing).
indexnow() {
  command -v curl >/dev/null || return 0
  KEYFILE=$(ls 2>/dev/null | grep -E '^[0-9a-f]{32}\.txt$' | head -1)
  [ -n "$KEYFILE" ] || return 0
  KEY="${KEYFILE%.txt}"
  URLS=$(git diff --name-only "$1" HEAD 2>/dev/null \
    | grep -E '(^|/)index\.html$' | grep -vE '^(_blog|rebate)/' \
    | sed -E 's#index\.html$##; s#^#'"$SITE"'/#' | sort -u | head -1000)
  [ -n "$URLS" ] || return 0
  LIST=$(printf '%s\n' $URLS | sed 's/.*/"&"/' | paste -sd, -)
  curl -s -m 20 -o /dev/null -H 'Content-Type: application/json; charset=utf-8' \
    -d "{\"host\":\"xrover5.com\",\"key\":\"$KEY\",\"keyLocation\":\"$SITE/$KEYFILE\",\"urlList\":[$LIST]}" \
    https://api.indexnow.org/indexnow
}

main "$@"
exit
