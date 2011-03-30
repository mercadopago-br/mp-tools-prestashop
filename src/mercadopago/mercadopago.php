<?php

class mercadopago extends PaymentModule
{
	private $_html 			= '';
    private $_postErrors 	= array();
    public $currencies;
	public $_botoes 		= array(
		'buy_now_mlb.gif'
		
		);
		public $_banners 		= array(
		'tipo2_418X74.jpg'
	);
		
	public function __construct()
    {
        $this->name 			= 'mercadopago';
        $this->tab 				= 'Payment';
        $this->version 			= '1.0';

        $this->currencies 		= true;
        $this->currencies_mode 	= 'radio';

        parent::__construct();

        $this->page 			= basename(__file__, '.php');
        $this->displayName 		= $this->l('MercadoPago');
        $this->description 		= $this->l('Aceitar pagamentos via MercadoPago');
		$this->confirmUninstall = $this->l('Tem certeza de que deseja excluir seus dados?');
		$this->textshowemail 	= $this->l('Você deve seguir coretamente os procedimentos de pagamento do Mercado Pago, para que sua compra seja validada.');
	}
	
	public function install()
	{
		
		if ( !Configuration::get('mercadopago_STATUS_1') )
			$this->create_states();
		if 
		(
			!parent::install() 
		OR 	!Configuration::updateValue('mercadopago_acc_id', '')
		OR 	!Configuration::updateValue('mercadopago_TOKEN', 	  '')
		OR 	!Configuration::updateValue('mercadopago_reseller_acc_id', '')
		OR 	!Configuration::updateValue('mercadopago_URLPROCESS', 	  'http://www.sualoja.com.br/modules/mercadopago/includes/retorno.php')
		OR 	!Configuration::updateValue('mercadopago_URLSUCCESFULL', 	  'http://www.sualoja.com.br/modules/mercadopago/includes/sucesso.php')
		OR 	!Configuration::updateValue('mercadopago_BTN', 	  0)  
		OR 	!Configuration::updateValue('mercadopago_BANNER',   0)    
		OR 	!$this->registerHook('payment') 
		OR 	!$this->registerHook('paymentReturn')
		OR 	!$this->registerHook('home')
		)
			return false;
			
		return true;
	}

