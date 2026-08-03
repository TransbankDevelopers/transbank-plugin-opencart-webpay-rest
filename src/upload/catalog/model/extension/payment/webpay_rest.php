<?php
class ModelExtensionPaymentWebpayRest extends Model {

	public function getMethod($address, $total) {

        $this->load->language('extension/payment/webpay_rest');
        $this->load->model('setting/setting');

        $status = false;

		if (intval($total) > 0 && isset($address['iso_code_2']) && $address['iso_code_2'] == 'CL') {
			$status = true;
        }

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code' => 'webpay_rest',
				'title' => $this->language->get('text_title'),
				'terms' => '',
				'sort_order' => $this->config->get('payment_webpay_rest_sort_order')
			);
		}

		return $method_data;
	}
}
