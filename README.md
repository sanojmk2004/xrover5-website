# XRover Tech website (xrover5.com)
Company website for XRover Tech — software products (XRover 5, AdClearX, TradingView → MT5 Connector) and custom software development.
Changes pushed to main are copied to the live site by deploy.sh (cPanel cron).

- Pages: index.html, products/, services/, contact/ (form → send.php), 404.html
- rebate/ — IB rebate portal: still works for existing users, hidden from menus and from search (X-Robots-Tag in .htaccess).
- _blog/ — archived blog sources (not deployed). /blog, /ml, /ta, /hi return 410 Gone.
