import logging
import requests
import time
import threading
from telegram import Bot
import os
from flask import Flask

TOKEN = os.getenv("BOT_TOKEN")
CHANNEL_ID = os.getenv("CHANNEL_ID")
PORT = int(os.getenv("PORT", 8080))  # default port for Render

bot = Bot(token=TOKEN)
logging.basicConfig(level=logging.INFO)

app = Flask(__name__)

@app.route("/")
def home():
    return "✅ Quote Bot is running!"

QUOTE_API = "https://quotes-api-w4zt.onrender.com/quotes/1"

def fetch_quote():
    try:
        res = requests.get(QUOTE_API)
        if res.status_code == 200:
            data = res.json()
            quote = data.get("quote", "No quote found.")
            author = data.get("author", "Unknown")
            return f"""✨ Inspirational Quote

╔〇══════════════════════〇
║  {author} ✍️
╚〇══════════════════════〇

❝ {quote} ❞

〇━━━━━━━━━━━━━━━━━━━━━━━〇
📌 ❖ ARSYNOX 
〇━━━━━━━━━━━━━━━━━━━━━━━〇"""
        else:
            return None
    except Exception as e:
        logging.error(f"Error fetching quote: {e}")
        return None

def send_startup_message():
    try:
        bot.send_message(chat_id=CHANNEL_ID, text="🚀 *Quote Bot Started Successfully!*", parse_mode="Markdown")
        logging.info("Startup message sent.")
    except Exception as e:
        logging.error(f"Failed to send startup message: {e}")

def quote_loop():
    send_startup_message()
    while True:
        quote_msg = fetch_quote()
        if quote_msg:
            try:
                bot.send_message(chat_id=CHANNEL_ID, text=quote_msg)
                logging.info("Quote sent successfully.")
            except Exception as e:
                logging.error(f"Failed to send quote: {e}")
        time.sleep(300)  # 5 minutes

if __name__ == "__main__":
    threading.Thread(target=quote_loop).start()
    app.run(host="0.0.0.0", port=PORT)
