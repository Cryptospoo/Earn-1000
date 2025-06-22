# Crypto Affiliate Bot

🚀 Earn $1,000+/month with automated crypto referrals

## Features
- Track Binance/Bybit/OKX signups
- Auto-calculate earnings
- PayPal payout system

## Deployment
1. Set environment variables:
   - `7989868027:AAHHLhJKNg3dqqbVsRr1tgGBp4C1W2ielpk`
   - `[BINANCE_REF_LINK](https://www.binance.com/activity/referral-entry/CPA?ref=CPA_00K0JPWHX7)`
   - `@JustPositive1`
2. Deploy to Render.com (Docker)
3. Set webhook:
   ```bash
   curl "https://api.telegram.org/bot<7989868027:AAHHLhJKNg3dqqbVsRr1tgGBp4C1W2ielpk>/setWebhook?url=<RENDER_URL>"
