📈 Algo Trading Dashboard

A PHP + Bootstrap (dark theme) based dashboard for managing and monitoring algorithmic trading strategies.
Supports strategy creation, paper/live trading modes, signal monitoring, live positions, and trade summaries, with file-based JSON storage (MySQL integration can be added later).

🚀 Features
🔐 Strategy Management

Create, edit, clone, and delete strategies

Unique webhook URL generated for each strategy

Strategy parameters:

Stoploss

Trailing Stoploss

Profit Booking

Trailing Profit Booking

Activate in Paper Trade or Live Trade mode

📡 Signal Processing

Accepts webhooks in JSON format:

{ "date": "{{timenow}}", "action": "BUY" }


Supported actions:

"BUY" → Buy Call

"SELL" → Sell Call

"SHORT" → Buy Put

"COVER" → Sell Put

📊 Dashboard

Overview of all strategies and their status

Live Nifty50, Sensex, BankNifty, and FinNifty data (via Upstox API)

Quick access to paper/live trades

📑 Signals & Orders

Signals Tab – list of all incoming signals per strategy

Orders Tab – list of all executed orders (paper + live)

Positions Tab – open live/paper trades across strategies

Daily Summary Tab – P&L for the day

📈 Market Data (Planned / Optional)

Options Chain view

Live stock data & charts

Overlay of executed orders and stoploss trails on charts

⚡ Tech Stack

PHP (vanilla)

Bootstrap 5 (Dark Theme)

JSON-based storage (easy migration to MySQL later)

Modular file structure

📂 Project Structure
algo/
 ├─ public/
 │   ├─ index.php          # Dashboard home
 │   ├─ dashboard.php      # Overview
 │   ├─ strategies.php     # Strategy management
 │   ├─ signals.php        # Signal list
 │   ├─ settings.php       # Settings page
 │   ├─ css/
 │   │   └─ style.css      # Custom dark theme styles
 │   ├─ js/
 │   │   └─ app.js         # Client-side logic
 │   └─ vendor/
 │       ├─ bootstrap.min.css
 │       └─ bootstrap.bundle.min.js
 ├─ src/
 │   ├─ helpers.php        # Utility functions
 │   ├─ router.php         # Simple PHP router
 │   └─ storage.php        # File-based storage handler
 ├─ data/
 │   ├─ strategies.json    # Saved strategies
 │   ├─ signals.json       # All incoming signals
 │   └─ orders.json        # Placed orders (paper/live)
 └─ config.php             # Config settings

⚙️ Installation

Clone or download this repo into your htdocs (XAMPP) or web root.

C:\xampp\htdocs\algo\


Start Apache (XAMPP Control Panel).

Visit:

http://localhost/algo/public/

🔑 Usage

Create Strategy

Go to Strategies Tab

Define parameters (Stoploss, Profit Booking, etc.)

Choose Paper or Live mode

Webhook URL

Each strategy has a unique webhook URL (generated automatically)

Send signals in JSON format:

{ "date": "2025-08-23 10:15:00", "action": "BUY" }


Monitor Signals & Orders

Check Signals Tab for all webhook signals

Orders will show up in Orders Tab

Active trades shown in Positions Tab

Daily results in Summary Tab

📌 Roadmap

 Add authentication & user roles

 Switch storage from JSON → MySQL

 Full Upstox API integration (login + live market data + order placement)

 Chart integration with live order overlays

 Telegram/Email notifications

🛠️ Developer Notes

Default setup uses JSON file storage for easy local testing

For production:

Migrate storage to MySQL

Secure routes with authentication

Deploy on server (Apache/Nginx + PHP 8)

📄 License

MIT License – free to use and modify.
