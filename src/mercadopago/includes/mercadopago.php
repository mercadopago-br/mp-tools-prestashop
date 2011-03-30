<?php

class mpago {
  var $_itens = array();
  var $_config = array ();
  var $_cliente = array ();
  var $_frete = array ();  
  var $_id_pedido = array ();  
  var $_url_retorno = array ();  
  var $_desconto = array();
 
  function mpago($args = array()) {
    if ('array'!=gettype($args)) $args=array();
    $default = array(
      
      'tipo_integracao'            => 'MP3',
      /*'frete'           => '',*/
    );
    $this->_config = $args+$default;
  }

  function error($msg){
    trigger_error($msg);
    return $this;
  }

  function adicionar($item) {
    if ('array' !== gettype($item))
      return $this->error("Item precisa ser um array.");
    if(isset($item[0]) && 'array' === gettype($item[0])){
      foreach ($item as $elm) {
        if('array' === gettype($elm)) {
          $this->adicionar($elm);
        }
      }
      return $this;
    }

    $tipos=array(
      "id" =>         array(1,"string",                '@\w@'         ),
      "quantidade" => array(1,"string,integer",        '@^\d+$@'      ),
      "valor" =>      array(1,"double,string,integer", '@^\d*\.?\d+$@'),
      "descricao" =>  array(1,"string",                '@\w@'         ),
      "frete" =>      array(0,"double,string,integer", '@^\d*\.?\d+$@'),
      "peso" =>       array(0,"string,integer",        '@^\d+$@'      ),
    );

    foreach($tipos as $elm=>$valor){
      list($obrigatorio,$validos,$regexp)=$valor;
      if(isset($item[$elm])){
		/*echo '<h1>item:'.$elm.' tipo: '.gettype($item[$elm]).'</h1>';*/
		if(strpos($validos,gettype($item[$elm])) === false ||
          (gettype($item[$elm]) === "string" && !preg_match($regexp,$item[$elm]))){
          return $this->error("Valor invalido passado para $elm.");
        }
      }elseif($obrigatorio){
        return $this->error("O item adicionado precisa conter $elm");
      }
    }

    $this->_itens[] = $item;
    return $this;
  }
  
   function adiciona_frete($args=array()) {
    if ('array'!==gettype($args)) return;
    $this->_frete = $args;
  }
  
  
  function adiciona_id_pedido($args=array()) {
    if ('array'!==gettype($args)) return;
    $this->_id_pedido = $args;
  }
  
    
  function adiciona_url_retorno($args=array()) {
    if ('array'!==gettype($args)) return;
    $this->_url_retorno = $args;
  }

  
  function adiciona_desconto($args=array()) {
    if ('array'!==gettype($args)) return;
    $this->_desconto = $args;
  }
  
  function cliente($args=array()) {
    if ('array'!==gettype($args)) return;
    $this->_cliente = $args;
  }
 
  function mostra ($args=array()) {
    $default = array (
      'print'       => false,
      'open_form'   => true,
      'close_form'  => true,
      'show_submit' => true,
      'img_button'  => false,
      'bnt_submit'  => false,
    );
    $args = $args+$default;
    $_input = '  <input type="hidden" name="%s" value="%s"  />';
    $_form = array();
    if ($args['open_form'])
      $_form[] = '
	  <form target="mercadopago" action="https://www.mercadopago.com/mlb/buybutton"   method="post">';
    foreach ($this->_config as $key=>$value)
      $_form[] = sprintf ($_input, $key, $value);

    $assoc = array (
      'id' => 'produto_codigo',
      'descricao' => 'produto_descricao',
      'quantidade' => 'produto_qtde',
    );
    $i=1;
    foreach ($this->_itens as $item) {
      foreach ($assoc as $key => $value) {
        $sufixo=($this->_config['tipo_integracao']=="CBR")?'':'_'.$i;
        $_form[] = sprintf ($_input, $value.$sufixo, $item[$key]);
        unset($item[$key]);
      }
      $_form[] = sprintf ('  <input type="hidden" name="%s" value="%.2f"  />', "produto_valor$sufixo", ($item['valor']));
      unset($item['valor']);

      foreach ($item as $key=>$value)
        $_form[] = sprintf ($_input, "item_{$key}{$sufixo}", $value);

      $i++;
    }

    foreach ($this->_id_pedido as $key=>$value)
      $_form[] = sprintf ($_input, "id_pedido", $value);
	
    foreach ($this->_frete as $key=>$value)
      $_form[] = sprintf ($_input, "frete", $value);
	  
    foreach ($this->_cliente as $key=>$value)
      $_form[] = sprintf ($_input, "$key", $value);
	
    foreach ($this->_url_retorno as $key=>$value)
      $_form[] = sprintf ($_input, "url_retorno", $value);

    foreach ($this->_desconto as $key=>$value)
      $_form[] = sprintf ($_input, "desconto", ($value)*-1);
	  
    if ($args['show_submit']) {
    	
    	$mercadopago = new mercadopago();
		$value 	= $args['btn_submit'];
		$btn 	= $mercadopago->_botoes[$value];
		    	
      if ($args['btn_submit'] == 0) {
        $_form[] = sprintf('  <center>
		<input type="submit" value="Efetuar Pagamento" class="exclusive_large" />
		</center>', 
		$args['img_button']);
      } elseif ($args['btn_submit']) {
		$_form[] = sprintf ('  <center><!--<input type="image" src="https://www.mercadopago.com/org-img/MP3/buy_now_02_mlb.gif"  
		name="submit" alt="Pague com o MercadoPago" />--></center>', $btn);
      } else {
        $_form[] = '  <center><input type="submit" value="Efetuar Pagamento" class="exclusive_large" />
		</center>';
      }
    }
    if($args['close_form']) $_form[] = '</form>';
    $return = implode("\n", $_form);
    if ($args['print']) print ($return);
    return $return;
  }
}

?>
