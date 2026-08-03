<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends Store_Controller
{
	public function show($slug)
	{
		$this->load->model('Store_model', 'store');
		$page = $this->store->page($slug);
		if ( ! $page) { show_404(); }
		$this->render('page', array(
			'page' => $page,
			'meta' => seo_entity_meta('page', $page->id, array(
				'title' => seo_title($page->meta_title ?: $page->title),
				'description' => $page->meta_description ?: strip_tags(mb_strimwidth((string) $page->content, 0, 160)),
				'canonical' => site_url('page/'.$page->slug),
			)),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), $page->title => site_url('page/'.$page->slug))),
				array(
					'@type' => 'WebPage',
					'@id' => site_url('page/'.$page->slug).'#webpage',
					'name' => $page->title,
					'description' => seo_clean_text($page->meta_description ?: $page->content, 200),
					'url' => site_url('page/'.$page->slug),
				),
				seo_entity_schema('page', $page->id),
			)),
		));
	}

	public function contact()
	{
		if (strtoupper($this->input->method(TRUE)) === 'POST')
		{
			$this->form_validation->set_rules('name', 'Name', 'required|max_length[150]');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[191]');
			$this->form_validation->set_rules('phone', 'Phone', 'max_length[20]');
			$this->form_validation->set_rules('subject', 'Subject', 'max_length[255]');
			$this->form_validation->set_rules('message', 'Message', 'required|min_length[10]');

			if ($this->form_validation->run() !== TRUE)
			{
				$this->session->set_flashdata('error', strip_tags(validation_errors()));
				redirect('contact');
			}

			$now = date('Y-m-d H:i:s');
			$this->db->insert('contact_messages', array(
				'name'       => $this->input->post('name', TRUE),
				'email'      => strtolower(trim((string) $this->input->post('email', TRUE))),
				'phone'      => $this->input->post('phone', TRUE),
				'subject'    => $this->input->post('subject', TRUE),
				'message'    => $this->input->post('message', TRUE),
				'ip_address' => $this->input->ip_address(),
				'status'     => 'active',
				'created_at' => $now,
				'updated_at' => $now,
				'created_by' => $this->auth->id(),
				'updated_by' => $this->auth->id(),
			));

			$this->session->set_flashdata('success', 'Thanks, your message has been sent. We will get back to you soon.');
			redirect('contact');
		}

		$this->render('contact', array(
			'meta' => seo_meta(array('title' => seo_title('Contact'), 'description' => 'Contact Kupiana support for order help, returns and product questions.', 'canonical' => site_url('contact'))),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Contact' => site_url('contact'))),
				array('@type' => 'ContactPage', '@id' => site_url('contact').'#contact', 'name' => 'Contact '.seo_site_name(), 'url' => site_url('contact')),
			)),
		));
	}

	public function track_order()
	{
		$order_number = trim((string) $this->input->get('order', TRUE));
		$identity = trim((string) $this->input->get('identity', TRUE));
		$order = NULL;
		$history = array();
		$shipments = array();
		$tracking = array();

		if ($order_number !== '' && $identity !== '')
		{
			$order = $this->db
				->from('orders')
				->where('order_number', $order_number)
				->where('deleted_at IS NULL', NULL, FALSE)
				->group_start()
					->where('customer_email', strtolower($identity))
					->or_where('customer_phone', $identity)
				->group_end()
				->limit(1)
				->get()
				->row();

			if ($order)
			{
				$history = $this->db->from('order_status_history')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result();
				$shipments = $this->db->from('shipments')->where('order_id', $order->id)->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result();
				if ( ! empty($shipments))
				{
					$shipment_ids = array_map(function ($shipment) { return (int) $shipment->id; }, $shipments);
					$tracking = $this->db->from('shipment_tracking')->where_in('shipment_id', $shipment_ids)->where('deleted_at IS NULL', NULL, FALSE)->order_by('occurred_at', 'DESC')->get()->result();
				}
			}
		}

		$this->render('track_order', array(
			'searched' => ($order_number !== '' || $identity !== ''),
			'order' => $order,
			'history' => $history,
			'shipments' => $shipments,
			'tracking' => $tracking,
			'meta' => seo_meta(array('title' => seo_title('Track Order'), 'description' => 'Track a Kupiana order by order number.', 'canonical' => site_url('track-order'), 'robots' => 'noindex,follow')),
		));
	}

	public function blog()
	{
		$posts = $this->db->from('blog_posts')->where('status', 'active')->where('deleted_at IS NULL', NULL, FALSE)->order_by('published_at', 'DESC')->limit(12)->get()->result();
		$this->render('blog', array(
			'posts' => $posts,
			'meta' => seo_meta(array('title' => seo_title('Blog'), 'description' => 'Stories and buying guides from Kupiana.', 'canonical' => site_url('blog'))),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Blog' => site_url('blog'))),
			)),
		));
	}

	public function blog_post($slug)
	{
		$post = $this->db
			->from('blog_posts')
			->where('slug', $slug)
			->where('status', 'active')
			->where('deleted_at IS NULL', NULL, FALSE)
			->limit(1)
			->get()
			->row();

		if ( ! $post) { show_404(); }

		$canonical = site_url('blog/'.$post->slug);
		$image = upload_url($post->featured_image);
		$this->render('blog_post', array(
			'post' => $post,
			'meta' => seo_entity_meta('blog_post', $post->id, array(
				'title' => seo_title($post->meta_title ?: $post->title),
				'description' => $post->meta_description ?: ($post->excerpt ?: seo_clean_text($post->content, 160)),
				'canonical' => $canonical,
				'og_type' => 'article',
				'og_image' => $image,
			)),
			'json_ld' => seo_json_ld_graph(array(
				seo_organization_schema(),
				seo_website_schema(),
				seo_breadcrumb_schema(array('Home' => site_url(), 'Blog' => site_url('blog'), $post->title => $canonical)),
				array(
					'@type' => 'BlogPosting',
					'@id' => $canonical.'#article',
					'headline' => $post->title,
					'description' => seo_clean_text($post->meta_description ?: ($post->excerpt ?: $post->content), 200),
					'image' => $image,
					'url' => $canonical,
					'datePublished' => $post->published_at ? date('c', strtotime($post->published_at)) : date('c', strtotime($post->created_at)),
					'dateModified' => $post->updated_at ? date('c', strtotime($post->updated_at)) : date('c'),
					'publisher' => array('@id' => site_url().'#organization'),
					'author' => array('@type' => 'Organization', 'name' => seo_site_name()),
				),
				seo_entity_schema('blog_post', $post->id),
			)),
		));
	}

	public function checkout()
	{
		$this->render('checkout', array('meta' => seo_meta(array('title' => seo_title('Checkout'), 'robots' => 'noindex,follow'))));
	}
}
