<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transactional email shell.
 *
 * Table-based with inline styles, because email clients ignore most modern CSS.
 * Mailer::wrap() injects the rendered template body as $body_html.
 *
 * @var string $subject
 * @var string $body_html
 * @var string $site_name
 * @var string $site_url
 * @var string $support_email
 * @var string $year
 */
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo html_escape($subject); ?></title>
</head>
<body style="margin:0;padding:0;background:#faf6f2;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#2c1e16;">

	<!-- Preheader: shown in inbox previews, hidden in the body. -->
	<div style="display:none;max-height:0;overflow:hidden;opacity:0;">
		<?php echo html_escape($subject); ?>
	</div>

	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf6f2;padding:24px 12px;">
		<tr>
			<td align="center">

				<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
				       style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;
				              border:1px solid #e9dfd6;">

					<!-- Header -->
					<tr>
						<td style="background:#cc4e3a;padding:24px 32px;text-align:center;">
							<a href="<?php echo html_escape($site_url); ?>"
							   style="color:#ffffff;font-size:22px;font-weight:700;text-decoration:none;letter-spacing:-.02em;">
								<?php echo html_escape($site_name); ?>
							</a>
						</td>
					</tr>

					<!-- Body -->
					<tr>
						<td style="padding:32px;font-size:15px;line-height:1.65;color:#4a3a30;">
							<?php echo $body_html; ?>
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td style="padding:20px 32px;background:#f6efe8;border-top:1px solid #e9dfd6;
						           font-size:12px;line-height:1.6;color:#7b6a5f;text-align:center;">
							<p style="margin:0 0 6px;">
								Need help? Write to
								<a href="mailto:<?php echo html_escape($support_email); ?>"
								   style="color:#cc4e3a;text-decoration:none;"><?php echo html_escape($support_email); ?></a>
							</p>
							<p style="margin:0;">
								&copy; <?php echo html_escape($year); ?> <?php echo html_escape($site_name); ?>.
								All rights reserved.
							</p>
						</td>
					</tr>
				</table>

				<p style="max-width:600px;margin:16px auto 0;font-size:11px;line-height:1.5;color:#a2907f;text-align:center;">
					This is an automated message — please do not reply directly to it.
				</p>
			</td>
		</tr>
	</table>
</body>
</html>
