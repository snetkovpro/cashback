<?php

class shopCashbackPlugin extends shopPlugin
{

	public $order;
	private $witems = array('4302', '4401', '4501', '2101', '2102', '9302', '9301', '9303', '9201', '5201', '8302', '1211', '1201', '2705', '2706', '2707', '2708', '2401', '2301', '2303', '2204', '2102', '2201');

	public function execute ($params) {

		 if (!$this->getSettings('enabled')) {
           		 return false;
       		 }
		
			
			// Получение данных о заказе
			$model = new shopOrderModel();
			$this->order = $model->getOrder($params['order_id']);
			$cashback_url = $this->order['params']['storefront'] . '/cashback/';
			$mail_banner = $this->order['params']['storefront'] . '/cashback/img/mail_banner.jpg';

			// Получение данных о товарах в заказе
			$items = $this->getItems();

			// Расчёт кэшбэка
			$cashback = $this->calc($items);
		
			if ($cashback) {
				
				// Формирование письма админу
				$data = $this->collectData('admin');

				$admin_view = wa()->getView();
				$admin_view->assign('order_id', $data['id']);
				$admin_view->assign('name', $data['name']);
				$admin_view->assign('phone', $data['phone']);
				$admin_view->assign('email', $data['email']);
				$admin_view->assign('cashback', $cashback);

		        $admin_content = $admin_view->fetch($this->path.'/templates/shopCashbackPluginAdmin.html');

		        $subject = 'Акция Cashback';

		        $admin_data['subject'] = $subject;
		        $admin_data['body'] = $admin_content;
		        $admin_data['email'] = 'snetkovpro@gmail.com';
		        $admin_data['name'] = 'Roman';
		        $this->mail($admin_data);

		        // Формирование письма клиенту

		        $data = $this->collectData('customer');
		       

		        $customer_view = wa()->getView();
		        $customer_view->assign('order_id', $data['id']);
		        $customer_view->assign('name', $data['name']);
		        $customer_view->assign('cashback', $cashback);
		        $customer_view->assign('cashback_url', $cashback_url);
		        $customer_view->assign('mail_banner', $mail_banner);

		        $customer_content = $customer_view->fetch($this->path.'/templates/shopCashbackPluginCustomer.html');

		        $subject = 'Акция Cashback от ВМПАВТО!';

		        $customer_data['subject'] = $subject;
		        $customer_data['body'] = $customer_content;
		        $customer_data['email'] = $data['email'];
		        $customer_data['name'] = $data['name'];

		        $this->mail($customer_data);

			}

		


        // waLog::dump($this->order, 'shop/cashback/order-actions/create.log');
      

	}

	public function cashbackCart () {

		if (!$this->getSettings('enabled')) {
           		 return false;
       		 }

      	

		$cart = new shopCart();
		$items = $cart->items();
		$action = 'ready';
		$base = wa()->getRouting()->getDomain();

		foreach ($items as $item) {

			switch ($action) {

				case 'bingo':
					break 2;

				case 'ready':
					if (in_array($item['sku_code'], $this->witems)) {
						$action = 'go';
						$name = $item['product']['name'];
						$url = $base . "/" . $item['product']['url'];
						$product_link = "<a href='http://" . $url . "' >" . $name . "</a>";
						$cashback_link = "<a href='http://" . $base . "/cashback/' >Cashback</a>"; 
					}
					break;
				
				case 'go':
					if (in_array($item['sku_code'], $this->witems)) {
						$action = 'bingo';
					}
					break;
				
			}
		
		}

		
		
		switch ($action) {

			case 'ready':
				return false;
			
			case 'go':
				$msg = "Товар " . $product_link . " участвует в акции " . $cashback_link . ". Купите ещё товар из зимнего ассортимента и получите кэшбэк.";
				$view = wa()->getView();
				$view->assign('msg', $msg);
				$content = $view->fetch($this->path.'/templates/msg.html');
				return $content;

			case 'bingo':
				$msg = "Поздравляем! Вы участвуете в акции " . $cashback_link . "!";
				$view = wa()->getView();
				$view->assign('msg', $msg);
				$content = $view->fetch($this->path.'/templates/msg.html');
				return $content;

		}


		
		

	}

	

	public function calc ($items) {

		$action = 'ready';
		$wisum = 0;

		foreach ($items as $key => $value) {

			
			if (in_array($value['sku_code'], $this->witems)) {
				
				
				switch ($action) {
					case 'ready':
						$action = 'steady';
						break;
					case 'steady':
						$action = 'go';
						break;
				}
				
				
				$wisum += $value['price'] * $value['quantity'];
				
			}

			

		}

		if ($action == 'go') {
			$percent = ($wisum * 5) / 100;
			if ($percent <= 1000) {
				$cashback = $percent;
			} else {
				$cashback = 1000;
			}

		} else {
			$cashback = 0;
		}

		return $cashback;

	}

	public function getItems () {

		$items = $this->order['items'];
		return $items;

	}

	public function mail ($data) {
		
		$mail_message = new waMailMessage($data['subject'], $data['body']);
		$mail_message->setFrom('store@smazka.ru', 'ВМПАВТО');
		
		if ($data['email'] != ' ') {
			$mail_message->setTo($data['email'], $data['name']);
			$mail_message->send();
		} 
	
	}

	public function collectData ($purpose) {

		$data = array();
		
		if ($purpose == 'admin') {

			$data['id'] = $this->order['id'];
			$data['name'] = $this->order['contact']['name'];
			$data['phone'] = $this->order['contact']['phone'];
			$data['email'] = isset($this->order['contact']['email']) ? $this->order['contact']['email'] : ' ';

		}

		if ($purpose == 'customer') {

			$data['id'] = $this->order['id'];
			$data['name'] = $this->order['contact']['name'];
			$data['email'] = isset($this->order['contact']['email']) ? $this->order['contact']['email'] : ' ';

		}
		
		return $data;

	}
}
