<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UI helper.
 *
 * Reusable Bootstrap 5 markup fragments so views never duplicate component
 * HTML. Everything returns an escaped string ready to echo.
 *
 * @package Kupiana\Helpers
 */

if ( ! function_exists('status_badge'))
{
	/**
	 * Coloured badge for a record/order/payment status.
	 *
	 * @param  string $status
	 * @param  string $map Which config map to read: record, order or payment.
	 * @return string
	 */
	function status_badge($status, $map = 'record')
	{
		$maps = array(
			'record'  => app_config('record_statuses', array()),
			'order'   => app_config('order_statuses', array()),
			'payment' => app_config('payment_statuses', array()),
		);

		$definitions = isset($maps[$map]) ? $maps[$map] : array();
		$definition  = isset($definitions[$status]) ? $definitions[$status] : NULL;

		$label = $definition ? $definition['label'] : ucwords(str_replace('_', ' ', (string) $status));
		$tone  = $definition && isset($definition['badge']) ? $definition['badge'] : 'secondary';

		return '<span class="badge badge-soft badge-soft-'.html_escape($tone).'">'
			.html_escape($label).'</span>';
	}
}

if ( ! function_exists('bool_badge'))
{
	/**
	 * Yes/No badge for boolean columns.
	 *
	 * @param  mixed  $value
	 * @param  string $yes
	 * @param  string $no
	 * @return string
	 */
	function bool_badge($value, $yes = 'Yes', $no = 'No')
	{
		return $value
			? '<span class="badge badge-soft badge-soft-success">'.html_escape($yes).'</span>'
			: '<span class="badge badge-soft badge-soft-secondary">'.html_escape($no).'</span>';
	}
}

if ( ! function_exists('breadcrumbs'))
{
	/**
	 * Bootstrap breadcrumb trail.
	 *
	 * @param  array $items Each: array('label' => string, 'uri' => string|null).
	 * @param  string $home_uri
	 * @return string
	 */
	function breadcrumbs(array $items, $home_uri = 'admin')
	{
		if (empty($items))
		{
			return '';
		}

		$html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">';
		$html .= '<li class="breadcrumb-item"><a href="'.site_url($home_uri).'">'
			.'<i class="fa-solid fa-house"></i></a></li>';

		$last = count($items) - 1;

		foreach ($items as $index => $item)
		{
			$label = html_escape($item['label']);
			$uri   = isset($item['uri']) ? $item['uri'] : NULL;

			if ($index === $last || $uri === NULL)
			{
				$html .= '<li class="breadcrumb-item active" aria-current="page">'.$label.'</li>';
			}
			else
			{
				$html .= '<li class="breadcrumb-item"><a href="'.site_url($uri).'">'.$label.'</a></li>';
			}
		}

		return $html.'</ol></nav>';
	}
}

if ( ! function_exists('empty_state'))
{
	/**
	 * Friendly placeholder for empty tables and grids.
	 *
	 * @param  string      $title
	 * @param  string      $message
	 * @param  string      $icon Font Awesome class.
	 * @param  array|null  $action array('label' => ..., 'url' => ..., 'icon' => ...)
	 * @return string
	 */
	function empty_state($title = 'Nothing here yet', $message = 'There are no records to show.', $icon = 'fa-inbox', $action = NULL)
	{
		$html = '<div class="empty-state text-center py-5">'
			.'<div class="empty-state-icon mb-3"><i class="fa-solid '.html_escape($icon).'"></i></div>'
			.'<h5 class="mb-1">'.html_escape($title).'</h5>'
			.'<p class="text-muted mb-3">'.html_escape($message).'</p>';

		if (is_array($action) && ! empty($action['url']))
		{
			$html .= '<a href="'.html_escape($action['url']).'" class="btn btn-primary">'
				.'<i class="fa-solid '.html_escape(array_get($action, 'icon', 'fa-plus')).' me-2"></i>'
				.html_escape(array_get($action, 'label', 'Create')).'</a>';
		}

		return $html.'</div>';
	}
}

