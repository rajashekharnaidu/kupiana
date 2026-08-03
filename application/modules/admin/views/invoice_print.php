<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html><head><meta charset="utf-8"><title><?php echo html_escape($invoice->invoice_number); ?></title>
<style>body{font-family:Arial,sans-serif;color:#111;margin:40px}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #ddd;padding:8px;text-align:left}.right{text-align:right}.muted{color:#666}</style></head>
<body>
<h1>Invoice <?php echo html_escape($invoice->invoice_number); ?></h1>
<p class="muted">Date: <?php echo format_date($invoice->invoice_date); ?> · Order: <?php echo html_escape($order->order_number); ?></p>
<p><strong><?php echo html_escape($order->customer_name); ?></strong><br><?php echo html_escape($order->customer_email); ?><br><?php echo html_escape($order->customer_phone); ?></p>
<table><thead><tr><th>Item</th><th>SKU</th><th class="right">Qty</th><th class="right">Unit</th><th class="right">Tax</th><th class="right">Total</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td><?php echo html_escape($item->product_name); ?></td><td><?php echo html_escape($item->sku); ?></td><td class="right"><?php echo (int) $item->quantity; ?></td><td class="right"><?php echo money($item->unit_price); ?></td><td class="right"><?php echo money($item->tax_amount); ?></td><td class="right"><?php echo money($item->total); ?></td></tr><?php endforeach; ?>
</tbody></table>
<h3 class="right">Total: <?php echo money($invoice->total_amount); ?></h3>
</body></html>
