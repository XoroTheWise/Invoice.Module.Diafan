<?php


class CREATE_PAYMENT
{
    /**
     * @var INVOICE_ORDER
     */
    public $order;
    /**
     * @var SETTINGS
     */
    public $settings;
    /**
     * @var array
     */
    public $custom_parameters;
    /**
     * @var array(ITEM)
     */
    public $receipt;

    public $mail;
    public $phone;
}