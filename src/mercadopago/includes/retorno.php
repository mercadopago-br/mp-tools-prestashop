<?
include('../../../config/config.inc.php');

if ($_POST) {

	// Variáveis de retorno
	
	// Token
	$stringcampo = "mercadopago_acc_id";
	$result = Db::getInstance()->getRow("SELECT value FROM "._DB_PREFIX_."configuration WHERE name = '".$stringcampo."'");
	$token = $result['value'];
	
	
	/* Montando as variáveis de retorno */
	
	$id_transacao = $_POST['id_transacao'];
	$data_transacao = $_POST['data_transacao'];
	$data_credito = $_POST['data_credito'];
	$valor_original = $_POST['valor_original'];
	$valor_loja = $_POST['valor_loja'];
	$desconto = $_POST['desconto'];
	$acrescimo = $_POST['acrescimo'];
	$tipo_pagamento = $_POST['tipo_pagamento'];
	$parcelas = $_POST['parcelas'];
	$cliente_nome = $_POST['cliente_nome'];
	$cliente_email = $_POST['cliente_email'];
	$cliente_rg = $_POST['cliente_rg'];
	$cliente_data_emissao_rg = $_POST['cliente_data_emissao_rg'];
	$cliente_orgao_emissor_rg = $_POST['cliente_orgao_emissor_rg'];
	$cliente_estado_emissor_rg = $_POST['cliente_estado_emissor_rg'];
	$cliente_cpf = $_POST['cliente_cpf'];
	$cliente_sexo = $_POST['cliente_sexo'];
	$cliente_data_nascimento = $_POST['cliente_data_nascimento'];
	$cliente_endereco = $_POST['cliente_endereco'];
	$cliente_complemento = $_POST['cliente_complemento'];
	$status = $_POST['status'];
	$cod_status = $_POST['cod_status'];
	$cliente_bairro = $_POST['cliente_bairro'];
	$cliente_cidade = $_POST['cliente_cidade'];
	$cliente_estado = $_POST['cliente_estado'];
	$cliente_cep = $_POST['cliente_cep'];
	$frete = $_POST['frete'];
	$tipo_frete = $_POST['tipo_frete'];
	$informacoes_loja = $_POST['informacoes_loja'];
	$id_pedido = $_POST['id_pedido'];
	$free = $_POST['free'];
	
	/* Essa variável indica a quantidade de produtos retornados */
	$qtde_produtos = $_POST['qtde_produtos'];
	
	/* Verificando ID da transação */
	/* Verificando status da transação */
	/* Verificando valor original */
	/* Verificando valor da loja */
	
	$post = "transacao=$id_transacao" .
	"&status=$status" .
	"&valor_original=$valor_original" .
	"&valor_loja=$valor_loja" .
	"&token=$token";
	$enderecoPost = "https://www.mercadopago.com/mlb/buybutton";
	
	echo "<br><br>".$enderecoPost.$post."<br><br>";
	
	ob_start();
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $enderecoPost);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
	curl_exec ($ch);
	$resposta = ob_get_contents();
	ob_end_clean();
	
	//$resposta = "VERIFICADO";
	
	if(trim($resposta)=="VERIFICADO"){
	
		$order 				= new Order(intval($id_pedido));
		$cart 				= Cart::getCartByOrderId($id_pedido);
					
		$mailVars 			= array('{bankwire_owner}' => '', '{bankwire_details}' => '',
			'{bankwire_address}' => '');
					
		switch($cod_status){
		case('0'): 
			$nomestatus = "mercadopago_STATUS_0";
			break;
		case('1'): 
			$nomestatus = "mercadopago_STATUS_1";
			break;
		case('2'): 
			$nomestatus = "mercadopago_STATUS_2";
			break;
		}
		
		// Pega Id status
		$result = Db::getInstance()->getRow("SELECT value FROM "._DB_PREFIX_."configuration WHERE name = '".$nomestatus."'");
		$status = $result['value'];
		
		$total 				= floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', ''));
												
		/** /ENVIO DO EMAIL **/	
		$extraVars 			= array();
		$history 			= new OrderHistory();
		$history->id_order 	= intval($id_pedido);
		$history->changeIdOrderState(intval($status), intval($id_pedido));
		$history->addWithemail(true, $extraVars);
				
		$result = Db::getInstance()->ExecuteS("INSERT INTO "._DB_PREFIX_."order_history (`id_employee`, `id_order`, `id_order_state`, `date_add`) VALUES ('0', '".$id_pedido."', '".$status."', NOW())");
				
	}else{
		echo "Variavel 'resposta' diferente de VERIFICADO";
	}
	
}


?>
