# Crypto Affiliate Bot

🚀 Earn $1,000+/month with automated crypto referrals

## Features
- Track Binance/Bybit/OKX signups
- Auto-calculate earnings
- PayPal payout system

## Deployment
1. Set environment variables:
   - `TELEGRAM_BOT_TOKEN`
   - `BINANCE_REF_LINK`
   - `ADMIN_TELEGRAM_ID`
2. Deploy to Render.com (Docker)
3. Set webhook:
   ```bash
   curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url=<RENDER_URL>"