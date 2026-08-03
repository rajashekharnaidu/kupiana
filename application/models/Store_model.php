<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Store_model extends CI_Model
{
	protected $memory = array();

	public function products(array $params = array())
	{
		$page = max(1, (int) array_get($params, 'page', 1));

		// Callers build $params straight from the query string, so 'per_page'
		// and 'page' are PRESENT but NULL when the visitor did not supply them.
		// array_get() only falls back on a missing key, not a null value, so
		// the default never applied and every listing paginated at one product
		// per page. Coalesce on emptiness, not on absence.
		$requested = array_get($params, 'per_page');
		$per_page  = (int) ($requested !== NULL && $requested !== '' ? $requested : app_config('store_per_page', 12));
		$per_page  = min(48, max(1, $per_page));
		$total = $this->product_query($params)->count_all_results();
		$rows = $this->product_query($params)
			->order_by($this->sort_column(array_get($params, 'sort')), $this->sort_order(array_get($params, 'sort')))
			->limit($per_page, ($page - 1) * $per_page)
			->get()->result();
		return array('data' => $this->attach_images($rows), 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => (int) ceil($total / $per_page), 'from' => $total ? (($page - 1) * $per_page) + 1 : 0, 'to' => min($page * $per_page, $total));
	}

	public function featured($limit = 8)
	{
		$limit = (int) $limit;
		return $this->remember('featured_'.$limit, 300, function () use ($limit) {
			return $this->attach_images($this->product_query(array('featured' => 1))->order_by('products.sold_count', 'DESC')->limit($limit)->get()->result());
		});
	}

	public function product($slug)
	{
		$row = $this->product_query(array('slug' => $slug))->limit(1)->get()->row();
		if ( ! $row) { return NULL; }
		$row->images = $this->db->from('product_images')->where('product_id', $row->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_primary', 'DESC')->order_by('sort_order', 'ASC')->get()->result();
		$row->variants = $this->db->from('product_variants')->where('product_id', $row->id)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_default', 'DESC')->get()->result();
		$row->tags = $this->db->select('tags.*')->from('product_tags')->join('tags', 'tags.id = product_tags.tag_id')->where('product_tags.product_id', $row->id)->where('product_tags.deleted_at IS NULL', NULL, FALSE)->where('tags.deleted_at IS NULL', NULL, FALSE)->get()->result();
		return $row;
	}

	public function categories($parents_only = FALSE)
	{
		return $this->remember('categories_'.($parents_only ? 'parents' : 'all'), 600, function () use ($parents_only) {
			$this->db->from('categories')->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->order_by('sort_order', 'ASC')->order_by('name', 'ASC');
			if ($parents_only) { $this->db->group_start()->where('parent_id', NULL)->or_where('parent_id', 0)->group_end(); }
			return $this->db->get()->result();
		});
	}

	public function mega_menu()
	{
		return $this->remember('mega_menu', 600, function () {
			$rows = $this->db->from('categories')->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get()->result();
			$parents = array(); $children = array();
			foreach ($rows as $row)
			{
				$row->children = array();
				$parent_id = (int) $row->parent_id;
				if ($parent_id > 0) { $children[$parent_id][] = $row; }
				else { $parents[$row->id] = $row; }
			}
			foreach ($parents as $id => $parent) { $parent->children = isset($children[$id]) ? $children[$id] : array(); }
			return array_values($parents);
		});
	}

	public function brands($limit = 0)
	{
		$limit = (int) $limit;
		return $this->remember('brands_'.$limit, 600, function () use ($limit) {
			$this->db->from('brands')->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->order_by('sort_order', 'ASC')->order_by('name', 'ASC');
			if ($limit) { $this->db->limit($limit); }
			return $this->db->get()->result();
		});
	}

	public function banners($position = 'home_slider')
	{
		$now = date('Y-m-d H:i:s');
		return $this->db->from('banners')->where('position', $position)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)
			->group_start()->where('starts_at IS NULL', NULL, FALSE)->or_where('starts_at <=', $now)->group_end()
			->group_start()->where('ends_at IS NULL', NULL, FALSE)->or_where('ends_at >=', $now)->group_end()
			->order_by('sort_order', 'ASC')->get()->result();
	}

	public function testimonials($limit = 6)
	{
		$limit = (int) $limit;
		return $this->remember('testimonials_'.$limit, 600, function () use ($limit) {
			return $this->db->from('testimonials')->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_featured', 'DESC')->order_by('sort_order', 'ASC')->limit($limit)->get()->result();
		});
	}

	public function offers($limit = 6)
	{
		$now = date('Y-m-d H:i:s');
		return $this->db->from('offers')->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)
			->group_start()->where('starts_at IS NULL', NULL, FALSE)->or_where('starts_at <=', $now)->group_end()
			->group_start()->where('ends_at IS NULL', NULL, FALSE)->or_where('ends_at >=', $now)->group_end()
			->order_by('priority', 'DESC')->limit((int) $limit)->get()->result();
	}

	public function page($slug)
	{
		$slug = (string) $slug;
		return $this->remember('page_'.$slug, 600, function () use ($slug) {
			return $this->db->from('pages')->where('slug', $slug)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		});
	}

	public function cart_items($identity)
	{
		$cart = $this->cart($identity, FALSE);
		if ( ! $cart) { return array(); }
		$items = $this->db->select('cart_items.*, products.name, products.slug, products.sku, products.mrp, products.stock_quantity, brands.name AS brand_name')
			->from('cart_items')->join('products', 'products.id = cart_items.product_id')->join('brands', 'brands.id = products.brand_id', 'left')
			->where('cart_items.cart_id', $cart->id)->where('cart_items.deleted_at IS NULL', NULL, FALSE)->where('products.deleted_at IS NULL', NULL, FALSE)->get()->result();
		return $this->attach_images($items);
	}

	public function cart_count($identity)
	{
		$cart = $this->cart($identity, FALSE);
		if ( ! $cart) { return 0; }
		return (int) $this->db->select_sum('quantity')->from('cart_items')->where('cart_id', (int) $cart->id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row()->quantity;
	}

	public function wishlist_count($user_id)
	{
		return (int) $this->db->from('wishlists')->where('user_id', (int) $user_id)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
	}

	public function cart($identity, $create = TRUE)
	{
		$where = ! empty($identity['user_id']) ? array('user_id' => $identity['user_id']) : array('session_id' => $identity['session_id']);
		$cart = $this->db->from('carts')->where($where)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ($cart || ! $create) { return $cart; }
		$now = date('Y-m-d H:i:s');
		$this->db->insert('carts', array_merge($where, array('status' => 'active', 'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')), 'created_at' => $now, 'updated_at' => $now)));
		return $this->db->from('carts')->where('id', (int) $this->db->insert_id())->get()->row();
	}

	protected function product_query(array $params)
	{
		$this->db->select('products.*, brands.name AS brand_name, categories.name AS category_name, categories.slug AS category_slug')
			->from('products')->join('brands', 'brands.id = products.brand_id', 'left')->join('categories', 'categories.id = products.category_id', 'left')
			->where('products.status', 'active')->where('products.deleted_at IS NULL', NULL, FALSE);
		if (array_get($params, 'slug')) { $this->db->where('products.slug', $params['slug']); }
		if (array_get($params, 'category_id')) {
			// Match through the product_categories pivot, not products.category_id.
			// The latter holds only the PRIMARY category, which for this catalogue
			// is a child ("Turmeric & Ginger"), so browsing a parent ("Organic
			// Spices") returned nothing even though the pivot maps it correctly.
			// A subquery keeps this free of the row duplication a JOIN would add.
			$cid = (int) $params['category_id'];
			$this->db->where(
				'products.id IN (SELECT pc.product_id FROM product_categories pc'
				.' WHERE pc.category_id = '.$cid.' AND pc.deleted_at IS NULL)', NULL, FALSE
			);
		}
		if (array_get($params, 'brand_id')) { $this->db->where('products.brand_id', (int) $params['brand_id']); }
		if (array_get($params, 'featured')) { $this->db->where('products.is_featured', 1); }
		if (array_get($params, 'trending')) { $this->db->where('products.is_trending', 1); }
		if (array_get($params, 'q')) { $this->db->group_start()->like('products.name', $params['q'])->or_like('products.sku', $params['q'])->or_like('products.short_description', $params['q'])->group_end(); }
		$min = array_get($params, 'min_price'); $max = array_get($params, 'max_price');
		if ($min !== NULL && $min !== '') { $this->db->where('products.price >=', (float) $min); }
		if ($max !== NULL && $max !== '') { $this->db->where('products.price <=', (float) $max); }
		return $this->db;
	}

	public function attach_images(array $rows)
	{
		if (empty($rows)) { return $rows; }
		$ids = array();
		foreach ($rows as $row) { $ids[] = isset($row->product_id) ? (int) $row->product_id : (int) $row->id; }
		$images = array();
		foreach ($this->db->from('product_images')->where_in('product_id', $ids)->where('deleted_at IS NULL', NULL, FALSE)->order_by('is_primary', 'DESC')->order_by('sort_order', 'ASC')->get()->result() as $image)
		{
			if ( ! isset($images[$image->product_id])) { $images[$image->product_id] = $image->image_path; }
		}
		foreach ($rows as $row)
		{
			$key = isset($row->product_id) ? (int) $row->product_id : (int) $row->id;
			$row->image_path = isset($images[$key]) ? $images[$key] : NULL;
		}
		return $rows;
	}

	protected function sort_column($sort)
	{
		$map = array('price_asc' => 'products.price', 'price_desc' => 'products.price', 'newest' => 'products.created_at', 'popular' => 'products.sold_count');
		return isset($map[$sort]) ? $map[$sort] : 'products.is_featured';
	}

	protected function sort_order($sort)
	{
		return $sort === 'price_asc' ? 'ASC' : 'DESC';
	}

	protected function remember($key, $ttl, callable $callback)
	{
		if (array_key_exists($key, $this->memory)) { return $this->memory[$key]; }
		if (isset($this->app_cache))
		{
			$this->memory[$key] = $this->app_cache->remember('store_'.$key, $ttl, $callback);
			return $this->memory[$key];
		}
		$this->memory[$key] = call_user_func($callback);
		return $this->memory[$key];
	}
}
