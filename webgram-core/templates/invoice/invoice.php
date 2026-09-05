<?php
/**
 * Invoice (spec 4.11): A4 portrait, table based for dompdf, DejaVu Sans, restricted CSS. $args: d (InvoiceData).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

$d       = $args['d'];
$accent  = $d['colors']['accent'];
$text    = $d['colors']['text'];
$money   = static fn( float $n ): string => wp_strip_all_tags( wc_price( $n, [ 'currency' => $d['currency'] ] ) );
$cols    = 4 + ( $d['show']['sku'] ? 1 : 0 ) + ( $d['show']['hsn'] ? 1 : 0 );
$icon    = static fn( string $path ): string => '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( $accent ) . '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo esc_html( sprintf( 'Invoice %s', $d['invoice']['number'] ) ); ?></title>
<style>
@page { margin: 14mm 12mm; }
body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: <?php echo esc_attr( $text ); ?>; margin: 0; }
table { border-collapse: collapse; width: 100%; }
td, th { vertical-align: top; }
.wgc-invoice { width: 100%; background: #fff; }
.head td { padding: 0 0 8pt; }
.brand img { max-height: 48pt; max-width: 160pt; }
.brand .name { font-size: 15pt; font-weight: bold; color: <?php echo esc_attr( $accent ); ?>; }
.brand .tag { font-size: 8.5pt; color: #6b7280; }
.contact { font-size: 8.5pt; line-height: 1.5; color: #374151; }
.contact svg { vertical-align: middle; margin-right: 3pt; }
.box { background: <?php echo esc_attr( $accent ); ?>; color: #fff; padding: 10pt 12pt; }
.box .title { font-size: 20pt; font-weight: bold; letter-spacing: 1pt; }
.box .no { font-size: 11pt; margin: 2pt 0 6pt; }
.box table td { color: #fff; font-size: 8.5pt; padding: 1pt 0; }
.rule { border-top: 2pt solid <?php echo esc_attr( $accent ); ?>; height: 0; margin: 4pt 0 10pt; }
.blocks td { width: 33.33%; padding: 0 10pt 0 0; border-right: 1pt solid #e5e7eb; }
.blocks td:last-child { border-right: 0; padding-right: 0; padding-left: 10pt; }
.blocks td.mid { padding-left: 10pt; }
.blocks h4 { margin: 0 0 4pt; font-size: 9pt; color: <?php echo esc_attr( $accent ); ?>; text-transform: uppercase; letter-spacing: .5pt; }
.blocks p { margin: 0; line-height: 1.5; font-size: 8.8pt; }
.items { margin-top: 12pt; }
.items th { background: <?php echo esc_attr( $accent ); ?>; color: #fff; font-size: 8.5pt; text-align: left; padding: 6pt 6pt; }
.items td { padding: 6pt; border-bottom: 1pt solid #e5e7eb; font-size: 8.8pt; }
.items .zebra td { background: #f9fafb; }
.items .num { text-align: right; white-space: nowrap; }
.items .center { text-align: center; }
.items img { width: 40pt; height: 40pt; }
.items .pname { font-weight: bold; }
.items .pvar { color: #6b7280; font-size: 8pt; }
.summary { margin-top: 12pt; }
.summary > tbody > tr > td { width: 50%; }
.grey { background: #f3f4f6; padding: 9pt 10pt; font-size: 8.8pt; line-height: 1.5; margin-bottom: 8pt; }
.grey h4 { margin: 0 0 3pt; font-size: 9pt; color: <?php echo esc_attr( $accent ); ?>; }
.totals td { padding: 3pt 6pt; font-size: 9pt; }
.totals .num { text-align: right; white-space: nowrap; }
.totals .neg { color: #15803d; }
.grand { background: <?php echo esc_attr( $accent ); ?>; color: #fff; font-weight: bold; font-size: 11pt; }
.grand td { padding: 7pt 8pt; }
.foot { margin-top: 16pt; border-top: 1pt solid #e5e7eb; padding-top: 8pt; }
.foot td { width: 33.33%; font-size: 8.3pt; line-height: 1.5; color: #374151; padding-right: 8pt; }
.foot h5 { margin: 0 0 2pt; font-size: 8.8pt; color: <?php echo esc_attr( $accent ); ?>; }
.strip { margin-top: 10pt; background: #f3f4f6; padding: 6pt 8pt; font-size: 7.8pt; color: #6b7280; }
.strip td { padding: 0; }
.strip .right { text-align: right; }
</style>
</head>
<body>
<div class="wgc-invoice">
	<table class="head">
		<tr>
			<td style="width:34%" class="brand">
				<?php if ( $d['store']['logo'] ) : ?><img src="<?php echo esc_url( $d['store']['logo'] ); ?>" alt=""><br><?php endif; ?>
				<div class="name"><?php echo esc_html( $d['store']['name'] ); ?></div>
				<?php if ( $d['store']['tagline'] ) : ?><div class="tag"><?php echo esc_html( $d['store']['tagline'] ); ?></div><?php endif; ?>
			</td>
			<td style="width:33%" class="contact">
				<?php if ( $d['store']['address'] ) : ?><div><?php echo $icon( '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo nl2br( esc_html( $d['store']['address'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
				<?php if ( $d['store']['phone'] ) : ?><div><?php echo $icon( '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.8 2z"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $d['store']['phone'] ); ?></div><?php endif; ?>
				<?php if ( $d['store']['email'] ) : ?><div><?php echo $icon( '<path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $d['store']['email'] ); ?></div><?php endif; ?>
				<?php if ( $d['store']['website'] ) : ?><div><?php echo $icon( '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( preg_replace( '#^https?://#', '', $d['store']['website'] ) ); ?></div><?php endif; ?>
			</td>
			<td style="width:33%">
				<div class="box">
					<div class="title"><?php esc_html_e( 'INVOICE', 'webgram-core' ); ?></div>
					<div class="no">#<?php echo esc_html( $d['invoice']['number'] ); ?></div>
					<table>
						<tr><td><?php esc_html_e( 'Order Date', 'webgram-core' ); ?> : <?php echo esc_html( $d['order']['date'] ); ?></td></tr>
						<tr><td><?php esc_html_e( 'Payment Date', 'webgram-core' ); ?> : <?php echo esc_html( $d['order']['payment_date'] ?: '-' ); ?></td></tr>
						<tr><td><?php esc_html_e( 'Order Status', 'webgram-core' ); ?> : <?php echo esc_html( $d['order']['status'] ); ?></td></tr>
					</table>
				</div>
			</td>
		</tr>
	</table>
	<div class="rule"></div>
	<table class="blocks">
		<tr>
			<td><h4><?php echo $icon( '<path d="M3 21h18M5 21V7l7-4 7 4v14"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Billing Address', 'webgram-core' ); ?></h4><p><?php echo wp_kses( $d['customer']['billing'], [ 'br' => [] ] ); ?><?php echo $d['customer']['phone'] ? '<br>' . esc_html( $d['customer']['phone'] ) : ''; ?><?php echo $d['customer']['email'] ? '<br>' . esc_html( $d['customer']['email'] ) : ''; ?></p></td>
			<td class="mid"><h4><?php echo $icon( '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Shipping Address', 'webgram-core' ); ?></h4><p><?php echo $d['customer']['shipping'] ? wp_kses( $d['customer']['shipping'], [ 'br' => [] ] ) : esc_html__( 'Same as billing', 'webgram-core' ); ?></p></td>
			<td><h4><?php echo $icon( '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Order Information', 'webgram-core' ); ?></h4>
				<p><?php esc_html_e( 'Invoice Number', 'webgram-core' ); ?>: <?php echo esc_html( $d['invoice']['number'] ); ?><br>
				<?php esc_html_e( 'Order Number', 'webgram-core' ); ?>: #<?php echo esc_html( $d['order']['number'] ); ?><br>
				<?php esc_html_e( 'Order Date', 'webgram-core' ); ?>: <?php echo esc_html( $d['order']['date'] ); ?><br>
				<?php esc_html_e( 'Payment Method', 'webgram-core' ); ?>: <?php echo esc_html( $d['order']['payment_method'] ?: '-' ); ?><br>
				<?php esc_html_e( 'Shipping Method', 'webgram-core' ); ?>: <?php echo esc_html( $d['order']['shipping_method'] ?: '-' ); ?></p></td>
		</tr>
	</table>
	<table class="items">
		<thead>
			<tr>
				<th style="width:5%">#</th>
				<th><?php esc_html_e( 'Product', 'webgram-core' ); ?></th>
				<?php if ( $d['show']['sku'] ) : ?><th style="width:11%"><?php esc_html_e( 'SKU', 'webgram-core' ); ?></th><?php endif; ?>
				<?php if ( $d['show']['hsn'] ) : ?><th style="width:9%"><?php esc_html_e( 'HSN', 'webgram-core' ); ?></th><?php endif; ?>
				<th class="num" style="width:13%"><?php esc_html_e( 'Price', 'webgram-core' ); ?></th>
				<th class="center" style="width:8%"><?php esc_html_e( 'Quantity', 'webgram-core' ); ?></th>
				<th class="num" style="width:14%"><?php esc_html_e( 'Total', 'webgram-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $d['items'] as $i => $item ) : ?>
				<tr class="<?php echo $d['show']['zebra'] && $i % 2 ? 'zebra' : ''; ?>">
					<td><?php echo (int) ( $i + 1 ); ?></td>
					<td>
						<table><tr>
							<?php if ( $item['image'] ) : ?><td style="width:46pt"><img src="<?php echo esc_url( $item['image'] ); ?>" alt=""></td><?php endif; ?>
							<td><div class="pname"><?php echo esc_html( $item['name'] ); ?></div><?php if ( $item['variation'] ) : ?><div class="pvar"><?php echo esc_html( $item['variation'] ); ?></div><?php endif; ?><?php if ( $item['discount'] > 0 ) : ?><div class="pvar"><?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'Discount: %s', 'webgram-core' ), $money( $item['discount'] ) ) ); ?></div><?php endif; ?></td>
						</tr></table>
					</td>
					<?php if ( $d['show']['sku'] ) : ?><td><?php echo esc_html( $item['sku'] ?: '-' ); ?></td><?php endif; ?>
					<?php if ( $d['show']['hsn'] ) : ?><td><?php echo esc_html( $item['hsn'] ?: '-' ); ?></td><?php endif; ?>
					<td class="num"><?php echo esc_html( $money( $item['unit_price'] ) ); ?></td>
					<td class="center"><?php echo (int) $item['qty']; ?></td>
					<td class="num"><?php echo esc_html( $money( $item['total'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<table class="summary">
		<tr>
			<td style="padding-right:12pt">
				<div class="grey"><h4><?php esc_html_e( 'Payment Information', 'webgram-core' ); ?></h4><?php echo esc_html( $d['order']['payment_line'] ); ?></div>
				<div class="grey"><h4><?php esc_html_e( 'Notes', 'webgram-core' ); ?></h4><?php echo $d['order']['note'] ? esc_html( $d['order']['note'] ) . '<br>' : ''; ?><?php echo esc_html( $d['store']['support'] ); ?></div>
			</td>
			<td>
				<table class="totals">
					<tr><td><?php esc_html_e( 'Subtotal', 'webgram-core' ); ?></td><td class="num"><?php echo esc_html( $money( $d['totals']['subtotal'] ) ); ?></td></tr>
					<?php if ( $d['totals']['discount'] > 0 ) : ?><tr><td><?php esc_html_e( 'Discount', 'webgram-core' ); ?><?php echo $d['totals']['coupon'] ? ' (' . esc_html( $d['totals']['coupon'] ) . ')' : ''; ?></td><td class="num neg">-<?php echo esc_html( $money( $d['totals']['discount'] ) ); ?></td></tr><?php endif; ?>
					<tr><td><?php esc_html_e( 'Shipping', 'webgram-core' ); ?></td><td class="num"><?php echo esc_html( $d['totals']['shipping'] > 0 ? $money( $d['totals']['shipping'] ) : __( 'Free', 'webgram-core' ) ); ?></td></tr>
					<?php if ( $d['totals']['fees'] > 0 ) : ?><tr><td><?php esc_html_e( 'Fees', 'webgram-core' ); ?></td><td class="num"><?php echo esc_html( $money( $d['totals']['fees'] ) ); ?></td></tr><?php endif; ?>
					<?php foreach ( $d['totals']['taxes'] as $tax ) : ?><tr><td><?php echo esc_html( $tax['label'] ); ?><?php echo $tax['rate'] > 0 ? ' (' . esc_html( rtrim( rtrim( number_format( $tax['rate'], 2, '.', '' ), '0' ), '.' ) ) . '%)' : ''; ?></td><td class="num"><?php echo esc_html( $money( $tax['amount'] ) ); ?></td></tr><?php endforeach; ?>
					<?php if ( $d['totals']['refunded'] > 0 ) : ?><tr><td><?php esc_html_e( 'Refunded', 'webgram-core' ); ?></td><td class="num neg">-<?php echo esc_html( $money( $d['totals']['refunded'] ) ); ?></td></tr><?php endif; ?>
					<tr class="grand"><td><?php esc_html_e( 'Grand Total', 'webgram-core' ); ?></td><td class="num"><?php echo esc_html( $money( $d['totals']['total'] ) ); ?></td></tr>
				</table>
			</td>
		</tr>
	</table>
	<table class="foot">
		<tr>
			<td><h5><?php echo esc_html( $d['store']['name'] ); ?></h5><?php echo $d['store']['gstin'] ? esc_html__( 'GSTIN', 'webgram-core' ) . ': ' . esc_html( $d['store']['gstin'] ) . '<br>' : ''; ?><?php echo $d['store']['pan'] ? esc_html__( 'PAN', 'webgram-core' ) . ': ' . esc_html( $d['store']['pan'] ) . '<br>' : ''; ?><?php echo $d['store']['cin'] ? esc_html__( 'CIN', 'webgram-core' ) . ': ' . esc_html( $d['store']['cin'] ) . '<br>' : ''; ?><?php echo nl2br( esc_html( $d['store']['address'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
			<td><h5><?php echo $icon( '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Need Help?', 'webgram-core' ); ?></h5><?php echo $d['store']['phone'] ? esc_html( $d['store']['phone'] ) . '<br>' : ''; ?><?php echo esc_html( $d['store']['email'] ); ?></td>
			<td><h5><?php esc_html_e( 'Stay connected', 'webgram-core' ); ?></h5><?php echo $d['store']['social'] ? nl2br( esc_html( $d['store']['social'] ) ) . '<br>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( preg_replace( '#^https?://#', '', $d['store']['website'] ) ); ?><?php echo $d['store']['footer'] ? '<br>' . esc_html( $d['store']['footer'] ) : ''; ?></td>
		</tr>
	</table>
	<table class="strip">
		<tr>
			<td><?php echo esc_html( $d['disclaimer'] ); ?></td>
			<td class="right"><?php echo esc_html( sprintf( /* translators: %s: date time */ __( 'Invoice generated on %s', 'webgram-core' ), $d['generated'] ) ); ?></td>
		</tr>
	</table>
</div>
</body>
</html>
