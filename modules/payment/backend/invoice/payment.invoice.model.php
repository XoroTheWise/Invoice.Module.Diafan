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
            $this->log("INFO: createPayment - ". $payment_url . "\n");
            $result['payment_url'] = $payment_url;
        } catch (Exception $e) {
            $result['payment_url'] = '/';
            $this->log("ERROR: errorCreatePayment - ". $e->getMessage() . "\n");
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    public function log($log) {
        $timestamp = date('Y-m-d H:i:s');
		$fp = fopen('invoice_payment.log', 'a+');
        fwrite($fp, "[{$timestamp}] {$log}\n");
		fclose($fp);
	}

    public function createPayment($id, $amount, $params) {
        $terminal = $this->getTerminal($params);

        $request = new CREATE_PAYMENT();
        $request->order = $this->getOrder($amount, $id);
        $request->settings = $this->getSettings($terminal);
        $request->receipt = $this->getReceipt($id);

        $this->log("INFO: CreatePayment request - ". json_encode($request) . "\n");

        $response = $this->getRestClient($params)->CreatePayment($request);

        if($response == null){ 
            $this->log("ERROR: CreatePayment - response is null ". "" . "\n");
            throw new Exception("Ошибка при создании платежа");
        }
        if(isset($response->error)){
            $this->log("ERROR: CreatePayment response - ". json_encode($response) . "\n");
             throw new Exception("Ошибка при создании платежа(".$response->description.")");
             
        }

        $this->log("INFO: CreatePayment response - ". json_encode($response) . "\n");

        return $response->payment_url;
    }

     /**
     * @return INVOICE_ORDER
     */

    private function getOrder($amount, $id) {
        $order = new INVOICE_ORDER();
        $order->amount = $amount;
        $order->id = $id . ":" . bin2hex(random_bytes(8));
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
        $request->defaultPrice = 0;
        $request->alias = md5( $params['invoice_login'] . ":" . $params['invoice_api_key'] );

        $this->log("INFO: CreateTerminal request - ". json_encode($request) . "\n");

        $response = $this->getRestClient($params)->CreateTerminal($request);

        $this->log("INFO: CreateTerminal response - ". json_encode($response) . "\n");

        if($response == null){
            $this->log("ERROR: CreatePayment - response is null ". "" . "\n");
            throw new Exception("Ошибка при создании терминала");
        } 
        if(isset($response->error)) {
            $this->log("ERROR: CreatePayment response - ". json_encode($response) . "\n");
            throw new Exception("Ошибка при создании терминала(".$response->description.")");
        }

        $this->log("INFO: CreateTerminal terminal - ". $response->id . "\n");

        return $response->id;
    }

    public function getRestClient($params) {
        return new RestClient($params['invoice_login'], $params['invoice_api_key']);
    }

    public function getTerminal($params) {
        $terminal = new GET_TERMINAL();
        $terminal->alias = md5( $params['invoice_login'] . ":" . $params['invoice_api_key'] );

        $this->log("INFO: GetTerminal request - ". json_encode($terminal) . "\n");

        $info = $this->getRestClient($params)->GetTerminal($terminal);

        $this->log("INFO: GetTerminal response - ". json_encode($info) . "\n");

        if($info->id == null || $info->error != null){
            $this->log("ERROR: GetTerminal - terminal is null or error ". json_encode($info) . "\n");
            return $this->createTerminal($params);
        } else {
            return $info->id; 
        }
    }
}