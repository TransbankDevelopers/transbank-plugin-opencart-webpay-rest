<?php
require_once DIR_SYSTEM . '/library/transbank/vendor/autoload.php';

use Transbank\Opencart\Webpay\Utils\HealthCheck;
use Transbank\Opencart\Webpay\Utils\LogHandler;

class ControllerExtensionPaymentWebpayRest extends Controller
{
    private $error = array();

    private const ROUTE = 'extension/payment/webpay_rest';

    private const PARAM_USER_TOKEN = 'user_token';

    private const PARAM_TYPE_PAYMENT = 'type=payment';

    private const DEFAULT_CONFIG = array(
        'test_mode' => "TEST",
        'commerce_code' => "597055555532",
        'api_key' => "579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C"
    );

    private $sections = array('commerce_code', 'api_key', 'test_mode');

    /**
     * Displays the payment method settings page, saving the posted
     * settings first when the request is a valid POST.
     * @return void
     */
    public function index()
    {
        $this->loadResources();

        $this->document->setTitle($this->language->get('heading_title'));;

        $redirs = array('authorize', 'finish', 'error', 'reject');

        foreach ($redirs as $value) {
            $this->request->post['payment_webpay_rest_url_' . $value] = HTTP_CATALOG . 'index.php?route=' . self::ROUTE . '/' . $value;
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_webpay_rest', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link(self::ROUTE, self::PARAM_USER_TOKEN . '=' . $this->session->data['user_token'] . '&' . self::PARAM_TYPE_PAYMENT, true));
        }

        foreach ($this->buildErrorData() as $key => $value) {
            $data[$key] = $value;
        }

        foreach ($this->buildLanguageData() as $key => $value) {
            $data[$key] = $value;
        }

        $data['breadcrumbs'] = $this->buildBreadcrumbs();

        $data['action'] = $this->url->link(self::ROUTE, self::PARAM_USER_TOKEN . '=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', self::PARAM_USER_TOKEN . '=' . $this->session->data['user_token'] . '&' . self::PARAM_TYPE_PAYMENT, true);

        foreach ($this->resolveSectionValues() as $key => $value) {
            $data[$key] = $value;
        }

        foreach ($this->resolveConfigValues() as $key => $value) {
            $data[$key] = $value;
        }

        $isEnabled = (bool) $data['payment_webpay_rest_status'];
        $data['status_options'] = array(
            array('value' => '1', 'label' => $this->language->get('text_enabled'), 'selected' => $isEnabled),
            array('value' => '0', 'label' => $this->language->get('text_disabled'), 'selected' => !$isEnabled),
        );

        $isLive = ($data['payment_webpay_rest_test_mode'] === 'LIVE');
        $data['test_mode_options'] = array(
            array('value' => 'TEST', 'label' => 'Integración', 'selected' => !$isLive),
            array('value' => 'LIVE', 'label' => 'Producción', 'selected' => $isLive),
        );

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['order_statuses_completed'] = $this->buildOrderStatusOptions($data['order_statuses'], $data['payment_webpay_rest_completed_order_status']);
        $data['order_statuses_rejected'] = $this->buildOrderStatusOptions($data['order_statuses'], $data['payment_webpay_rest_rejected_order_status']);
        $data['order_statuses_canceled'] = $this->buildOrderStatusOptions($data['order_statuses'], $data['payment_webpay_rest_canceled_order_status']);
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $args = $this->resolveHealthCheckArgs();

        foreach ($this->buildHealthCheckData($args) as $key => $value) {
            $data[$key] = $value;
        }

        foreach ($this->buildLogData() as $key => $value) {
            $data[$key] = $value;
        }

        $data['url_check_conn'] = html_entity_decode($this->url->link(self::ROUTE . '/checkConnection', self::PARAM_USER_TOKEN . '=' . $this->session->data['user_token'], true));
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(self::ROUTE, $data));
    }

    /**
     * Checks the connection with Webpay's API
     *
     * @return void
     */
    public function checkConnection()
    {
        $args = array(
            'MODO' => $this->config->get('payment_webpay_rest_test_mode'),
            'COMMERCE_CODE' => $this->config->get('payment_webpay_rest_commerce_code'),
            'API_KEY' => $this->config->get('payment_webpay_rest_api_key'),
            'ECOMMERCE' => 'opencart'
        );

        $healthcheck = new HealthCheck($args);
        $resp = $healthcheck->setInitTransaction();
        $this->response->setOutput(json_encode($resp));
    }

    /**
     * Loads the language file and the models the settings page needs.
     * @return void
     */
    private function loadResources()
    {
        $this->load->language(self::ROUTE);
        $this->load->model('setting/setting');
        $this->load->model('localisation/order_status');
    }

    /**
     * Validates the posted settings, checking permission and that every
     * required section field is present.
     * @return bool
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', self::ROUTE)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->sections as $value) {
            if (!$this->request->post['payment_webpay_rest_' . $value]) {
                $this->error[$value] = $this->language->get('error_' . $value);
            }
        }

        return !$this->error;
    }

    /**
     * Builds the error_warning and per-section error message data
     * consumed by the settings template.
     * @return array
     */
    private function buildErrorData()
    {
        $data = array();
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        foreach ($this->sections as $value) {
            $data['error_' . $value] = isset($this->error['payment_webpay_rest_' . $value])
                ? $this->error['payment_webpay_rest_' . $value]
                : '';
        }

        return $data;
    }

    /**
     * Loads the language strings the settings template needs.
     * @return array
     */
    private function buildLanguageData()
    {
        $vars = array(
            'entry_commerce_code',
            'entry_api_key',
            'entry_test_mode',
            'entry_total',
            'entry_geo_zone',
            'entry_status',
            'entry_sort_order',
            'entry_completed_order_status',
            'entry_rejected_order_status',
            'tab_settings',
            'entry_canceled_order_status'
        );

        $data = array();

        foreach ($vars as $var) {
            $data[$var] = $this->language->get($var);
        }

        return $data;
    }

    /**
     * Builds the settings page breadcrumb trail.
     * @return array
     */
    private function buildBreadcrumbs()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', self::PARAM_USER_TOKEN . '=' . $this->session->data['user_token'], true),
        );

        $breadcrumbs[] = array(
            'text' => $this->language->get('text_webpay_rest'),
            'href' => $this->url->link('marketplace/extension', self::PARAM_USER_TOKEN . '=' . $this->session->data['user_token'] . '&' . self::PARAM_TYPE_PAYMENT, true),
        );

        $breadcrumbs[] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(self::ROUTE, self::PARAM_USER_TOKEN . '=' . $this->session->data['user_token'], true),
        );

        return $breadcrumbs;
    }

    /**
     * Resolves commerce_code/api_key/test_mode from POST, falling back to
     * the stored config value, then to the default config.
     * @return array
     */
    private function resolveSectionValues()
    {
        $values = array();

        foreach ($this->sections as $value) {
            if (isset($this->request->post['payment_webpay_rest_' . $value])) {
                $values['payment_webpay_rest_' . $value] = $this->request->post['payment_webpay_rest_' . $value];
            } else if ($this->config->get('payment_webpay_rest_' . $value)) {
                $values['payment_webpay_rest_' . $value] = $this->config->get('payment_webpay_rest_' . $value);
            } else {
                $values['payment_webpay_rest_' . $value] = self::DEFAULT_CONFIG[$value];
            }
        }

        return $values;
    }

    /**
     * Resolves fields from POST, falling back to the stored config value,
     * with no default beyond that.
     * @return array
     */
    private function resolveConfigValues()
    {
        $fields = array('total', 'completed_order_status', 'rejected_order_status', 'canceled_order_status', 'geo_zone', 'sort_order', 'status');

        $values = array();

        foreach ($fields as $value) {
            if (isset($this->request->post['payment_webpay_rest_' . $value])) {
                $values['payment_webpay_rest_' . $value] = $this->request->post['payment_webpay_rest_' . $value];
            } else {
                $values['payment_webpay_rest_' . $value] = $this->config->get('payment_webpay_rest_' . $value);
            }
        }

        return $values;
    }

    /**
     * Builds the option list for an order status select, flagging which one
     * is currently selected so the template doesn't need to compare values.
     * @param array $order_statuses
     * @param int|string $selectedId
     * @return array
     */
    private function buildOrderStatusOptions($order_statuses, $selectedId)
    {
        $options = array();

        foreach ($order_statuses as $order_status) {
            $options[] = array(
                'order_status_id' => $order_status['order_status_id'],
                'name' => $order_status['name'],
                'selected' => ($order_status['order_status_id'] == $selectedId),
            );
        }

        return $options;
    }

    /**
     * Resolves the MODO/COMMERCE_CODE/API_KEY arguments for HealthCheck,
     * falling back from POST to the stored config, then to the default config.
     * @return array
     */
    private function resolveHealthCheckArgs()
    {
        $args = array(
            'MODO' => self::DEFAULT_CONFIG['test_mode'],
            'COMMERCE_CODE' => self::DEFAULT_CONFIG['commerce_code'],
            'API_KEY' => self::DEFAULT_CONFIG['api_key'],
            'ECOMMERCE' => 'opencart'
        );

        if (isset($this->request->post['payment_webpay_rest_commerce_code'])) {
            $args = array(
                'MODO' => $this->request->post['payment_webpay_rest_test_mode'],
                'COMMERCE_CODE' => $this->request->post['payment_webpay_rest_commerce_code'],
                'API_KEY' => $this->request->post['payment_webpay_rest_api_key'],
                'ECOMMERCE' => 'opencart'
            );
        } else if ($this->config->get('payment_webpay_rest_commerce_code')) {
            $args = array(
                'MODO' => $this->config->get('payment_webpay_rest_test_mode'),
                'COMMERCE_CODE' => $this->config->get('payment_webpay_rest_commerce_code'),
                'API_KEY' => $this->config->get('payment_webpay_rest_api_key'),
                'ECOMMERCE' => 'opencart'
            );
        }

        return $args;
    }

    /**
     * Runs HealthCheck with the given args and returns its resume in both
     * the raw and decoded forms the template and its diagnostic modal use.
     * @param array $args
     * @return array
     */
    private function buildHealthCheckData($args)
    {
        $hc = new HealthCheck($args);
        $healthcheck = json_decode($hc->printFullResume(), true);

        return array(
            'hc_data' => $hc->printFullResume(),
            'healthcheck' => $healthcheck,
        );
    }

    /**
     * Resolves the log resume and shapes the last log entry's content for
     * the diagnostic modal's logs tab.
     * @return array
     */
    private function buildLogData()
    {
        $logHandler = new LogHandler();

        $data = array();
        $data['log_data'] = json_decode($logHandler->getResume(), true);

        if (isset($data['log_data']['last_log']['log_content'])) {
            $data['res_logcontent'] = json_encode($data['log_data']['last_log']['log_content']);
            $data['log_file'] = $data['log_data']['last_log']['log_file'];
            $data['log_file_weight'] = $data['log_data']['last_log']['log_weight'];
            $data['log_file_regs'] = $data['log_data']['last_log']['log_regs_lines'];
        } else {
            $data['res_logcontent'] = $data['log_data']['last_log'][0];
            $data['log_file'] = json_encode($data['res_logcontent']);
            $data['log_file_weight'] = $data['log_file'];
            $data['log_file_regs'] = $data['log_file'];
        }

        $data['log_dir'] = stripslashes(json_encode($data['log_data']['log_dir']));
        $data['log_count'] = json_encode($data['log_data']['logs_count']['log_count']);

        return $data;
    }
}
