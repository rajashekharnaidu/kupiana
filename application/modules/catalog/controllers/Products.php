<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Store_Controller
{
	public function index()
	{
		$this->load->model('Store_model', 'store');
		$params = $this->filters();
		$pagination = $this->store->products($params);
		$meta = seo_meta(array_merge(array(
			'title' => seo_title('Shop'),
			'description' => 'Browse products, brands and curated collections at Kupiana.',
			'canonical' => site_url('shop'),
			'robots' => $this->has_filters($params) ? 'noindex,follow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
		), seo_pagination_meta($pagination, site_url('shop'), $this->input->get(NULL, TRUE) ?: array())));
		$this->render('shop', array(
			'page_title' => 'Shop',
			'pagination' => $pagination,
			'categories' => $this->store->categories(TRUE),
			'brands' => $this->store->brands(),
			'meta' => $meta,
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Shop' => site_url('shop'))),
				seo_item_list_schema($pagination['data'], 'Kupiana products'),
			)),
		));
	}

	public function detail($slug)
	{
		$this->load->model('Store_model', 'store');
		$product = $this->store->product($slug);
		if ( ! $product) { show_404(); }
		$related = $this->store->products(array('category_id' => $product->category_id, 'per_page' => 4));
		$primary = ! empty($product->images) ? $product->images[0]->image_path : (isset($product->image_path) ? $product->image_path : NULL);
		$product_schema = array(
			'@type' => 'Product',
			'@id' => site_url('products/'.$product->slug).'#product',
			'name' => $product->name,
			'description' => seo_clean_text($product->meta_description ?: ($product->short_description ?: $product->description), 500),
			'sku' => $product->sku,
			'brand' => array('@type' => 'Brand', 'name' => $product->brand_name ?: seo_site_name()),
			'category' => $product->category_name,
			'image' => upload_url($primary),
			'url' => site_url('products/'.$product->slug),
			'offers' => array(
				'@type' => 'Offer',
				'priceCurrency' => array_get($this->config->item('currency', 'app'), 'code', 'INR'),
				'price' => (float) $product->price,
				'availability' => $product->stock_quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'url' => site_url('products/'.$product->slug),
				'itemCondition' => 'https://schema.org/NewCondition',
			),
		);
		if ((int) $product->rating_count > 0) {
			$product_schema['aggregateRating'] = array(
				'@type' => 'AggregateRating',
				'ratingValue' => (float) $product->rating_average,
				'reviewCount' => (int) $product->rating_count,
			);
		}
		$this->render('product_detail', array(
			'product' => $product,
			'related' => $related['data'],
			'meta' => seo_entity_meta('product', $product->id, array(
				'title' => seo_title($product->meta_title ?: $product->name),
				'description' => $product->meta_description ?: ($product->short_description ?: 'View '.$product->name.' pricing, availability and product details at Kupiana.'),
				'keywords' => isset($product->meta_keywords) ? $product->meta_keywords : '',
				'canonical' => site_url('products/'.$product->slug),
				'og_type' => 'product',
				'og_image' => upload_url($primary),
			)),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array(
					'Home' => site_url(),
					'Shop' => site_url('shop'),
					$product->category_name ?: 'Category' => $product->category_slug ? site_url('category/'.$product->category_slug) : site_url('shop'),
					$product->name => site_url('products/'.$product->slug),
				)),
				$product_schema,
				seo_entity_schema('product', $product->id),
			)),
		));
	}

	public function category($slug)
	{
		$this->load->model('Store_model', 'store');
		$category = $this->db->from('categories')->where('slug', $slug)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $category) { show_404(); }
		$params = $this->filters(); $params['category_id'] = $category->id;
		$pagination = $this->store->products($params);
		$base = site_url('category/'.$category->slug);
		$meta = seo_entity_meta('category', $category->id, array_merge(array(
			'title' => seo_title($category->meta_title ?: $category->name),
			'description' => $category->meta_description ?: ($category->description ?: 'Browse '.$category->name.' products at Kupiana.'),
			'canonical' => $base,
			'og_image' => upload_url($category->image),
			'robots' => $this->has_filters($params) ? 'noindex,follow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
		), seo_pagination_meta($pagination, $base, $this->input->get(NULL, TRUE) ?: array())));
		$this->render('shop', array(
			'page_title' => $category->name,
			'category' => $category,
			'pagination' => $pagination,
			'categories' => $this->store->categories(TRUE),
			'brands' => $this->store->brands(),
			'meta' => $meta,
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Shop' => site_url('shop'), $category->name => $base)),
				seo_item_list_schema($pagination['data'], $category->name.' products'),
				seo_entity_schema('category', $category->id),
			)),
		));
	}

	public function search()
	{
		$this->load->model('Store_model', 'store');
		$params = $this->filters();
		$pagination = $this->store->products($params);
		$query = trim((string) array_get($params, 'q'));
		$this->render('shop', array(
			'page_title' => $query ? 'Search results for “'.$query.'”' : 'Search results',
			'pagination' => $pagination,
			'categories' => $this->store->categories(TRUE),
			'brands' => $this->store->brands(),
			'meta' => seo_meta(array_merge(array(
				'title' => seo_title($query ? 'Search results for '.$query : 'Search'),
				'description' => 'Search products, brands and categories at Kupiana.',
				'canonical' => site_url('search'),
				'robots' => 'noindex,follow',
			), seo_pagination_meta($pagination, site_url('search'), $this->input->get(NULL, TRUE) ?: array()))),
		));
	}

	public function brands()
	{
		$this->load->model('Store_model', 'store');
		$brands = $this->store->brands();
		$this->render('brands', array(
			'brands' => $brands,
			'meta' => seo_meta(array('title' => seo_title('Brands'), 'description' => 'Shop curated brands available on Kupiana.', 'canonical' => site_url('brands'))),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Brands' => site_url('brands'))),
			)),
		));
	}

	public function brand($slug)
	{
		$this->load->model('Store_model', 'store');
		$brand = $this->db->from('brands')->where('slug', $slug)->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $brand) { show_404(); }
		$params = $this->filters(); $params['brand_id'] = $brand->id;
		$pagination = $this->store->products($params);
		$base = site_url('brand/'.$brand->slug);
		$this->render('shop', array(
			'page_title' => $brand->name,
			'brand' => $brand,
			'pagination' => $pagination,
			'categories' => $this->store->categories(TRUE),
			'brands' => $this->store->brands(),
			'meta' => seo_entity_meta('brand', $brand->id, array_merge(array(
				'title' => seo_title($brand->meta_title ?: $brand->name),
				'description' => $brand->meta_description ?: ($brand->description ?: 'Browse '.$brand->name.' products at Kupiana.'),
				'canonical' => $base,
				'og_image' => upload_url($brand->logo),
				'robots' => $this->has_filters($params) ? 'noindex,follow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
			), seo_pagination_meta($pagination, $base, $this->input->get(NULL, TRUE) ?: array()))),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Brands' => site_url('brands'), $brand->name => $base)),
				seo_item_list_schema($pagination['data'], $brand->name.' products'),
				seo_entity_schema('brand', $brand->id),
			)),
		));
	}

	public function deals()
	{
		$this->load->model('Store_model', 'store');
		$params = $this->filters(); $params['trending'] = 1;
		$pagination = $this->store->products($params);
		$this->render('shop', array(
			'page_title' => 'Deals & Trending',
			'pagination' => $pagination,
			'categories' => $this->store->categories(TRUE),
			'brands' => $this->store->brands(),
			'meta' => seo_meta(array_merge(array(
				'title' => seo_title('Deals'),
				'description' => 'Discover current deals and trending products at Kupiana.',
				'canonical' => site_url('deals'),
				'robots' => $this->has_filters($params) ? 'noindex,follow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
			), seo_pagination_meta($pagination, site_url('deals'), $this->input->get(NULL, TRUE) ?: array()))),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Deals' => site_url('deals'))),
				seo_item_list_schema($pagination['data'], 'Kupiana deals'),
			)),
		));
	}

	public function offers()
	{
		$this->load->model('Store_model', 'store');
		$this->render('offers', array(
			'offers' => $this->store->offers(20),
			'meta' => seo_meta(array('title' => seo_title('Offers'), 'description' => 'Current Kupiana offers and promotions.', 'canonical' => site_url('offers'))),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Offers' => site_url('offers'))),
			)),
		));
	}

	protected function filters()
	{
		return array('q' => $this->input->get('q', TRUE), 'sort' => $this->input->get('sort', TRUE), 'min_price' => $this->input->get('min_price', TRUE), 'max_price' => $this->input->get('max_price', TRUE), 'page' => $this->input->get('page', TRUE), 'per_page' => $this->input->get('per_page', TRUE));
	}

	protected function has_filters(array $params)
	{
		foreach (array('q', 'sort', 'min_price', 'max_price', 'per_page') as $key) {
			if (array_get($params, $key) !== NULL && array_get($params, $key) !== '') {
				return TRUE;
			}
		}

		return FALSE;
	}
}
