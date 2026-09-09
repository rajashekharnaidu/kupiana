<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Coupon lookup, eligibility checks and discount calculation shared by the
 * cart, checkout and order-placement flows.
 */
class Coupon_model extends MY_Model
{
	protected $table      = 'coupons';
	protected $fillable   = array('code', 'name', 'description', 'type', 'value', 'min_order_amount', 'max_discount_amount', 'usage_limit', 'usage_limit_per_user', 'applies_to', 'first_order_only', 'starts_at', 'expires_at', 'status');
	protected $searchable = array('code', 'name');
	protected $sortable   = array('code', 'name', 'created_at');

	/**
	 * Active coupon matching a customer-entered code, case-insensitive.
	 *
	 * @param  string $code
	 * @return object|null
	 */
	public function find_by_code($code)
	{
		$code = trim((string) $code);
		if ($code === '') { return NULL; }

		return $this->db->from('coupons')
			->where('UPPER(code) =', strtoupper($code), FALSE)
			->where('status', 'active')
			->where('deleted_at IS NULL', NULL, FALSE)
			->get()->row();
	}

	/**
	 * Validate a coupon against the current cart/customer and compute its discount.
	 *
	 * @param  object $coupon
	 * @param  array  $context {user_id: int|null, email: string|null, items: object[], subtotal: float}
	 * @return array {success: bool, message: string, discount: float, free_shipping: bool}
	 */
	public function evaluate($coupon, array $context)
	{
		$user_id  = array_get($context, 'user_id');
		$email    = array_get($context, 'email');
		$items    = (array) array_get($context, 'items', array());
		$subtotal = (float) array_get($context, 'subtotal', 0);

		if ( ! $coupon || $coupon->status !== 'active')
		{
			return $this->invalid('This coupon is no longer active.');
		}

		$now = date('Y-m-d H:i:s');
		if ( ! empty($coupon->starts_at) && $coupon->starts_at > $now)
		{
			return $this->invalid('This coupon is not active yet.');
		}
		if ( ! empty($coupon->expires_at) && $coupon->expires_at < $now)
		{
			return $this->invalid('This coupon has expired.');
		}

		if ($subtotal < (float) $coupon->min_order_amount)
		{
			return $this->invalid('This coupon needs a minimum order of '.money($coupon->min_order_amount).'.');
		}

		if ($coupon->usage_limit !== NULL && (int) $coupon->used_count >= (int) $coupon->usage_limit)
		{
			return $this->invalid('This coupon has reached its usage limit.');
		}

		if ($coupon->usage_limit_per_user !== NULL && $user_id)
		{
			$used = (int) $this->db->from('coupon_usages')->where('coupon_id', $coupon->id)->where('user_id', $user_id)->where('deleted_at IS NULL', NULL, FALSE)->count_all_results();
			if ($used >= (int) $coupon->usage_limit_per_user)
			{
				return $this->invalid('You have already used this coupon.');
			}
		}

		if ($coupon->first_order_only && ($user_id || $email) && $this->has_prior_orders($user_id, $email))
		{
			return $this->invalid('This coupon is valid for first orders only.');
		}

		$allowed_users = $this->restricted_ids($coupon->id, 'user');
		if ( ! empty($allowed_users) && ! in_array((int) $user_id, $allowed_users, TRUE))
		{
			return $this->invalid('This coupon is not valid for your account.');
		}

		$eligible_subtotal = $this->eligible_subtotal($coupon, $items);
		if ($eligible_subtotal <= 0)
		{
			return $this->invalid('This coupon does not apply to the items in your cart.');
		}

		if ($coupon->type === 'free_shipping')
		{
			return array('success' => TRUE, 'message' => 'Coupon applied.', 'discount' => 0.0, 'free_shipping' => TRUE);
		}

		if ($coupon->type === 'fixed')
		{
			$discount = min((float) $coupon->value, $eligible_subtotal);
		}
		else
		{
			$discount = $eligible_subtotal * (float) $coupon->value / 100;
			if ($coupon->max_discount_amount !== NULL)
			{
				$discount = min($discount, (float) $coupon->max_discount_amount);
			}
		}

		return array('success' => TRUE, 'message' => 'Coupon applied.', 'discount' => round($discount, 2), 'free_shipping' => FALSE);
	}

