# WhatsApp template texts to submit to Meta

Webgram Core sends transactional WhatsApp messages only through pre-approved Meta templates. Create these in
Meta Business Suite (WhatsApp Manager > Message templates), category **Utility**, then press "Sync templates
from Meta" in Webgram > Core Settings > Notifications and map each event to its template. Parameters are
numbered `{{1}}`, `{{2}}` in Meta; in Webgram list the variables in the same order.

| Event | Suggested name | Body text | Parameters in Webgram |
|---|---|---|---|
| Order placed | `wg_order_placed` | Hi {{1}}, thanks for your order {{2}} at {{3}}. Total: {{4}}. We will message you when it ships. | `{customer_name}, {order_number}, {store_name}, {order_total}` |
| Payment successful | `wg_payment_received` | Hi {{1}}, we received your payment of {{2}} for order {{3}}. Invoice: {{4}} | `{customer_name}, {order_total}, {order_number}, {invoice_url}` |
| Processing | `wg_order_processing` | Hi {{1}}, order {{2}} is being prepared. Expected dispatch soon. | `{customer_name}, {order_number}` |
| Shipped | `wg_order_shipped` | Hi {{1}}, order {{2}} has shipped via {{3}}. Tracking: {{4}} {{5}} | `{customer_name}, {order_number}, {carrier}, {tracking_number}, {tracking_url}` |
| Out for delivery | `wg_out_for_delivery` | Hi {{1}}, order {{2}} is out for delivery today. Please keep your phone reachable. | `{customer_name}, {order_number}` |
| Delivered | `wg_order_delivered` | Hi {{1}}, order {{2}} was delivered. We hope you love it. Need help? Reply here. | `{customer_name}, {order_number}` |
| Cancelled | `wg_order_cancelled` | Hi {{1}}, order {{2}} was cancelled. Any payment will be refunded to the original method. | `{customer_name}, {order_number}` |
| Payment failed | `wg_payment_failed` | Hi {{1}}, the payment for order {{2}} did not go through. Retry here: {{3}} | `{customer_name}, {order_number}, {order_url}` |
| Refunded | `wg_order_refunded` | Hi {{1}}, a refund for order {{2}} was issued. It reaches your account in 5 to 7 working days. | `{customer_name}, {order_number}` |

Notes
- Templates that attach the invoice PDF need a **Document** header in Meta; tick "attach invoice PDF" for that event in Webgram. The link is a temporary signed URL valid for 7 days.
- Message charges, if any, are billed by Meta to your WhatsApp Business account according to Meta's pricing. Webgram never relays or bills messages.
- Customers must consent at checkout (or in My Account) before any WhatsApp message is sent. Without consent the log shows `skipped_no_consent`.
- The webhook URL to register in your Meta app is `https://your-site/wp-json/webgram/v1/whatsapp/webhook`; use the verify token and app secret from the settings so delivery and read receipts update the log.
