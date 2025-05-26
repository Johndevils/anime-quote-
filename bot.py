import logging
import requests
import time
from telegram import Bot
import os

TOKEN = os.getenv("BOT_TOKEN")
CHANNEL_ID = os.getenv("CHANNEL_ID")

bot = Bot(token=TOKEN)
logging.basicConfig(level=logging.INFO)

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

def main():
    while True:
        quote_msg = fetch_quote()
        if quote_msg:
            try:
                bot.send_message(chat_id=CHANNEL_ID, text=quote_msg)
                logging.info("Quote sent successfully.")
            except Exception as e:
                logging.error(f"Failed to send message: {e}")
        time.sleep(300)  # 5 minutes

if __name__ == "__main__":
    main()