if ( ! function_exists('sort_link'))
{
	/**
	 * Sortable table header link that preserves current filters.
	 *
	 * @param  string $label
	 * @param  string $column
	 * @return string
	 */
	function sort_link($label, $column)
	{
		$CI =& get_instance();

		$current_sort  = $CI->input->get('sort', TRUE);
		$current_order = strtolower((string) $CI->input->get('order', TRUE)) === 'asc' ? 'asc' : 'desc';

		$is_active  = ($current_sort === $column);
		$next_order = ($is_active && $current_order === 'asc') ? 'desc' : 'asc';

		$icon = 'fa-sort';

		if ($is_active)
		{
			$icon = ($current_order === 'asc') ? 'fa-sort-up' : 'fa-sort-down';
		}

		$url = query_url(array('sort' => $column, 'order' => $next_order, 'page' => 1));

		return '<a href="'.html_escape($url).'" class="table-sort'.($is_active ? ' is-active' : '').'">'
			.html_escape($label).' <i class="fa-solid '.$icon.'"></i></a>';
	}
}

if ( ! function_exists('render_pagination'))
{
	/**
	 * Pagination bar for the array returned by MY_Model::paginate().
	 *
	 * @param  array $pagination
	 * @return string
	 */
	function render_pagination(array $pagination)
	{
		$page  = (int) array_get($pagination, 'page', 1);
		$pages = (int) array_get($pagination, 'total_pages', 0);
		$total = (int) array_get($pagination, 'total', 0);

		if ($pages <= 1)
		{
			return '<div class="text-muted small">Showing '.$total.' record'.($total === 1 ? '' : 's').'</div>';
		}

		$html = '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">';
		$html .= '<div class="text-muted small">Showing '.(int) array_get($pagination, 'from', 0)
			.'–'.(int) array_get($pagination, 'to', 0).' of '.$total.'</div>';
		$html .= '<ul class="pagination pagination-sm mb-0">';

		$html .= '<li class="page-item'.($page <= 1 ? ' disabled' : '').'">'
			.'<a class="page-link" href="'.html_escape(query_url(array('page' => $page - 1))).'">'
			.'<i class="fa-solid fa-chevron-left"></i></a></li>';

		$start = max(1, $page - 2);
		$end   = min($pages, $start + 4);
		$start = max(1, $end - 4);

		if ($start > 1)
		{
			$html .= '<li class="page-item"><a class="page-link" href="'
				.html_escape(query_url(array('page' => 1))).'">1</a></li>';

			if ($start > 2)
			{
				$html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
			}
		}

		for ($i = $start; $i <= $end; $i++)
		{
			$html .= '<li class="page-item'.($i === $page ? ' active' : '').'">'
				.'<a class="page-link" href="'.html_escape(query_url(array('page' => $i))).'">'.$i.'</a></li>';
		}

		if ($end < $pages)
		{
			if ($end < $pages - 1)
			{
				$html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
			}

			$html .= '<li class="page-item"><a class="page-link" href="'
				.html_escape(query_url(array('page' => $pages))).'">'.$pages.'</a></li>';
		}

		$html .= '<li class="page-item'.($page >= $pages ? ' disabled' : '').'">'
			.'<a class="page-link" href="'.html_escape(query_url(array('page' => $page + 1))).'">'
			.'<i class="fa-solid fa-chevron-right"></i></a></li>';

		return $html.'</ul></div>';
	}
}

if ( ! function_exists('per_page_selector'))
{
	/**
	 * "Show N entries" dropdown.
	 *
	 * @param  int $current
	 * @return string
	 */
	function per_page_selector($current)
	{
		$options = app_config('per_page_options', array(15, 25, 50, 100));

		$html = '<select class="form-select form-select-sm w-auto" data-navigate-on-change>';

		foreach ($options as $option)
		{
			$html .= '<option value="'.html_escape(query_url(array('per_page' => $option, 'page' => 1))).'"'
				.((int) $current === (int) $option ? ' selected' : '').'>'.$option.' / page</option>';
		}

		return $html.'</select>';
	}
}

