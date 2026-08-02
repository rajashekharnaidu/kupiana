/**
 * Kupiana — application JavaScript
 *
 * A single global namespace shared by the admin panel and the storefront.
 * Every AJAX call goes through Kupiana.ajax so CSRF rotation, loading state
 * and the JSON envelope are handled in exactly one place.
 *
 * Depends on: jQuery 3.x, Bootstrap 5.3 bundle.
 */
(function (window, $) {
	'use strict';

	var config = window.KUPIANA || {};

	var Kupiana = {

		config: config,

		/* ==============================================================
		   Toasts
		   ============================================================== */

		/**
		 * Show a toast notification.
		 *
		 * @param {string} type    success | error | warning | info
		 * @param {string} message
		 * @param {number} [delay] Milliseconds before auto-hide.
		 */
		toast: function (type, message, delay) {
			var container = document.getElementById('toastContainer');

			if (!container) {
				return;
			}

			var icons = {
				success: 'fa-circle-check',
				error:   'fa-circle-xmark',
				warning: 'fa-triangle-exclamation',
				info:    'fa-circle-info'
			};

			var icon = icons[type] || icons.info;
			var el = document.createElement('div');

			el.className = 'toast k-toast toast-' + (type || 'info');
			el.setAttribute('role', 'alert');
			el.setAttribute('aria-live', 'assertive');
			el.innerHTML =
				'<div class="toast-body d-flex align-items-start gap-3">' +
					'<i class="fa-solid ' + icon + ' toast-icon"></i>' +
					'<div class="flex-grow-1">' + Kupiana.escape(message) + '</div>' +
					'<button type="button" class="btn-close btn-sm" data-bs-dismiss="toast" aria-label="Close"></button>' +
				'</div>';

			container.appendChild(el);

			var toast = new bootstrap.Toast(el, { delay: delay || 4000 });

			el.addEventListener('hidden.bs.toast', function () {
				el.remove();
			});

			toast.show();
		},

		/** Escape a string for safe insertion as HTML. */
		escape: function (value) {
			return String(value === null || value === undefined ? '' : value)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#039;');
		},

		/* ==============================================================
		   Loading state
		   ============================================================== */

		/** @param {boolean} active */
		loading: function (active) {
			var overlay = document.getElementById('loadingOverlay');

			if (overlay) {
				overlay.classList.toggle('is-active', !!active);
			}
		},

		/**
		 * Put a button into a spinner state and return a restore function.
		 *
		 * @param  {HTMLElement|jQuery} button
		 * @param  {string} [label]
		 * @return {Function} Call to restore the original markup.
		 */
		buttonLoading: function (button, label) {
			var $btn = $(button);
			var original = $btn.html();

			$btn.prop('disabled', true).html(
				'<span class="spinner-border spinner-border-sm me-2" role="status"></span>' +
				(label || 'Please wait…')
			);

			return function () {
				$btn.prop('disabled', false).html(original);
			};
		},

		/* ==============================================================
		   AJAX
		   ============================================================== */

		/**
		 * Perform a request against a Kupiana JSON endpoint.
		 *
		 * Resolves with the envelope's payload on success and rejects with the
		 * envelope on failure, after showing an error toast.
		 *
		 * @param  {Object} options {url, method, data, overlay, silent}
		 * @return {Promise}
		 */
		ajax: function (options) {
			var settings = $.extend({
				url: '',
				method: 'POST',
				data: {},
				overlay: false,
				silent: false,
				dataType: 'json'
			}, options);

			// Attach the CSRF token to every state-changing request.
			if (settings.method.toUpperCase() !== 'GET' && config.csrfName) {
				if (settings.data instanceof FormData) {
					settings.data.append(config.csrfName, config.csrfHash);
					settings.processData = false;
					settings.contentType = false;
				} else {
					settings.data[config.csrfName] = config.csrfHash;
				}
			}

			if (settings.overlay) {
				Kupiana.loading(true);
			}

			return new Promise(function (resolve, reject) {
				$.ajax(settings)
					.done(function (response) {
						Kupiana.refreshCsrf(response);

						if (response && response.status === 'success') {
							if (response.meta && response.meta.redirect) {
								window.location.href = response.meta.redirect;
								return;
							}

							resolve(response);
							return;
						}

						if (!settings.silent) {
							Kupiana.toast('error', (response && response.message) || 'Request failed.');
						}

						reject(response);
					})
					.fail(function (xhr) {
						var response = xhr.responseJSON;

						if (xhr.status === 401) {
							Kupiana.toast('warning', 'Your session expired. Redirecting to sign in…');
							setTimeout(function () {
								window.location.href = config.siteUrl + 'login';
							}, 1500);
							reject(response);
							return;
						}

						if (!settings.silent) {
							Kupiana.toast('error',
								(response && response.message) || 'A network error occurred. Please try again.');
						}

						reject(response);
					})
					.always(function () {
						if (settings.overlay) {
							Kupiana.loading(false);
						}
					});
			});
		},

		/**
		 * Keep the in-page CSRF hash in sync when CI regenerates it.
		 *
		 * @param {Object} response
		 */
		refreshCsrf: function (response) {
			if (!response || !response.meta || !response.meta.csrf_hash) {
				return;
			}

			config.csrfHash = response.meta.csrf_hash;

			$('input[name="' + config.csrfName + '"]').val(config.csrfHash);
		},

		/* ==============================================================
		   Confirm dialog
		   ============================================================== */

		/**
		 * Show the shared confirm modal.
		 *
		 * @param {string}   message
		 * @param {Function} onAccept
		 */
		confirm: function (message, onAccept) {
			var modalEl = document.getElementById('confirmModal');

			if (!modalEl) {
				if (window.confirm(message)) {
					onAccept();
				}
				return;
			}

			document.getElementById('confirmMessage').textContent = message;

			var modal  = bootstrap.Modal.getOrCreateInstance(modalEl);
			var accept = document.getElementById('confirmAccept');

			// Replace the node to clear any handler from a previous invocation.
			var fresh = accept.cloneNode(true);
			accept.parentNode.replaceChild(fresh, accept);

			fresh.addEventListener('click', function () {
				modal.hide();
				onAccept();
			});

			modal.show();
		},

		/* ==============================================================
		   Formatting
		   ============================================================== */

		/**
		 * Format a number using the configured currency.
		 *
		 * @param  {number} amount
		 * @return {string}
		 */
		money: function (amount) {
			var currency = config.currency || {};
			var decimals = currency.decimals === undefined ? 2 : currency.decimals;
			var symbol   = currency.symbol || '₹';

			var formatted = Number(amount || 0).toFixed(decimals)
				.replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousand_separator || ',');

			return currency.position === 'after' ? formatted + symbol : symbol + formatted;
		},

		/**
		 * Debounce a function.
		 *
		 * @param  {Function} fn
		 * @param  {number}   wait
		 * @return {Function}
		 */
		debounce: function (fn, wait) {
			var timer = null;

			return function () {
				var context = this;
				var args = arguments;

				clearTimeout(timer);
				timer = setTimeout(function () {
					fn.apply(context, args);
				}, wait || 300);
			};
		},

		/* ==============================================================
		   Behaviours — bound once on DOM ready, and re-bindable for
		   content injected by AJAX.
		   ============================================================== */

		/**
		 * Wire up every declarative behaviour inside a scope.
		 *
		 * @param {HTMLElement|Document} [scope]
		 */
		bind: function (scope) {
			var $scope = $(scope || document);

			// Bootstrap tooltips.
			$scope.find('[data-bs-toggle="tooltip"]').each(function () {
				bootstrap.Tooltip.getOrCreateInstance(this);
			});

			// Navigate when a select's value changes (per-page, filters).
			$scope.find('[data-navigate-on-change]').off('change.k').on('change.k', function () {
				if (this.value) {
					window.location.href = this.value;
				}
			});

			// Lazy images.
			$scope.find('img[data-src]').each(function () {
				this.src = this.getAttribute('data-src');
				this.removeAttribute('data-src');
			});
		},

		/** Set up all global, delegated behaviours. Called once. */
		init: function () {
			Kupiana.bind(document);
			Kupiana.initTheme();
			Kupiana.initSidebar();
			Kupiana.initConfirmButtons();
			Kupiana.initAjaxForms();
			Kupiana.initValidation();
			Kupiana.initBulkSelect();
			Kupiana.initLiveSearch();
			Kupiana.initQuantitySteppers();
			Kupiana.initScrollBehaviour();
			Kupiana.initFilterForms();
		},

		/** Light/dark theme toggle, persisted in localStorage. */
		initTheme: function () {
			var stored = localStorage.getItem('kupiana-theme');

			if (stored) {
				document.documentElement.setAttribute('data-bs-theme', stored);
			}

			$(document).on('click', '[data-theme-toggle]', function () {
				var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';

				document.documentElement.setAttribute('data-bs-theme', next);
				localStorage.setItem('kupiana-theme', next);
			});
		},

		/** Off-canvas sidebar on small screens, plus the storefront nav. */
		initSidebar: function () {
			$(document).on('click', '[data-sidebar-toggle]', function () {
				$('#adminSidebar').toggleClass('is-open');
				$('.sidebar-backdrop').toggleClass('is-visible');
			});

			$(document).on('click', '[data-mobile-nav-toggle]', function () {
				$('#storeNav').toggleClass('is-open');
			});
		},

		/**
		 * Any element with data-confirm-url opens the confirm modal and, on
		 * accept, POSTs to that URL. Used by every delete button.
		 */
		initConfirmButtons: function () {
			$(document).on('click', '[data-confirm-url]', function (event) {
				event.preventDefault();

				var $el = $(this);
				var url = $el.data('confirm-url');
				var message = $el.data('confirm-message') || 'This action cannot be undone.';
				var method = ($el.data('confirm-method') || 'POST').toUpperCase();

				Kupiana.confirm(message, function () {
					Kupiana.ajax({ url: url, method: method, overlay: true })
						.then(function (response) {
							Kupiana.toast('success', response.message);

							if ($el.data('confirm-remove')) {
								$el.closest($el.data('confirm-remove')).fadeOut(250, function () {
									$(this).remove();
								});
							} else {
								setTimeout(function () { window.location.reload(); }, 600);
							}
						})
						.catch(function () { /* toast already shown by ajax() */ });
				});
			});
		},

		/**
		 * Submit any form marked data-ajax-form through Kupiana.ajax.
		 * Field errors from a 422 are painted onto the matching inputs.
		 */
		initAjaxForms: function () {
			$(document).on('submit', 'form[data-ajax-form]', function (event) {
				event.preventDefault();

				var form = this;
				var $form = $(form);

				if (!Kupiana.validateForm(form)) {
					return;
				}

				var $submit = $form.find('[type="submit"]').first();
				var restore = Kupiana.buttonLoading($submit);

				$form.find('.is-invalid').removeClass('is-invalid');
				$form.find('.invalid-feedback.js-error').remove();

				Kupiana.ajax({
					url: $form.attr('action'),
					method: ($form.attr('method') || 'POST').toUpperCase(),
					data: new FormData(form),
					silent: true
				})
					.then(function (response) {
						restore();
						Kupiana.toast('success', response.message);

						if ($form.data('reset-on-success')) {
							form.reset();
						}

						$form.trigger('kupiana:success', [response]);
					})
					.catch(function (response) {
						restore();

						var errors = (response && response.errors) || {};
						var hasFieldErrors = false;

						Object.keys(errors).forEach(function (field) {
							var $input = $form.find('[name="' + field + '"]');

							if ($input.length) {
								hasFieldErrors = true;
								$input.addClass('is-invalid');
								$input.after('<div class="invalid-feedback js-error d-block">' +
									Kupiana.escape(errors[field]) + '</div>');
							}
						});

						Kupiana.toast('error',
							(response && response.message) ||
							(hasFieldErrors ? 'Please correct the highlighted fields.' : 'Request failed.'));
					});
			});
		},

		/** Bootstrap-style client-side validation for non-AJAX forms. */
		initValidation: function () {
			$(document).on('submit', 'form.needs-validation', function (event) {
				if (!Kupiana.validateForm(this)) {
					event.preventDefault();
					event.stopPropagation();
				}

				$(this).addClass('was-validated');
			});
		},

		/**
		 * Run native constraint validation over a form.
		 *
		 * @param  {HTMLFormElement} form
		 * @return {boolean}
		 */
		validateForm: function (form) {
			if (!form.checkValidity) {
				return true;
			}

			var valid = form.checkValidity();

			if (!valid) {
				$(form).addClass('was-validated');

				var $first = $(form).find(':invalid').first();

				if ($first.length) {
					$first.trigger('focus');
				}
			}

			return valid;
		},

		/**
		 * Select-all checkbox plus the bulk action bar used by admin tables.
		 *
		 * Markup contract:
		 *   [data-bulk-toggle]  header checkbox
		 *   [data-bulk-item]    row checkboxes
		 *   [data-bulk-bar]     the action bar
		 *   [data-bulk-count]   element showing the selected count
		 *   [data-bulk-action]  a button carrying data-url
		 */
		initBulkSelect: function () {
			$(document).on('change', '[data-bulk-toggle]', function () {
				var checked = this.checked;

				$('[data-bulk-item]').prop('checked', checked);
				Kupiana.updateBulkBar();
			});

			$(document).on('change', '[data-bulk-item]', function () {
				var total = $('[data-bulk-item]').length;
				var selected = $('[data-bulk-item]:checked').length;

				$('[data-bulk-toggle]')
					.prop('checked', total > 0 && selected === total)
					.prop('indeterminate', selected > 0 && selected < total);

				Kupiana.updateBulkBar();
			});

			$(document).on('click', '[data-bulk-action]', function (event) {
				event.preventDefault();

				var $btn = $(this);
				var ids = $('[data-bulk-item]:checked').map(function () {
					return this.value;
				}).get();

				if (!ids.length) {
					Kupiana.toast('warning', 'Select at least one record first.');
					return;
				}

				var message = $btn.data('confirm') ||
					('Apply this action to ' + ids.length + ' selected record(s)?');

				Kupiana.confirm(message, function () {
					Kupiana.ajax({
						url: $btn.data('url'),
						method: 'POST',
						data: { ids: ids, bulk_action: $btn.data('bulk-action') },
						overlay: true
					}).then(function (response) {
						Kupiana.toast('success', response.message);
						setTimeout(function () { window.location.reload(); }, 700);
					}).catch(function () { /* handled */ });
				});
			});
		},

		/** Show/hide the bulk bar and update its counter. */
		updateBulkBar: function () {
			var selected = $('[data-bulk-item]:checked').length;

			$('[data-bulk-bar]').toggleClass('is-visible', selected > 0);
			$('[data-bulk-count]').text(selected);
		},

		/** Debounced storefront search suggestions. */
		initLiveSearch: function () {
			var $panel = $('#searchSuggestions');

			$(document).on('input', '[data-live-search]', Kupiana.debounce(function () {
				var term = this.value.trim();
				var url = $(this).data('live-search');

				if (term.length < 2) {
					$panel.attr('hidden', true).empty();
					return;
				}

				Kupiana.ajax({ url: url, method: 'GET', data: { q: term }, silent: true })
					.then(function (response) {
						var items = response.data || [];

						if (!items.length) {
							$panel.attr('hidden', true).empty();
							return;
						}

						var html = items.map(function (item) {
							return '<a href="' + Kupiana.escape(item.url) + '">' +
								'<img src="' + Kupiana.escape(item.image) + '" alt="" width="40" height="40" ' +
								'class="rounded" loading="lazy">' +
								'<span class="flex-grow-1">' + Kupiana.escape(item.name) + '</span>' +
								'<strong>' + Kupiana.money(item.price) + '</strong></a>';
						}).join('');

						$panel.html(html).removeAttr('hidden');
					})
					.catch(function () {
						$panel.attr('hidden', true).empty();
					});
			}, 300));

			// Dismiss the panel on an outside click.
			$(document).on('click', function (event) {
				if (!$(event.target).closest('.search-wrap').length) {
					$panel.attr('hidden', true);
				}
			});
		},

		/** Plus/minus quantity controls on cart and product pages. */
		initQuantitySteppers: function () {
			$(document).on('click', '[data-qty-step]', function () {
				var $btn = $(this);
				var step = parseInt($btn.data('qty-step'), 10);
				var $input = $btn.closest('.qty-stepper').find('input');

				var min = parseInt($input.attr('min'), 10) || 1;
				var max = parseInt($input.attr('max'), 10) || 9999;
				var next = (parseInt($input.val(), 10) || min) + step;

				next = Math.min(Math.max(next, min), max);

				$input.val(next).trigger('change');
			});
		},

		/** Sticky header shadow and the back-to-top button. */
		initScrollBehaviour: function () {
			var $header = $('#storeHeader');
			var $top = $('#backToTop');

			$(window).on('scroll', function () {
				var y = window.scrollY;

				$header.toggleClass('is-stuck', y > 10);
				$top.toggleClass('is-visible', y > 400);
			});

			$(document).on('click', '#backToTop', function () {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			});
		},

		/**
		 * Auto-submit admin filter forms and debounce their search input, so
		 * every listing screen filters without a page-specific script.
		 */
		initFilterForms: function () {
			$(document).on('change', 'form[data-filter-form] select, form[data-filter-form] input[type="date"]',
				function () {
					$(this).closest('form').trigger('submit');
				});

			$(document).on('input', 'form[data-filter-form] input[type="search"]',
				Kupiana.debounce(function () {
					$(this).closest('form').trigger('submit');
				}, 500));
		}
	};

	window.Kupiana = Kupiana;

	$(function () {
		Kupiana.init();
	});

}(window, jQuery));
