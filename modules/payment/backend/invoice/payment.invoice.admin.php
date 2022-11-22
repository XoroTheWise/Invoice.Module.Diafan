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

class Payment_invoice_admin
{
    public $config;

    public function __construct()
    {
        $this->config = array(
            "name" => 'Invoice',
            "params" => array(
                'invoice_api_key' => array(
                    'name'	=>	'API Key',
                    'help'	=>	'Можно взять из ЛК Invoice'
                ),
                'invoice_login' => array(
                    'name'	=>	'Merchant ID',
                    'help'	=>	'Можно взять из ЛК Invoice'
                ),
                'invoice_default_terminal_name' => array(
                    'name' => 'Имя терминала',
                    'help' => 'Имя терминала по умолчанию'
                )
            )
        );
    }
}