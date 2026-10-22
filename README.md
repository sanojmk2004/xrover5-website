# XRover 5 website
Website for XRover 5 by XDemat. Changes pushed to main are copied to the live site by deploy.sh (cPanel cron).

## Blog
Articles live in `_blog/posts/`; see `_blog/GUIDE.md`. Build with `python3 _blog/build.py`.

## IB Rebate portal (`/rebate/`)
PHP + MySQL app at https://xrover5.com/rebate/ — customers open broker accounts through our IB links,
connect MT4/MT5 with the investor password, and get a % of the IB commission back in USDT.

**One-time setup on cPanel** (needs PHP 8.1+ with pdo_mysql, sodium, curl — check in cPanel > Select PHP Version):
1. cPanel > MySQL Databases: create a database and user, add the user to the database with ALL PRIVILEGES.
2. Generate a key in cPanel Terminal: `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`
3. Copy `rebate/inc/config.sample.php` to `/home/<cpanel-user>/rebate-config.php` (outside public_html) and fill in DB details + key. Back up the key.
4. Open https://xrover5.com/rebate/install.php and create the admin login (locks itself afterwards).
5. Admin > Brokers: set customer rebate % and estimated commission per lot for Octa and Switch Markets.
6. Optional lot tracking: add a MetaApi token in Admin > Settings, then a cPanel cron job every 30 min:
   `/usr/local/bin/php /home/<cpanel-user>/public_html/rebate/cron/sync.php >/dev/null 2>&1`

**Routine:** verify new accounts (Admin > Accounts) against the broker partner portal; import each broker's IB
commission CSV (Admin > Commissions) — that is what credits customer balances; send USDT for payout requests and paste
the TXID (Admin > Payouts).
