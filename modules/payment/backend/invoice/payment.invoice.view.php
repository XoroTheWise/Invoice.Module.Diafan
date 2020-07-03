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

echo $result["text"];

?>
<?php
if(isset($result['error'])):
    ?><p>Возникла ошибка: <?$result['error']?></p <?php
else:
?>

<form name="invoice" action="<?php echo $result["payment_url"]; ?>">
    <p><input type="submit" value="<?php echo $this->diafan->_('Оплатить', false);?>"></p>
</form>
<?php endif;?>
