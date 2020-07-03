<?php

class Callback {
    private $self;

    public function __construct($self)
    {
        $this->self = $self;
    }

    public function handle() {
        $notification = $this->getNotification();

        $type = $notification["notification_type"];
        $id = $notification["order"]["id"];

        $signature = $notification["signature"];

        $pay = $this->getOrder($id);

        if($signature != $this->getSignature($notification["id"], $notification["status"], $pay['params']['invoice_api_key'])) {
            return "Wrong signature";
        }

        if($type == "pay") {

            if($notification["status"] == "successful") {
                $this->pay($pay);
                return "payment successful";
            }
            if($notification["status"] == "error") {
                return "payment failed";
            }
        }

        return "null";
    }

    private function pay($pay) {
        $this->self->diafan->_payment->success($pay, 'pay');
        return true;
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