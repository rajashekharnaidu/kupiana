<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function seo_meta($meta = array())
{
	$site_name = seo_site_name();
	$defaults = array(
		'title' => $site_name,
		'description' => 'Shop curated ecommerce products from '.$site_name.'.',
		'keywords' => '',
		'canonical' => seo_current_url(),
		'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
		'og_type' => 'website',
		'og_title' => '',
		'og_description' => '',
		'og_image' => base_url('public/assets/images/og-default.jpg'),
		'twitter_card' => 'summary_large_image',
		'rel_prev' => '',
		'rel_next' => '',
	);

	$merged = array_merge($defaults, $meta);
	$merged['title'] = seo_clean_text($merged['title'], 70);
	$merged['description'] = seo_clean_text($merged['description'], 160);
	$merged['canonical'] = seo_absolute_url($merged['canonical']);
	$merged['og_title'] = seo_clean_text($merged['og_title'] ?: $merged['title'], 70);
	$merged['og_description'] = seo_clean_text($merged['og_description'] ?: $merged['description'], 200);
	$merged['og_image'] = seo_absolute_url($merged['og_image']);
	$merged['rel_prev'] = $merged['rel_prev'] ? seo_absolute_url($merged['rel_prev']) : '';
	$merged['rel_next'] = $merged['rel_next'] ? seo_absolute_url($merged['rel_next']) : '';

	return $merged;
}

function seo_title($title)
{
	$site_name = seo_site_name();
	$title = trim((string) $title);

	return $title === '' || $title === $site_name ? $site_name : $title.' | '.$site_name;
}

function product_slug($name, $id = NULL)
{
	$slug = url_title(convert_accented_characters($name), '-', TRUE);
	return $id ? $slug.'-'.$id : $slug;
}

function seo_site_name()
{
	$CI =& get_instance();
	$app = (array) $CI->config->item('app', 'app');
	$default = array_get($app, 'name', 'Kupiana');

	return isset($CI->settings) ? (string) $CI->settings->get('site_name', $default) : $default;
}

function seo_current_url()
{
	return current_url();
}

function seo_absolute_url($url)
{
	$url = trim((string) $url);

	if ($url === '') {
		return current_url();
	}

	if (preg_match('#^https?://#i', $url)) {
		return $url;
	}

	return base_url(ltrim($url, '/'));
}

function seo_clean_text($text, $limit = 160)
{
	$text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));

	if ($text === '') {
		return '';
	}

	return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)).'…' : $text;
}

function seo_entity_meta($entity_type, $entity_id, array $fallback = array())
{
	$meta = $fallback;
	$CI =& get_instance();

	if (!isset($CI->db) || !$CI->db->table_exists('seo_meta')) {
		return seo_meta($meta);
	}

	$row = $CI->db
		->from('seo_meta')
		->where('entity_type', $entity_type)
		->where('entity_id', (int) $entity_id)
		->where('status', 'active')
		->where('deleted_at IS NULL', NULL, FALSE)
		->limit(1)
		->get()
		->row();

	if ($row) {
		$meta = array_merge($meta, array_filter(array(
			'title' => $row->meta_title ? seo_title($row->meta_title) : NULL,
			'description' => $row->meta_description,
			'keywords' => $row->meta_keywords,
			'og_title' => $row->og_title,
			'og_description' => $row->og_description,
			'og_image' => $row->og_image,
			'canonical' => $row->canonical_url,
			'robots' => $row->robots,
		), function ($value) {
			return $value !== NULL && $value !== '';
		}));
	}

	return seo_meta($meta);
}

function seo_entity_schema($entity_type, $entity_id)
{
	$CI =& get_instance();

	if (!isset($CI->db) || !$CI->db->table_exists('seo_meta')) {
		return NULL;
	}

	$row = $CI->db
		->select('schema_json')
		->from('seo_meta')
		->where('entity_type', $entity_type)
		->where('entity_id', (int) $entity_id)
		->where('status', 'active')
		->where('deleted_at IS NULL', NULL, FALSE)
		->limit(1)
		->get()
		->row();

	if (!$row || trim((string) $row->schema_json) === '') {
		return NULL;
	}

	$decoded = json_decode($row->schema_json, TRUE);

	return is_array($decoded) ? $decoded : NULL;
}

function seo_pagination_meta(array $pagination, $base_url, array $query = array())
{
	$page = isset($pagination['page']) ? (int) $pagination['page'] : 1;
	$total_pages = isset($pagination['total_pages']) ? (int) $pagination['total_pages'] : 1;
	$base_url = seo_absolute_url($base_url);
	$meta = array();

	unset($query['page']);
	$query = array_filter($query, function ($value) {
		return $value !== NULL && $value !== '';
	});

	if ($page > 1) {
		$self_query = $query;
		$self_query['page'] = $page;
		$meta['canonical'] = $base_url.'?'.http_build_query($self_query);
	}

	if ($page > 1) {
		$prev_query = $query;
		if ($page > 2) {
			$prev_query['page'] = $page - 1;
		}
		$meta['rel_prev'] = $base_url.(empty($prev_query) ? '' : '?'.http_build_query($prev_query));
	}

	if ($page < $total_pages) {
		$next_query = $query;
		$next_query['page'] = $page + 1;
		$meta['rel_next'] = $base_url.'?'.http_build_query($next_query);
	}

	return $meta;
}

function seo_json_ld_graph(array $nodes)
{
	$nodes = array_values(array_filter($nodes));

	if (count($nodes) === 1) {
		return $nodes[0];
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph' => $nodes,
	);
}

function seo_organization_schema()
{
	$CI =& get_instance();
	$app = (array) $CI->config->item('app', 'app');
	$site_name = seo_site_name();
	$support_email = array_get($app, 'support_email', '');
	$support_phone = array_get($app, 'support_phone', '');

	$schema = array(
		'@type' => 'Organization',
		'@id' => site_url().'#organization',
		'name' => $site_name,
		'url' => site_url(),
		'logo' => base_url(array_get($app, 'logo', 'public/assets/images/logo.svg')),
	);

	if ($support_email) {
		$schema['email'] = $support_email;
	}

	if ($support_phone) {
		$schema['telephone'] = $support_phone;
	}

	return $schema;
}

function seo_website_schema()
{
	return array(
		'@type' => 'WebSite',
		'@id' => site_url().'#website',
		'name' => seo_site_name(),
		'url' => site_url(),
		'publisher' => array('@id' => site_url().'#organization'),
		'potentialAction' => array(
			'@type' => 'SearchAction',
			'target' => site_url('search').'?q={search_term_string}',
			'query-input' => 'required name=search_term_string',
		),
	);
}

function seo_breadcrumb_schema(array $items)
{
	$list = array();
	$position = 1;

	foreach ($items as $name => $url) {
		$list[] = array(
			'@type' => 'ListItem',
			'position' => $position++,
			'name' => (string) $name,
			'item' => seo_absolute_url($url),
		);
	}

	return array(
		'@type' => 'BreadcrumbList',
		'itemListElement' => $list,
	);
}

function seo_item_list_schema(array $items, $name = 'Products')
{
	$list = array();
	$position = 1;

	foreach ($items as $item) {
		$list[] = array(
			'@type' => 'ListItem',
			'position' => $position++,
			'url' => site_url('products/'.$item->slug),
			'name' => $item->name,
		);
	}

	return array(
		'@type' => 'ItemList',
		'name' => $name,
		'itemListElement' => $list,
	);
}
