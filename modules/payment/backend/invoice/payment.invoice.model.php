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
require "InvoiceSDK/GET_TERMINAL.php";
require "InvoiceSDK/common/ITEM.php";

class Payment_invoice_model extends Diafan
{

    public function get($params, $pay)
    {
        $result["text"] = $pay['text'];
        $result["desc"] = $pay['desc'];

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
        $this->checkOrCreateTerminal($params);
        $terminal = $this->getTerminal($params);

        $request = new CREATE_PAYMENT();
        $request->order = $this->getOrder($amount, $id);
        $request->settings = $this->getSettings($terminal);
        $request->receipt = $this->getReceipt($id);

        $response = $this->getRestClient($params)->CreatePayment($request);

        if($response == null) throw new Exception("Ошибка при создании платежа");
        if(isset($response->error)) throw new Exception("Ошибка при создании платежа(".$response->description.")");

        return $response->payment_url;
    }

     /**
     * @return INVOICE_ORDER
     */

    private function getOrder($amount, $id) {
        $order = new INVOICE_ORDER();
        $order->amount = $amount;
        $order->id = $id;
        $order->currency = "RUB";

        return $order;
    }

    /**
     * @return INVOICE_SETTINGS
     */

    private function getSettings($terminal) {
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $settings = new INVOICE_SETTINGS();
        $settings->terminal_id = $terminal;
        $settings->success_url = $url;
        $settings->fail_url = $url;

        return $settings;
    }

    /**
     * @return ITEM
     */

    private function getReceipt($id) {
        $receipt = array();
        $basket = $this->diafan->_order->get($id)['rows'];

        foreach ($basket as $basketItem) {
            $item = new ITEM();
            $item->name = $basketItem['name'];
            $item->price = $basketItem['price'];
            $item->resultPrice = $basketItem['summ'];
            $item->quantity = $basketItem['count'];
            
            array_push($receipt, $item);
        }

        return $receipt;
    }

    public function createTerminal($params) {
        $request = new CREATE_TERMINAL();
        $request->name = $params['invoice_default_terminal_name'];
        $request->type = "dynamical";
        $request->description = "DifanModule";
        $request->defaultPrice = "10";

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
        $tid = $this->getTerminal($params);
        if($tid == null or empty($tid)) {
            $tid = $this->createTerminal($params);
        }
        return $tid;
    }

    public function saveTerminal($id) {
        file_put_contents("invoice_tid", $id);
    }

    public function getTerminal($params) {
        $terminal = new GET_TERMINAL();
        $terminal->alias = file_get_contents("invoice_tid");

        $info = $this->getRestClient($params)->GetTerminal($terminal);

        if($info->id == null || $info->id != $terminal->alias){
            return null;
        } else {
            return $info->id;
        }
    }
}