		public function create_states()
	{
		
		$this->order_state = array(
		array( 'ccfbff', '00100', 'MercadoPago - Transação em Andamento', 		 ''	),
		array( 'c9fecd', '11110', 'MercadoPago - Transação Concluída',	  	  'payment' ),
		array( 'fec9c9', '11110', 'MercadoPago - Transação Cancelada', 'order_canceled'	)
		);
		
	
		$languages = Db::getInstance()->ExecuteS('
		SELECT `id_lang`, `iso_code`
		FROM `'._DB_PREFIX_.'lang`
		');
			
		foreach ($this->order_state as $key => $value)
		{
			
			Db::getInstance()->Execute
			('
				INSERT INTO `' . _DB_PREFIX_ . 'order_state` 
			( `invoice`, `send_email`, `color`, `unremovable`, `logable`, `delivery`) 
				VALUES
			('.$value[1][0].', '.$value[1][1].', \'#'.$value[0].'\', '.$value[1][2].', '.$value[1][3].', '.$value[1][4].');
			');
		
			
			$this->figura 	= mysql_insert_id();
			
			foreach ( $languages as $language_atual )
			{
				
				Db::getInstance()->Execute
				('
					INSERT INTO `' . _DB_PREFIX_ . 'order_state_lang` 
				(`id_order_state`, `id_lang`, `name`, `template`)
					VALUES
				('.$this->figura .', '.$language_atual['id_lang'].', \''.$value[2].'\', \''.$value[3].'\');
				');
				
			}
			
			
			
				$file 		= (dirname(__file__) . "/icons/$key.gif");
				$newfile 	= (dirname( dirname (dirname(__file__) ) ) . "/img/os/$this->figura.gif");
				if (!copy($file, $newfile)) {
    			return false;
    			}
    		
    		Configuration::updateValue("mercadopago_STATUS_$key", 	$this->figura);
    		   				
		}
		
		return true;
		
	}

	public function uninstall()
	{
		if 
		(
			!Configuration::deleteByName('mercadopago_acc_id')
		OR	!Configuration::deleteByName('mercadopago_TOKEN')
		OR 	!Configuration::updateValue('mercadopago_reseller_acc_id')
		OR	!Configuration::deleteByName('mercadopago_URLPROCESS')
		OR 	!Configuration::updateValue('mercadopago_URLSUCCESFULL')
		OR	!Configuration::deleteByName('mercadopago_BTN')
		OR	!Configuration::deleteByName('mercadopago_BANNER')
		OR 	!parent::uninstall()
		) 
			return false;
		
		return true;
	}

	public function getContent()
	{
		$this->_html = '<h2>MercadoPago</h2>';
		if (isset($_POST['submitmercadopago']))
		{
			if (empty($_POST['acc_id'])) $this->_postErrors[] = $this->l('Digite o Acc_ID');
			
				if (!sizeof($this->_postErrors)) {
					Configuration::updateValue('mercadopago_acc_id', $_POST['acc_id']);
						if (!empty($_POST['pg_token']))
						{
						Configuration::updateValue('mercadopago_TOKEN', $_POST['pg_token']);
						}
						if (!empty($_POST['pg_reseller']))
						{
						Configuration::updateValue('mercadopago_reseller_acc_id', $_POST['pg_reseller']);
						}
						if (!empty($_POST['pg_url_retorno']))
						{
						Configuration::updateValue('mercadopago_URLPROCESS', $_POST['pg_url_retorno']);
						}
						if (!empty($_POST['pg_url_succesfull']))
						{
						Configuration::updateValue('mercadopago_URLSUCCESFULL', $_POST['pg_url_succesfull']);
						}
				$this->displayConf();
				}
				else $this->displayErrors();
		}
		elseif (isset($_POST['submitmercadopago_Btn']))
		{
			Configuration::updateValue('mercadopago_BTN', 	$_POST['btn_pg']);
			$this->displayConf();
		}
			elseif (isset($_POST['submitmercadopago_Bnr']))
		{
			Configuration::updateValue('mercadopago_BANNER', 	$_POST['banner_pg']);
			$this->displayConf();
		}
		
		$this->displaymercadopago();
		$this->displayFormSettingsmercadopago();
		return $this->_html;
	}
	
	public function displayConf()
	{
		$this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Configurações atualizadas').'
		</div>';
	}
	
	public function displayErrors()
	{
		$nbErrors = sizeof($this->_postErrors);
		$this->_html .= '
		<div class="alert error">
			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
			<ol>';
		foreach ($this->_postErrors AS $error)
			$this->_html .= '<li>'.$error.'</li>';
		$this->_html .= '
			</ol>
		</div>';
	}

	public function displaymercadopago()
	{
		$this->_html .= '
		<img src="../modules/mercadopago/imagens/mercadopago.jpg" style="float:left; margin-right:15px;" />
		<b>'.$this->l('Configure sua conta no MercadoPago.').'</b><br /><br />
		'.$this->l('Oferença toda segurança através do nosso módulo.').'<br />
		'.$this->l('Você precisa configurar o seu ACC_ID e o ENC, para depois usar este módulo.').'
		<br /><br /><br />';
	}

	public function displayFormSettingsmercadopago()
	{
		$conf 			= Configuration::getMultiple
		(array(
			'mercadopago_acc_id',
			'mercadopago_TOKEN',
			'mercadopago_reseller_acc_id',
			'mercadopago_URLPROCESS',
			'mercadopago_URLSUCCESFULL',
			'mercadopago_BTN',
			'mercadopago_BANNER'
			  )
		);
		
		$acc_idPag 	= array_key_exists('acc_id', $_POST) ? $_POST['acc_id'] : (array_key_exists('mercadopago_acc_id', $conf) ? $conf['mercadopago_acc_id'] : '');
		$token 			= array_key_exists('pg_token', $_POST) ? $_POST['pg_token'] : (array_key_exists('mercadopago_TOKEN', $conf) ? $conf['mercadopago_TOKEN'] : '');
		$reseller		= array_key_exists('pg_reseller', $_POST) ? $_POST['pg_reseller'] : (array_key_exists('mercadopago_reseller_acc_id', $conf) ? $conf['mercadopago_reseller_acc_id'] : '');
		$url_retorno	= array_key_exists('pg_url_retorno', $_POST) ? $_POST['pg_url_retorno'] : (array_key_exists('mercadopago_URLPROCESS', $conf) ? $conf['mercadopago_URLPROCESS'] : '');
		$url_succesfull	= array_key_exists('pg_url_succesfull', $_POST) ? $_POST['pg_url_succesfull'] : (array_key_exists('mercadopago_URLSUCCESFULL', $conf) ? $conf['mercadopago_URLSUCCESFULL'] : '');
		$btn 			= array_key_exists('btn_pg', $_POST) ? $_POST['btn_pg'] : (array_key_exists('mercadopago_BTN', $conf) ? $conf['mercadopago_BTN'] : '');
		$bnr 			= array_key_exists('banner_pg', $_POST) ? $_POST['banner_pg'] : (array_key_exists('mercadopago_BANNER', $conf) ? $conf['mercadopago_BANNER'] : '');
		
		
		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Configurações').'</legend>
			<label>'.$this->l('Digite o acc_id').':</label>
			<div class="margin-form"><input type="text" size="33" name="acc_id" value="'.htmlentities($acc_idPag, ENT_COMPAT, 'UTF-8').'" /></div>
			<br />
			
			<label>'.$this->l('Código de Segurança').':</label>
			<div class="margin-form"><input type="text" size="33" name="pg_token" value="'.$token.'" /></div>
			<br />
			
				<label>'.$this->l('reseller_acc_id').':</label>
			<div class="margin-form"><input type="text" size="33" name="pg_reseller" value="'.$reseller.'" /></div>
			<br />
			
			<label>'.$this->l('URL de Processando Pagamento').':</label>
			<div class="margin-form"><input type="text" size="33" name="pg_url_retorno" value="'.$url_retorno.'" /></div>
			<br />
			
			<label>'.$this->l('URL de Sucesso Pagamento (Aprovado)').':</label>
			<div class="margin-form"><input type="text" size="33" name="pg_url_succesfull" value="'.$url_succesfull.'" /></div>
			<br />
			
			<center><input type="submit" name="submitmercadopago" value="'.$this->l('Atualizar').'" class="button" /></center>
		</fieldset>
		</form>';
		
		// Linha de código para incluir um botão de pagamento para o administrador da loja escolher
		
		/*$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
		<fieldset>
			<legend><img src="../img/admin/themes.gif" />'.$this->l('Botão').'</legend><br/>';
		
		
		foreach ( $this->_botoes as $id => $value )
		{
			if ($btn ==  $id){
				$check = 'checked="checked"'; 
			}else{
				$check = '';
			}
			
			$this->_html .=  '
			<div>
			<input type="radio" name="btn_pg" value="'.$id.'" '.$check.' >';
			
			if( $value == 'default' )
			$this->_html .=  '<input type="submit" value="Pague com o mercadopago" class="exclusive_large" />';
			else
			$this->_html .=  '<img src="https://www.mercadopago.com/org-img/MP3/'.$value.'" />';
			
			$this->_html .=  '</div>
			<br />';
			
		}

		$this->_html .= '<br /><center><input type="submit" name="submitmercadopago_Btn" value="'.$this->l('Salvar').'" 
			class="button" />
		</center>
		</fieldset>
		</form>';*/
	
		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
		<fieldset>
			<legend><img src="../img/admin/themes.gif" />'.$this->l('Banner').'</legend><br/>';
			
		foreach ( $this->_banners as $id => $value )
		{
			if ($bnr ==  $id){
				$check = 'checked="checked"'; 
			}else{
				$check = '';
			}
			
			$this->_html .=  '
			<div>
			<input type="radio" name="banner_pg" value="'.$id.'" '.$check.' >';
			
			$this->_html .=  '
			<img src="http://www.mercadolivre.com.br/org-img/MLB/MP/BANNERS/'.$value.'" />';
			
			$this->_html .=  '
			</div>
			<br />';
			
		}

		$this->_html .= '<br /><center><input type="submit" name="submitmercadopago_Bnr" value="'.$this->l('Salvar').'" 
			class="button" />
		</center>
		</fieldset>
		</form>';
		
		
	}

    public function execPayment($cart)
    {
        global $cookie, $smarty;
        $invoiceAddress 	= new Address(intval($cart->id_address_invoice));
        $customerPag 		= new Customer(intval($cart->id_customer));
        $currencies 		= Currency::getCurrencies();
        $currencies_used 	= array();
		$currency 			= $this->getCurrency();

        $currencies 		= Currency::getCurrencies();
        foreach ($currencies as $key => $currency)
            $smarty->assign(array(
			'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
            'currencies' => $currencies_used, 
			'imgBtn' => "mercadopago.jpg",
			'imgBnr' => "http://www.mercadolivre.com.br/org-img/MLB/MP/BANNERS/".
						$this->_banners[Configuration::get('mercadopago_BANNER')],
            'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
            'currencies' => $currencies_used, 
			'total' => number_format(Tools::convertPrice($cart->getOrderTotal(true, 3), $currency), 2, '.', ''), 
			'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
            'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT,'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'));

        return $this->display(__file__, 'payment_execution.tpl');
    }
	
	public function hookPayment($params)
	{
		
		global $smarty;
		$smarty->assign(array(
			'imgBtn' => "modules/mercadopago/imagens/logo.gif",
			'this_path' => $this->_path, 'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
			'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT,
			'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'));
		return $this->display(__file__, 'payment.tpl');
		
	}
	
	public function hookPaymentReturn($params)
    {
        global $cookie, $smarty;
		include(dirname(__FILE__).'/includes/mercadopago.php');
		
		$cartmercadopago 		= new mpago(array(
		'acc_id' => Configuration::get('mercadopago_acc_id'),
		'enc' => Configuration::get('mercadopago_TOKEN'),
		'reseller_acc_id' => Configuration::get('mercadopago_reseller_acc_id'),
		'url_process' => Configuration::get('mercadopago_URLPROCESS'),
		'url_succesfull' => Configuration::get('mercadopago_URLSUCCESFULL'),
				
		
		'seller_op_id'=>$params['objOrder']->id));
        
        $state 				= $params['objOrder']->getCurrentState();  
        $order 				= new Order($mercadopago->currentOrder);
		$DadosOrder 		= new Order($params['objOrder']->id);
		$DadosCart 			= new Cart($DadosOrder->id_cart);
		$currency 			= new Currency($DadosOrder->id_currency);
		$frete 				= number_format( Tools::convertPrice( $DadosOrder->total_shipping, $currency), 2, '.', '');
		$ArrayListaProdutos = $DadosOrder->getProducts();
		$id_pedido = $params['objOrder']->id;
		$customer           = new Customer(intval($cookie->id_customer));
		$ArrayCliente = $customer->getFields();
		$ArrayClienteAdress = Db::getInstance()->getRow('SELECT a.*, cl.`name` AS country, s.name AS state
														FROM `'._DB_PREFIX_.'address` a
														LEFT JOIN `'._DB_PREFIX_.'country` c ON a.`id_country` = c.`id_country`
														LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON c.`id_country` = cl.`id_country`
														LEFT JOIN `'._DB_PREFIX_.'state` s ON s.`id_state` = a.`id_state`
														WHERE `id_lang` = '.intval($cookie->id_lang).'
														AND `id_customer` = '.intval($cookie->id_customer).'
														AND a.`deleted` = 0');
														
		$result_url_retorno = Db::getInstance()->getRow("SELECT value FROM ps_configuration WHERE name = 'mercadopago_URLPROCESS'");
		$url_retorno = $result_url_retorno['value'];
		$result_desconto = Db::getInstance()->getRow("SELECT value FROM ps_order_discount WHERE id_order = '".$params['objOrder']->id."'");
		$desconto = $result_desconto['value'];	
		
		echo "<br><br>".$desconto."<br><br>";												
		
		
		foreach($ArrayListaProdutos as $info) {
			$item = array (
				// Cria um Array com a descrições dos produtos
				$zb[]=$info['product_name'],
				'id'         => uniqid(), 
				'descricao'  => $info['product_name'],
				'quantidade' => $info['product_quantity'],
				'valor'      => $info['product_price_wt']
				);
			$cartmercadopago->adicionar($item);	
		}
				
		if($frete > 0) {
			$item = array (
				'valor'      => $frete
			);
			$cartmercadopago->adiciona_frete($item);
		}		
				
		
		$item = array (
			'quantidade'      => $id_pedido
		);
		$cartmercadopago->adiciona_id_pedido($item);
		
			
		
		$item = array (
			'descricao'      => $info['product_name'],
		);
		$cartmercadopago->adiciona_url_retorno($item);
		
		//Separa o Array
		$concat=implode("+",$zb);
		//echo $concat;
			
			$item = array (
			   'cart_name'   => $ArrayCliente['firstname'],
			   'cart_surname'=> $ArrayCliente['lastname'],
			   'cart_cep'    => $ArrayClienteAdress['postcode'],
			   'cart_street'    => $ArrayClienteAdress['address1'],
			   'cart_state'    => $ArrayCliente['state'],
			   'cart_city' => $ArrayClienteAdress['city'],
			   'cart_phone'    => $ArrayClienteAdress['phone'],
			   'name'=>$concat,
			   'cart_email'  => $ArrayCliente['email'],
			   'price' =>($params['total_to_pay']),// Tools::displayPrice($params['total_to_pay'],$params['currencyObj'], false, false),
		
			);
			$cartmercadopago->cliente($item);	
		
		$discounts = $DadosCart->getDiscounts();			
		if ( $discounts[0] ){
		
			$item = array (
				'valor'      => $DadosCart->getOrderTotal(true, 2)
			);
			$cartmercadopago->adiciona_desconto($item);
		
		}
		

		$formmercadopago = $cartmercadopago->mostra(array ('btn_submit'=> Configuration::get('mercadopago_BTN') ));
		$smarty->assign(array(
			'totalApagar' 	=> Tools::displayPrice($params['total_to_pay'],$params['currencyObj'], false, false), 
			'status' 		=> 'ok', 
			'seller_op_id' 		=> $params['objOrder']->id,
			'secure_key' 	=> $params['objOrder']->secure_key,
			'id_module' 	=> $this->id,
			'formmercadopago' => $formmercadopago
		));
		
		
		return $this->display(__file__, 'payment_return.tpl');
    }
    
        function hookHome($params)
	{
    	include(dirname(__FILE__).'/includes/retorno.php');
    }
    
        function getStatus($param)
    {
    	global $cookie;
    		
    		$sql_status = Db::getInstance()->Execute
		('
			SELECT `name`
			FROM `'._DB_PREFIX_.'order_state_lang`
			WHERE `id_order_state` = '.$param.'
			AND `id_lang` = '.$cookie->id_lang.'
			
		');
		
		return mysql_result($sql_status, 0);
    }
    
    public function enviar($mailVars, $template, $assunto, $DisplayName, $idCustomer, $idLang, $CustMail, $TplDir)
	{
		
		Mail::Send
			( intval($idLang), $template, $assunto, $mailVars, $CustMail, null, null, null, null, null, $TplDir);
		
	}
	
	public function getUrlByMyOrder($myOrder)
	{

		$module				= Module::getInstanceByName($myOrder->module);			
		$pagina_qstring		= __PS_BASE_URI__."order-confirmation.php?id_cart="
							  .$myOrder->id_cart."&id_module=".$module->id."&id_order="
							  .$myOrder->id."&key=".$myOrder->secure_key;			
		
		if	(	$_SERVER['HTTPS']	!=	"on"	)
		$protocolo			=	"http";
		
		else
		$protocolo			=	"https";
		
		$retorno 			= $protocolo . "://" . $_SERVER['SERVER_NAME'] . $pagina_qstring;			
		return $retorno;

	}
    
}
?>