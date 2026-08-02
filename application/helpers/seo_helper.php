<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function seo_meta($meta = array())
{
	$defaults = array(
		'title' => 'Kupiana',
		'description' => 'Shop curated ecommerce products from Kupiana.',
		'canonical' => current_url(),
		'robots' => 'index,follow',
		'og_type' => 'website',
		'og_image' => base_url('public/assets/images/placeholder.svg'),
	);

	return array_merge($defaults, $meta);
}

function seo_title($title)
{
	return html_escape($title).' | Kupiana';
}

function product_slug($name, $id = NULL)
{
	$slug = url_title(convert_accented_characters($name), '-', TRUE);
	return $id ? $slug.'-'.$id : $slug;
}
