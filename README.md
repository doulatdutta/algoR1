## Upstox Algo Trading Platform (TradingView Webhook → Options Orders)

This project is a web-based algo trading platform built in PHP to connect TradingView alerts with the Upstox API.
It places market orders on ATM NIFTY options with configurable stoploss and profit-lock (target).

##  🚀 Features

Dashboard with multiple tabs:

Overview → Summary of latest trades & webhook activity

Settings → Configure API keys, quantity, stoploss %, target %, secrets

Logs → View all webhook requests and order actions

Tools → Sync Upstox instruments, clear logs, test webhooks

TradingView → Webhook integration

ATM option finder (NIFTY)

Market entry orders with:

SL-M stoploss order

LIMIT target order

JSON-backed configuration (editable via UI)

Test Mode (safe simulation, no live trades)

## 📂 Project Structure
/public
  ├─ index.php          → Dashboard UI
  ├─ webhook.php        → TradingView webhook endpoint
  ├─ save_settings.php  → Save settings form
  ├─ tools.php          → Utility actions (sync instruments, clear logs)
  └─ assets/styles.css  → Dashboard theme

/src
  ├─ config.php         → Load/save settings
  ├─ helpers.php        → Logger & utilities
  ├─ upstox.php         → Upstox REST API client
  └─ instruments.php    → ATM strike finder (via Upstox master.csv)

/storage
  └─ config.json        → Stores saved settings

/logs
  ├─ webhook.log
  ├─ orders.log
  └─ app.log

## ⚡ Installation
1. Localhost (XAMPP)

Copy the project folder into C:\xampp\htdocs\algo

Start Apache (and MySQL if you need later)

Open: http://localhost/algo/public/

2. cPanel / Web Hosting

Upload the zip to your hosting account

Extract it inside public_html/algo/

Access: https://yourdomain.com/algo/public/

## 🔑 Setup

Go to the Settings tab and fill in:

API Key

API Secret

Access Token

Quantity, Stoploss %, Target %

Webhook secret

Mode: Test/Live

Save settings (creates/updates /storage/config.json)

Go to Tools → Sync Instruments
This downloads master.csv (Upstox instruments list) to /storage/.

## 📡 TradingView Setup

Create an alert in TradingView (e.g., EMA crossover).

Webhook URL:

http://localhost/algo/public/webhook.php


(replace localhost with your cPanel domain later)

Webhook message (example):

{ "secret": "secret123", "action": "BUY_CALL", "expiry": "weekly", "qty": 1 }


or

{ "secret": "secret123", "action": "SELL_PUT", "expiry": "weekly", "qty": 1 }


Alerts will be processed instantly and orders sent to Upstox.

📊 Logs

Check the Logs tab or /logs/*.log:

webhook.log → Incoming TradingView alerts

orders.log → Order placements

app.log → Errors & general events

⚠️ Important Notes

Always test in Test Mode first (orders are simulated).

Ensure master.csv is refreshed daily (use Tools → Sync Instruments).

Adjust upstox.php if your account requires instrument_key instead of instrument_token.

This version only supports ATM NIFTY Options (expandable later).

🛠️ Next Features (Roadmap)

Show open positions in Dashboard

Order status polling

Multi-strategy routing (different secrets)

BankNifty/Finnifty support

💡 Built with ❤️ in PHP for algo traders.