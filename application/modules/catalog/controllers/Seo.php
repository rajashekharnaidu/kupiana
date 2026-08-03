<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Seo extends MY_Controller
{
	public function robots()
	{
		$lines = array(
			'User-agent: *',
			'Allow: /',
			'Disallow: /admin',
			'Disallow: /account',
			'Disallow: /cart',
			'Disallow: /checkout',
			'Disallow: /wishlist',
			'Disallow: /login',
			'Disallow: /logout',
			'Disallow: /register',
			'Disallow: /forgot-password',
			'Disallow: /reset-password',
			'Disallow: /verify-email',
			'Disallow: /resend-verification',
			'Disallow: /payments/',
			'Disallow: /api/',
			'Disallow: /*?q=',
			'Disallow: /*?sort=',
			'Disallow: /*?min_price=',
			'Disallow: /*?max_price=',
			'Sitemap: '.site_url('sitemap.xml'),
			'',
		);

		$this->output
			->set_content_type('text/plain')
			->set_header('X-Robots-Tag: noindex')
			->set_output(implode("\n", $lines));
	}

	public function sitemap()
	{
		$urls = array(
			array('loc' => site_url(), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '1.0'),
			array('loc' => site_url('shop'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '0.9'),
			array('loc' => site_url('brands'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'weekly', 'priority' => '0.7'),
			array('loc' => site_url('offers'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '0.7'),
			array('loc' => site_url('deals'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'daily', 'priority' => '0.8'),
			array('loc' => site_url('blog'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'weekly', 'priority' => '0.6'),
			array('loc' => site_url('contact'), 'lastmod' => date('Y-m-d'), 'changefreq' => 'monthly', 'priority' => '0.4'),
		);

		$categories = $this->db
			->select('slug, updated_at')
			->where('categories.status', 'active')
			->where('categories.deleted_at IS NULL', NULL, FALSE)
			->get('categories')
			->result();

		foreach ($categories as $category) {
			$urls[] = array(
				'loc' => site_url('category/'.$category->slug),
				'lastmod' => $this->lastmod($category->updated_at),
				'changefreq' => 'weekly',
				'priority' => '0.8',
			);
		}

		$brands = $this->db
			->select('slug, updated_at')
			->where('brands.status', 'active')
			->where('brands.deleted_at IS NULL', NULL, FALSE)
			->get('brands')
			->result();

		foreach ($brands as $brand) {
			$urls[] = array(
				'loc' => site_url('brand/'.$brand->slug),
				'lastmod' => $this->lastmod($brand->updated_at),
				'changefreq' => 'weekly',
				'priority' => '0.7',
			);
		}

		$products = $this->db
			->select('products.slug, products.updated_at, products.name, images.image_path')
			->join('(SELECT product_id, MIN(image_path) AS image_path FROM product_images WHERE deleted_at IS NULL GROUP BY product_id) images', 'images.product_id = products.id', 'left')
			->where('products.status', 'active')
			->where('products.deleted_at IS NULL', NULL, FALSE)
			->get('products')
			->result();

		foreach ($products as $product) {
			$urls[] = array(
				'loc' => site_url('products/'.$product->slug),
				'lastmod' => $this->lastmod($product->updated_at),
				'changefreq' => 'weekly',
				'priority' => '0.9',
				'image' => $product->image_path ? upload_url($product->image_path) : '',
				'image_title' => $product->name,
			);
		}

		$pages = $this->db
			->select('slug, updated_at')
			->where('pages.status', 'active')
			->where('pages.deleted_at IS NULL', NULL, FALSE)
			->get('pages')
			->result();

		foreach ($pages as $page) {
			$urls[] = array(
				'loc' => site_url('page/'.$page->slug),
				'lastmod' => $this->lastmod($page->updated_at),
				'changefreq' => 'monthly',
				'priority' => '0.5',
			);
		}

		$posts = $this->db
			->select('slug, updated_at, published_at')
			->where('blog_posts.status', 'active')
			->where('blog_posts.deleted_at IS NULL', NULL, FALSE)
			->get('blog_posts')
			->result();

		foreach ($posts as $post) {
			$urls[] = array(
				'loc' => site_url('blog/'.$post->slug),
				'lastmod' => $this->lastmod($post->updated_at ?: $post->published_at),
				'changefreq' => 'monthly',
				'priority' => '0.6',
			);
		}

		$this->output
			->set_content_type('application/xml')
			->set_header('X-Robots-Tag: noindex')
			->set_output($this->load->view('sitemap', array('urls' => $urls), TRUE));
	}

	protected function lastmod($date)
	{
		return $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
	}
}