	/**
	 * Record that a coupon was consumed by a placed order.
	 *
	 * @param  int      $coupon_id
	 * @param  int|null $user_id
	 * @param  int      $order_id
	 * @param  float    $discount_amount
	 * @return void
	 */
	public function record_usage($coupon_id, $user_id, $order_id, $discount_amount)
	{
		$now = date('Y-m-d H:i:s');
		$this->db->insert('coupon_usages', array(
			'coupon_id' => (int) $coupon_id,
			'user_id' => $user_id ?: NULL,
			'order_id' => (int) $order_id,
			'discount_amount' => (float) $discount_amount,
			'status' => 'active',
			'created_at' => $now,
			'updated_at' => $now,
			'created_by' => $user_id ?: NULL,
			'updated_by' => $user_id ?: NULL,
		));
		$this->db->where('id', (int) $coupon_id)->set('used_count', 'used_count + 1', FALSE)->update('coupons');
	}

	/** @return array */
	protected function invalid($message)
	{
		return array('success' => FALSE, 'message' => $message, 'discount' => 0.0, 'free_shipping' => FALSE);
	}

	/** @param int|null $user_id @param string|null $email @return bool */
	protected function has_prior_orders($user_id, $email)
	{
		$this->db->from('orders')->where('deleted_at IS NULL', NULL, FALSE)->where('order_status !=', 'cancelled');
		if ($user_id)
		{
			$this->db->group_start()->where('user_id', (int) $user_id);
			if ($email) { $this->db->or_where('customer_email', strtolower(trim($email))); }
			$this->db->group_end();
		}
		else
		{
			$this->db->where('customer_email', strtolower(trim((string) $email)));
		}
		return $this->db->count_all_results() > 0;
	}

	/** @param int $coupon_id @param string $type @return int[] */
	protected function restricted_ids($coupon_id, $type)
	{
		$rows = $this->db->select('reference_id')->from('coupon_restrictions')
			->where('coupon_id', (int) $coupon_id)->where('restrict_type', $type)
			->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)
			->get()->result();
		return array_map(function ($row) { return (int) $row->reference_id; }, $rows);
	}

	/**
	 * Sum of cart line totals the coupon is allowed to discount.
	 *
	 * @param  object $coupon
	 * @param  object[] $items
	 * @return float
	 */
	protected function eligible_subtotal($coupon, array $items)
	{
		if ($coupon->applies_to === 'all')
		{
			$product_ids = NULL;
		}
		elseif ($coupon->applies_to === 'products')
		{
			$product_ids = $this->restricted_ids($coupon->id, 'product');
		}
		else
		{
			$category_ids = $this->restricted_ids($coupon->id, 'category');
			$item_product_ids = array_map(function ($item) { return (int) $item->product_id; }, $items);
			$product_ids = empty($category_ids) || empty($item_product_ids) ? array() : array_map(function ($row) { return (int) $row->product_id; },
				$this->db->select('DISTINCT product_id')->from('product_categories')
					->where_in('category_id', $category_ids)->where_in('product_id', $item_product_ids)
					->where('deleted_at IS NULL', NULL, FALSE)->get()->result()
			);
		}

		$subtotal = 0.0;
		foreach ($items as $item)
		{
			if ($product_ids !== NULL && ! in_array((int) $item->product_id, $product_ids, TRUE)) { continue; }
			$subtotal += (float) $item->unit_price * (int) $item->quantity;
		}
		return round($subtotal, 2);
	}
}
