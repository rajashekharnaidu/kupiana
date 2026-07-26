<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Seo extends MY_Controller
{
	public function robots()
	{
		$this->output
			->set_content_type('text/plain')
			->set_output("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /account\nSitemap: ".site_url('sitemap.xml')."\n");
	}

	public function sitemap()
	{
		$urls = array(
			array('loc' => site_url(), 'priority' => '1.0'),
		);

		$categories = $this->db
			->select('slug, updated_at')
			->where('is_active', 1)
			->get('categories')
			->result();

		foreach ($categories as $category) {
			$urls[] = array(
				'loc' => site_url('category/'.$category->slug),
				'lastmod' => $this->lastmod($category->updated_at),
				'priority' => '0.8',
			);
		}

		$products = $this->db
			->select('slug, updated_at')
			->where('status', 'active')
			->get('products')
			->result();

		foreach ($products as $product) {
			$urls[] = array(
				'loc' => site_url('products/'.$product->slug),
				'lastmod' => $this->lastmod($product->updated_at),
				'priority' => '0.9',
			);
		}

		$this->output
			->set_content_type('application/xml')
			->set_output($this->load->view('sitemap', array('urls' => $urls), TRUE));
	}

	protected function lastmod($date)
	{
		return $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
	}
}
