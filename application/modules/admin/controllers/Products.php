<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends Admin_Controller
{
	protected $active_menu = 'catalog.products';
	protected $required_permission = 'products.manage';

	public function manage($id = NULL)
	{
		$product = $this->product((int) $id);
		if ( ! $product) { show_404(); }
		$this->breadcrumb('Products', 'admin/products');
		$this->breadcrumb('Manage '.$product->name);
		$this->render('product_manage', array(
			'page_title' => 'Manage '.$product->name,
			'product' => $product,
			'images' => $this->rows('product_images', array('product_id' => $product->id), 'sort_order'),
			'variants' => $this->variants($product->id),
			'categories' => $this->options('categories', 'name'),
			'tags' => $this->options('tags', 'name'),
			'attributes' => $this->attribute_options(),
			'selected_categories' => $this->selected('product_categories', 'category_id', $product->id),
			'selected_tags' => $this->selected('product_tags', 'tag_id', $product->id),
		));
	}

	public function save_relations($id = NULL)
	{
		$product = $this->product((int) $id);
		if ( ! $product) { show_404(); }
		$this->sync_pivot('product_categories', 'category_id', $product->id, (array) $this->input->post('category_ids', TRUE));
		$this->sync_pivot('product_tags', 'tag_id', $product->id, (array) $this->input->post('tag_ids', TRUE));
		$this->audit->log('product_relations_update', 'products', $product->id, 'Product categories and tags updated.');
		$this->session->set_flashdata('success', 'Product relationships saved.');
		redirect('admin/products/manage/'.$product->id);
	}

	public function upload_image($id = NULL)
	{
		$product = $this->product((int) $id);
		if ( ! $product) { show_404(); }
		include_once APPPATH.'libraries/Upload.php';
		$upload = (new Upload())->image('image', 'products');
		if ($upload === FALSE)
		{
			$this->session->set_flashdata('error', 'Choose a valid image file.');
			redirect('admin/products/manage/'.$product->id);
		}
		$now = date('Y-m-d H:i:s');
		$this->db->insert('product_images', array(
			'product_id' => $product->id,
			'variant_id' => (int) $this->input->post('variant_id', TRUE) ?: NULL,
			'image_path' => 'products/'.$upload['name'],
			'alt_text' => trim((string) $this->input->post('alt_text', TRUE)) ?: $product->name,
			'sort_order' => (int) $this->input->post('sort_order', TRUE),
			'is_primary' => $this->input->post('is_primary', TRUE) ? 1 : 0,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => (int) $this->session->userdata('user_id') ?: NULL,
			'updated_by' => (int) $this->session->userdata('user_id') ?: NULL,
		));
		if ($this->input->post('is_primary', TRUE))
		{
			$this->db->where('product_id', $product->id)->where('id !=', $this->db->insert_id())->update('product_images', array('is_primary' => 0));
		}
		$this->audit->log('product_image_upload', 'products', $product->id, 'Product image uploaded.');
		$this->session->set_flashdata('success', 'Image uploaded.');
		redirect('admin/products/manage/'.$product->id);
	}

	public function delete_image($product_id = NULL, $image_id = NULL)
	{
		$product = $this->product((int) $product_id);
		if ( ! $product) { show_404(); }
		$this->db->where(array('id' => (int) $image_id, 'product_id' => $product->id))->update('product_images', array('deleted_at' => date('Y-m-d H:i:s'), 'updated_by' => (int) $this->session->userdata('user_id') ?: NULL));
		$this->audit->log('product_image_delete', 'products', $product->id, 'Product image removed.', array('image_id' => (int) $image_id));
		$this->session->set_flashdata('success', 'Image removed.');
		redirect('admin/products/manage/'.$product->id);
	}

	public function save_variant_attributes($product_id = NULL, $variant_id = NULL)
	{
		$product = $this->product((int) $product_id);
		$variant = $this->db->from('product_variants')->where(array('id' => (int) $variant_id, 'product_id' => (int) $product_id))->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
		if ( ! $product || ! $variant) { show_404(); }
		$values = (array) $this->input->post('attributes', TRUE);
		$now = date('Y-m-d H:i:s');
		$user_id = (int) $this->session->userdata('user_id') ?: NULL;
		foreach ($values as $attribute_id => $attribute_value_id)
		{
			$attribute_id = (int) $attribute_id; $attribute_value_id = (int) $attribute_value_id;
			if ($attribute_id <= 0 || $attribute_value_id <= 0) { continue; }
			$exists = $this->db->where(array('variant_id' => $variant->id, 'attribute_id' => $attribute_id))->count_all_results('variant_attribute_values') > 0;
			$row = array('attribute_value_id' => $attribute_value_id, 'status' => 'active', 'updated_at' => $now, 'updated_by' => $user_id, 'deleted_at' => NULL);
			if ($exists) { $this->db->where(array('variant_id' => $variant->id, 'attribute_id' => $attribute_id))->update('variant_attribute_values', $row); }
			else { $row += array('variant_id' => $variant->id, 'attribute_id' => $attribute_id, 'created_at' => $now, 'created_by' => $user_id); $this->db->insert('variant_attribute_values', $row); }
		}
		$this->audit->log('variant_attributes_update', 'product_variants', $variant->id, 'Variant attributes updated.');
		$this->session->set_flashdata('success', 'Variant attributes saved.');
		redirect('admin/products/manage/'.$product->id);
	}

	protected function product($id)
	{
		return $this->db->from('products')->where('id', (int) $id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
	}

	protected function rows($table, array $where, $sort = 'id')
	{
		return $this->db->from($table)->where($where)->where('deleted_at IS NULL', NULL, FALSE)->order_by($sort, 'ASC')->get()->result();
	}

	protected function options($table, $label)
	{
		$options = array();
		foreach ($this->rows($table, array('status' => 'active'), $label) as $row) { $options[$row->id] = $row->{$label}; }
		return $options;
	}

	protected function selected($table, $column, $product_id)
	{
		$ids = array();
		foreach ($this->rows($table, array('product_id' => (int) $product_id), $column) as $row) { $ids[] = (int) $row->{$column}; }
		return $ids;
	}

	protected function sync_pivot($table, $column, $product_id, array $ids)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$now = date('Y-m-d H:i:s'); $user_id = (int) $this->session->userdata('user_id') ?: NULL;
		$this->db->where('product_id', (int) $product_id)->update($table, array('deleted_at' => $now, 'updated_by' => $user_id));
		foreach ($ids as $id)
		{
			$exists = $this->db->where(array('product_id' => (int) $product_id, $column => $id))->count_all_results($table) > 0;
			$data = array('status' => 'active', 'deleted_at' => NULL, 'updated_at' => $now, 'updated_by' => $user_id);
			if ($exists) { $this->db->where(array('product_id' => (int) $product_id, $column => $id))->update($table, $data); }
			else { $data += array('product_id' => (int) $product_id, $column => $id, 'created_at' => $now, 'created_by' => $user_id); $this->db->insert($table, $data); }
		}
	}

	protected function variants($product_id)
	{
		$variants = $this->rows('product_variants', array('product_id' => (int) $product_id), 'id');
		foreach ($variants as $variant)
		{
			$variant->attributes = array();
			foreach ($this->rows('variant_attribute_values', array('variant_id' => $variant->id), 'attribute_id') as $row) { $variant->attributes[(int) $row->attribute_id] = (int) $row->attribute_value_id; }
		}
		return $variants;
	}

	protected function attribute_options()
	{
		$attributes = array();
		foreach ($this->options('attributes', 'name') as $id => $name) { $attributes[$id] = array('name' => $name, 'values' => array()); }
		foreach ($this->rows('attribute_values', array('status' => 'active'), 'sort_order') as $value)
		{
			if (isset($attributes[$value->attribute_id])) { $attributes[$value->attribute_id]['values'][$value->id] = $value->value; }
		}
		return $attributes;
	}
}
