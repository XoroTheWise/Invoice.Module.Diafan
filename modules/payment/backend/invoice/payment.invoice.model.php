<?php

if (! defined('DIAFAN'))
{
    $path = __FILE__; $i = 0;
    while(! file_exists($path.'/includes/404.php'))
    {
        if($i == 10) exit; $i++;
        $path = dirname($path);
    }
    include $path.'/includes/404.php';
}

require "InvoiceSDK/RestClient.php";
require "InvoiceSDK/common/SETTINGS.php";
require "InvoiceSDK/common/ORDER.php";
require "InvoiceSDK/CREATE_TERMINAL.php";
require "InvoiceSDK/CREATE_PAYMENT.php";

class Payment_invoice_model extends Diafan
{

    public function get($params, $pay)
    {
        $result["text"]			= $pay['text'];
        $result["desc"]			= $pay['desc'];

        try {
            $payment_url = $this->createPayment($pay['id'], $pay['summ'], $params);
            $result['payment_url'] = $payment_url;
        } catch (Exception $e) {
            $result['payment_url'] = '/';
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function createPayment($id, $amount, $params) {
        $order = new INVOICE_ORDER($amount);
        $order->id = $id;
        $settings = new SETTINGS($this->checkOrCreateTerminal($params));
        $settings->success_url = ( ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);

        $request = new CREATE_PAYMENT($order, $settings, []);
        $response = $this->getRestClient($params)->CreatePayment($request);

        if($response == null) throw new Exception("Ошибка при создании платежа");
        if(isset($response->error)) throw new Exception("Ошибка при создании платежа(".$response->description.")");

        return $response->payment_url;
    }

    public function createTerminal($params) {
        $request = new CREATE_TERMINAL($params['invoice_default_terminal_name']);
        $response = $this->getRestClient($params)->CreateTerminal($request);

        if($response == null) throw new Exception("Ошибка при создании терминала");
        if(isset($response->error)) throw new Exception("Ошибка при создании терминала(".$response->description.")");

        $this->saveTerminal($response->id);

        return $response->id;
    }

    public function getRestClient($params) {
        return new RestClient($params['invoice_login'], $params['invoice_api_key']);
    }

    public function checkOrCreateTerminal($params) {
        $tid = $this->getTerminal();
        if($tid == null or empty($tid)) {
            $tid = $this->createTerminal($params);
        }
        return $tid;
    }

    public function saveTerminal($id) {
        file_put_contents("invoice_tid", $id);
    }

    public function getTerminal() {
        if(!file_exists("invoice_tid")) return "";
        return file_get_contents("invoice_tid");
    }
}