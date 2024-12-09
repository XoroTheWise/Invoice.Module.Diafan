<?php

class Callback {
    private $self;

    public function __construct($self)
    {
        $this->self = $self;
    }

    public function handle() {
        $notification = $this->getNotification();

        $this->log("INFO: Collback notification - ". json_encode($notification) . "\n");

        $type = $notification["notification_type"];
        $id = strstr($notification["order"]["id"], ":", true);

        $signature = $notification["signature"];

        $pay = $this->getOrder($id);

        if($signature != $this->getSignature($notification["id"], $notification["status"], $pay['params']['invoice_api_key'])) {
            return "Wrong signature";
        }

        if($type == "pay") {

            if($notification["status"] == "successful") {
                $this->log("INFO: Collback status - pay". "" . "\n");
                $this->pay($pay);
                return "payment successful";
            }
            if($notification["status"] == "error") {
                $this->log("ERROR: Collback status - pay". "" . "\n");
                return "payment failed";
            }
        }

        return "null";
    }

    private function pay($pay) {
        $this->self->diafan->_payment->success($pay, 'pay');
        return true;
    }

    public function log($log) {
        $timestamp = date('Y-m-d H:i:s');
		$fp = fopen('invoice_payment.log', 'a+');
        fwrite($fp, "[{$timestamp}] {$log}\n");
		fclose($fp);
	}

    private function getOrder($id) {
        $pay = DB::query_fetch_array("SELECT * FROM {payment_history} WHERE id=%d LIMIT 1", $id);
        if (! $pay)
        {
            return false;
        }

        $pay["payment"] = DB::query_fetch_array("SELECT * FROM {payment} WHERE id=%d AND payment='%s' LIMIT 1", $pay["payment_id"], 'invoice');
        if(! $pay["payment"])
        {
            return false;
        }
        $pay["params"] = unserialize($pay["payment"]["params"]);

        return $pay;
    }

    private function getNotification() {
        $postData = file_get_contents('php://input');
        return json_decode($postData, true);
    }

    private function getSignature($id, $status, $key) {
        return md5($id.$status.$key);
    }
}

$callback = new Callback($this);
echo $callback->handle();