<h1>Invoice Payment Module</h1>

<h3>Установка</h3>

1. Скачайте [плагин](https://github.com/Invoice-LLC/Invoice.Module.Diafan/archive/master.zip)
2. Распакуйте архив в корневую директорию сайта
3. Перейдите во вкладку **Оплата->Методы оплаты**, затем нажмите "Добавить"
4. Заполните форму, как показано на скриншоте<br>
![image](https://user-images.githubusercontent.com/91345275/199292627-30818844-84a9-4aa2-83c7-d3a8d40615a3.png)

<br>Api ключ и Merchant Id в [личном кабинете Invoice](https://lk.invoice.su/):<br>
![image](https://user-images.githubusercontent.com/91345275/196218699-a8f8c00e-7f28-451e-9750-cfa1f43f15d8.png)
![image](https://user-images.githubusercontent.com/91345275/196218722-9c6bb0ae-6e65-4bc4-89b2-d7cb22866865.png)<br>
<br>Terminal Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218998-b17ea8f1-3a59-434b-a854-4e8cd3392824.png)
![image](https://user-images.githubusercontent.com/91345275/196219014-45793474-6dfa-41e3-945d-fc669c916aca.png)<br>


5. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
   с типом **WebHook** и адресом: **%URL сайта%/payment/get/invoice**<br>
   ![Imgur](https://imgur.com/lMmKhj1.png)