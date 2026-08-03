<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public courier tracking webhook receiver.
 */
class Tracking extends Store_Controller
{
	public function webhook()
	{
		$this->load->model('Tracking_model', 'tracking_service');
		$body = file_get_contents('php://input');
		$payload = json_decode((string) $body, TRUE);
		if ( ! is_array($payload)) { $this->output->set_status_header(400)->set_output('invalid json'); return; }

		$secret = (string) $this->settings->get('tracking_webhook_secret', kupiana_env('TRACKING_WEBHOOK_SECRET', ''));
		if ($secret !== '')
		{
			$signature = isset($_SERVER['HTTP_X_KUPIANA_TRACKING_SIGNATURE']) ? $_SERVER['HTTP_X_KUPIANA_TRACKING_SIGNATURE'] : '';
			$expected = hash_hmac('sha256', (string) $body, $secret);
			if ( ! hash_equals($expected, $signature)) { $this->output->set_status_header(400)->set_output('invalid signature'); return; }
		}

		$tracking_number = trim((string) array_get($payload, 'tracking_number'));
		$shipment_number = trim((string) array_get($payload, 'shipment_number'));
		$this->db->from('shipments')->where('deleted_at IS NULL', NULL, FALSE);
		if ($tracking_number !== '') { $this->db->where('tracking_number', $tracking_number); }
		elseif ($shipment_number !== '') { $this->db->where('shipment_number', $shipment_number); }
		else { $this->output->set_status_header(400)->set_output('missing shipment identifier'); return; }
		$shipment = $this->db->limit(1)->get()->row();
		if ( ! $shipment) { $this->output->set_status_header(404)->set_output('shipment not found'); return; }

		$result = $this->tracking_service->add_event($shipment->id, array(
			'status_text' => array_get($payload, 'status_text', 'Courier update'),
			'shipment_status' => array_get($payload, 'shipment_status'),
			'location' => array_get($payload, 'location'),
			'description' => array_get($payload, 'description'),
			'occurred_at' => array_get($payload, 'occurred_at'),
		));
		$this->output->set_status_header($result['success'] ? 200 : 422)->set_output($result['success'] ? 'ok' : $result['message']);
	}
}
