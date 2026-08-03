<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Opening markup shared by every auth screen.
 *
 * Set $auth_title, $auth_subtitle and optionally $auth_width before loading:
 *
 *     $auth_title = 'Welcome back';
 *     $this->load->view('auth/_open', get_defined_vars());
 *     ... form ...
 *     $this->load->view('auth/_close', get_defined_vars());
 *
 * @var string $auth_title
 * @var string $auth_subtitle
 */

$auth_width = isset($auth_width) ? $auth_width : 'col-lg-5';
?>
<section class="py-5">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-sm-10 col-md-7 <?php echo html_escape($auth_width); ?>">

				<div class="card fade-in-up">
					<div class="card-body p-4 p-md-5">

						<div class="text-center mb-4">
							<img class="brand-logo brand-logo-auth mb-3" src="<?php echo base_url(array_get($app, 'logo', 'public/assets/images/kupiana-logo-512.png')); ?>" alt="<?php echo html_escape($site_name); ?> logo">
							<h1 class="h4 mb-1"><?php echo html_escape($auth_title); ?></h1>
							<?php if ( ! empty($auth_subtitle)): ?>
								<p class="text-muted mb-0"><?php echo $auth_subtitle; ?></p>
							<?php endif; ?>
						</div>

						<?php
						/*
						 * No validation_errors() summary here on purpose: every field
						 * renders its own field_error() inline, and showing both meant
						 * each message appeared twice. Inline errors point at the
						 * offending input, so they win.
						 */
						?>