if ( ! function_exists('page_header'))
{
	/**
	 * Admin page title row with optional action buttons.
	 *
	 * @param  string $title
	 * @param  string $subtitle
	 * @param  array  $actions Each: array('label','url','icon','class').
	 * @return string
	 */
	function page_header($title, $subtitle = '', array $actions = array())
	{
		$html = '<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">';
		$html .= '<div><h1 class="page-title mb-1">'.html_escape($title).'</h1>';

		if ($subtitle !== '')
		{
			$html .= '<p class="text-muted mb-0">'.html_escape($subtitle).'</p>';
		}

		$html .= '</div>';

		if ( ! empty($actions))
		{
			$html .= '<div class="d-flex flex-wrap gap-2">';

			foreach ($actions as $action)
			{
				$html .= '<a href="'.html_escape(array_get($action, 'url', '#')).'" class="btn '
					.html_escape(array_get($action, 'class', 'btn-primary')).'">'
					.'<i class="fa-solid '.html_escape(array_get($action, 'icon', 'fa-plus')).' me-2"></i>'
					.html_escape(array_get($action, 'label', 'Action')).'</a>';
			}

			$html .= '</div>';
		}

		return $html.'</div>';
	}
}

if ( ! function_exists('stat_card'))
{
	/**
	 * Dashboard KPI tile.
	 *
	 * @param  string      $label
	 * @param  string      $value
	 * @param  string      $icon
	 * @param  string      $tone   Bootstrap colour name.
	 * @param  float|null  $trend  Percentage change; NULL hides the trend row.
	 * @return string
	 */
	function stat_card($label, $value, $icon = 'fa-chart-line', $tone = 'primary', $trend = NULL)
	{
		$html = '<div class="card stat-card h-100"><div class="card-body d-flex align-items-start gap-3">';
		$html .= '<div class="stat-icon bg-soft-'.html_escape($tone).'">'
			.'<i class="fa-solid '.html_escape($icon).'"></i></div>';
		$html .= '<div class="flex-grow-1"><div class="stat-label">'.html_escape($label).'</div>';
		$html .= '<div class="stat-value">'.html_escape($value).'</div>';

		if ($trend !== NULL)
		{
			$up    = ((float) $trend >= 0);
			$class = $up ? 'text-success' : 'text-danger';
			$arrow = $up ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';

			$html .= '<div class="stat-trend small '.$class.'">'
				.'<i class="fa-solid '.$arrow.' me-1"></i>'
				.abs((float) $trend).'% vs last period</div>';
		}

		return $html.'</div></div></div>';
	}
}

if ( ! function_exists('icon_button'))
{
	/**
	 * Compact icon action button for table rows.
	 *
	 * @param  string $url
	 * @param  string $icon
	 * @param  string $title
	 * @param  string $tone
	 * @param  array  $attributes Extra HTML attributes.
	 * @return string
	 */
	function icon_button($url, $icon, $title, $tone = 'secondary', array $attributes = array())
	{
		$extra = '';

		foreach ($attributes as $key => $value)
		{
			$extra .= ' '.html_escape($key).'="'.html_escape($value).'"';
		}

		return '<a href="'.html_escape($url).'" class="btn btn-sm btn-icon btn-outline-'.html_escape($tone).'" '
			.'data-bs-toggle="tooltip" title="'.html_escape($title).'"'.$extra.'>'
			.'<i class="fa-solid '.html_escape($icon).'"></i></a>';
	}
}

if ( ! function_exists('delete_button'))
{
	/**
	 * Delete button wired to the global confirm modal in app.js.
	 *
	 * @param  string $url
	 * @param  string $label
	 * @return string
	 */
	function delete_button($url, $label = 'Delete this record?')
	{
		return '<button type="button" class="btn btn-sm btn-icon btn-outline-danger" '
			.'data-confirm-url="'.html_escape($url).'" '
			.'data-confirm-message="'.html_escape($label).'" '
			.'data-bs-toggle="tooltip" title="Delete">'
			.'<i class="fa-solid fa-trash"></i></button>';
	}
}

if ( ! function_exists('form_error_class'))
{
	/**
	 * Append `is-invalid` when a field failed server-side validation.
	 *
	 * @param  string $field
	 * @param  string $base
	 * @return string
	 */
	function form_error_class($field, $base = 'form-control')
	{
		return $base.(form_error($field) ? ' is-invalid' : '');
	}
}

if ( ! function_exists('field_error'))
{
	/**
	 * Bootstrap invalid-feedback block for a field.
	 *
	 * @param  string $field
	 * @return string
	 */
	function field_error($field)
	{
		return form_error($field, '<div class="invalid-feedback d-block">', '</div>');
	}
}
