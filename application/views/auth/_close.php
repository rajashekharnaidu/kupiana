<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Closing markup shared by every auth screen.
 *
 * @var string|null $auth_footer Optional HTML shown under the card body.
 */
?>
						<?php if ( ! empty($auth_footer)): ?>
							<p class="text-center text-muted small mt-4 mb-0"><?php echo $auth_footer; ?></p>
						<?php endif; ?>
					</div>
				</div>

				<p class="text-center text-muted small mt-3 mb-0">
					<i class="fa-solid fa-lock me-1"></i>Your connection to this site is secure.
				</p>
			</div>
		</div>
	</div>
</section>

<script>
/* Reveal/hide password fields. Applies to any [data-toggle-password] button. */
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var field = document.getElementById(btn.getAttribute('data-toggle-password'));

			if (!field) { return; }

			var hidden = field.type === 'password';

			field.type = hidden ? 'text' : 'password';
			btn.innerHTML = hidden
				? '<i class="fa-regular fa-eye-slash"></i>'
				: '<i class="fa-regular fa-eye"></i>';
		});
	});
});
</script>
