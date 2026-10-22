XRover 5 IB Rebates - MT5 lot sync on your Windows VPS
======================================================

One MT5 terminal per broker reads every customer account one after another
(log in with investor password -> read closed trades -> send lots to website).
Only MT5 accounts are tracked this way; MT4 lots come from broker IB reports.

1. Install on the VPS
   - Octa MT5 and Switch Markets MT5 (from each broker's website). Open each once.
   - Python 3 from python.org - tick "Add python.exe to PATH" on the first screen.
   - Open Command Prompt and run:   pip install MetaTrader5

2. Put the files in C:\xr5sync\
   - xr5_lot_sync.py, start-sync.bat, config.example.json
     (download from Admin > Settings > Live lot tracking)
   - Rename config.example.json to config.json

3. Edit config.json (right-click > Edit / Notepad)
   - token: from Admin > Settings > "Create sync token"
   - broker_id: shown on the same settings page (e.g. Octa = 1)
   - path: right-click each MT5 desktop shortcut > Open file location,
     copy the folder path, add \terminal64.exe. Use double backslashes \\ in JSON.

4. On the website: Admin > Settings > Live lot tracking > choose "Own VPS" > Save.

5. Test: double-click start-sync.bat. You should see each account with its lots.
   Keep the window open. To start it automatically after a VPS restart:
   Win+R > shell:startup > put a shortcut to start-sync.bat in that folder.

Log file: C:\xr5sync\sync.log
