<?php

namespace Bling;

/**
 * @name BlingSDK
 * @internal Classe para integração com a API V2 Bling
 * @file _root/adm/protected/components/BlingSDK.php
 * @author Davi Crystal | Digital UP | davicrystal@gmail.com | http://twitter.com/davicrystal
 * @license GNU GENERAL PUBLIC LICENSE Version 3
 */

class BlingSDK{

	/**
	 * @name $debug
	 * @param boolean
	 * @internal Ativa/desativa o modo de debug
	 * @access public
	 */

	public $debug = false;

	/**
	 * @name $log_file
	 * @param string
	 * @internal Define o caminho para salvar registros de log
	 * @access public
	 */

	public $log_file;

	/**
	 * @name $strBlingUrl
	 * @param string
	 * @internal Define a URL de integração com o Bling
	 * @access private
	 */

	private $strBlingUrl = "https://bling.com.br/Api/v2";

	/**
	 * @name $strApiKey
	 * @param string
	 * @internal Define a ApiKey de acesso ao Bling
	 * @access public
	 */

	public $strApiKey = "";

	/**
	 * @name postProduct
	 * @access public
	 * @internal Insere um produto no ERP Bling
	 * @internal Para inserir um produto ignore o parâmetro $strProductCode
	 * @internal Para atualizar um produto informe o parâmetro $strProductCode
	 * @author Davi Crystal
	 * @param array $arrayPreData
	 * @param string $strProductCode
	 * @return string | json
	 */

	public function postProduct($arrayPreData, $strProductCode = NULL){

		// DEFINE O CONTEXTO DO ENVIO
		$strContext = 'produto';

			// SE HOUVER O PARÂMETRO LIMPA PONTUAÇÃO E CRIA MÁSCARA PARA O PARÂMETRO CLASSE FISCAL
		    if(isset($arrayPreData['class_fiscal']) && !empty($arrayPreData['class_fiscal'])){

			    $arrayPreData['class_fiscal'] = str_replace('.', '', $arrayPreData['class_fiscal']);
			    $arrayPreData['class_fiscal'] = $this->mask($arrayPreData['class_fiscal'], '####.##.##');

			}

			// DEFINE UM VALOR PADRÂO PARA O PARÂMETRO un CASO NÃO SEJA INFORMADO
			if(empty($arrayPreData['un']) || !isset($arrayPreData['un']))
				$arrayPreData['un'] = 'un';

		// GERA A ARRAY PADRÃO PARA API 2 BLING
	    $arrayData = array("apikey"=>$this->strApiKey, "xml" => rawurlencode($this->buildXml($arrayPreData, $strContext)));

	    // EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling($strContext, 'post', $arrayData, 'json', $strProductCode);

	}

	/**
	 * @name postOrder
	 * @access public
	 * @internal Insere um pedido no ERP Bling
	 * @author Davi Crystal
	 * @param array $arrayPreData
	 * @param boolean $boolGerarNfe 
	 * @return string | json
	 */

	public function postOrder($arrayPreData, $boolGerarNfe = false){

		// DEFINE O CONTEXTO DO ENVIO
		$strContext = 'pedido';

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING CNPJ
			if(isset($arrayPreData['cliente']['cnpj']))
				$arrayPreData['cliente']['cnpj'] = str_replace(array('.','-','/'), '', $arrayPreData['cliente']['cnpj']);

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING PARA INSCRIÇÃO ESTADUAL
			if(isset($arrayPreData['cliente']['ie']))
				$arrayPreData['cliente']['ie'] = str_replace(array('.','-','/'), '', $arrayPreData['cliente']['ie']);

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING RG
			if(isset($arrayPreData['cliente']['rg']))
				$arrayPreData['cliente']['rg'] = str_replace(array('.','-','/'), '', $arrayPreData['cliente']['rg']);

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING CEP
			if(isset($arrayPreData['cliente']['cep']) && !empty($arrayPreData['cliente']['cep']))
				$arrayPreData['cliente']['cep'] = $this->mask($arrayPreData['cliente']['cep'], '##.###-###');

		// PRÉ-DEFINE O PARÂMETRO ANTES DA ITERAÇÃO A SEGUIR
		$n=0;

			// DEFINE UM VALOR PADRÂO EM CADA ITEM PARA O PARÂMETRO 'un' CASO NÃO SEJA INFORMADO 
			foreach ($arrayPreData['itens'] as $arrayValue) {
				
				if(isset($arrayValue['un']) && !$arrayValue['un'])
					$arrayPreData['itens'][$n]['item']['un'] = 'un';

				$n++;


			}

		// GERA A ARRAY PADRÃO PARA API 2 BLING
	    $arrayData = array("apikey"=>$this->strApiKey, "xml" => rawurlencode($this->buildXml($arrayPreData, $strContext)), "gerarnfe" => $boolGerarNfe);

	    // EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling($strContext, 'post', $arrayData, 'json');

	}

	/**
	 * @name postNf
	 * @access public
	 * @internal Gera uma Nota Fiscal no ERP Bling
	 * @author Davi Crystal
	 * @param array $arrayPreData
	 * @return string | json
	 */

	public function postNf($arrayPreData){

		// DEFINE O CONTEXTO DO ENVIO
		$strContext = 'notafiscal';

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING CNPJ PARA CLIENTE
			if(isset($arrayPreData['cliente']['cpf_cnpj']))
				$arrayPreData['cliente']['cpf_cnpj'] = str_replace(array('.','-','/'), '', $arrayPreData['cliente']['cpf_cnpj']);

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING PARA INSCRIÇÃO ESTADUAL PARA CLIENTE
			if(isset($arrayPreData['cliente']['ie_rg']))
				$arrayPreData['cliente']['ie_rg'] = str_replace(array('.','-','/'), '', $arrayPreData['cliente']['ie_rg']);

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING CEP PARA CLIENTE
			if(isset($arrayPreData['cliente']['cep']))
				$arrayPreData['cliente']['cep'] = $this->mask($arrayPreData['cliente']['cep'], '##.###-###');

		// PRÉ-DEFINE O PARÂMETRO ANTES DA ITERAÇÃO A SEGUIR
		$n=0;

			// DEFINE UM VALOR PADRÂO EM CADA ITEM PARA O PARÂMETRO 'un' CASO NÃO SEJA INFORMADO 
			foreach ($arrayPreData['itens'] as $arrayValue) {
				
					if(isset($arrayValue['un']) && !$arrayValue['un'])
						$arrayPreData['itens'][$n]['item']['un'] = 'un';

					// SE HOUVER O PARÂMETRO LIMPA PONTUAÇÃO E CRIA MÁSCARA PARA O PARÂMETRO CLASSE FISCAL
				    if(isset($arrayValue['classe_fiscal']) && !empty($arrayValue['classe_fiscal'])){

					    $arrayValue['classe_fiscal'] = str_replace('.', '', $arrayValue['classe_fiscal']);
					    $arrayPreData['itens'][$n]['item']['classe_fiscal'] = $this->mask($arrayValue['classe_fiscal'], '####.##.##');

					}

				$n++;

			}

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING CNPJ PARA TRANSPORTADORA
			if(isset($arrayPreData['transporte']['cpf_cnpj']))
				$arrayPreData['transporte']['cpf_cnpj'] = str_replace(array('.','-','/'), '', $arrayPreData['transporte']['cpf_cnpj']);

			// LIMPA CARACTERES DESNECESSÁRIOS NA STRING PARA INSCRIÇÃO ESTADUAL PARA TRANSPORTADORA
			if(isset($arrayPreData['transporte']['ie_rg']))
				$arrayPreData['transporte']['ie_rg'] = str_replace(array('.','-','/'), '', $arrayPreData['transporte']['ie_rg']);

			// LIMPA CARACTERES DESNECESSÁRIOS E CRIA  A MÁSCARA NA STRING CEP PARA DADOS DA ETIQUETA
			if(isset($arrayPreData['transporte']['dados_etiqueta']['cep']) && !empty($arrayPreData['transporte']['dados_etiqueta']['cep']))
				$arrayPreData['transporte']['dados_etiqueta']['cep'] = $this->mask($arrayPreData['transporte']['dados_etiqueta']['cep'], '##.###-###');

		// GERA A ARRAY PADRÃO PARA API 2 BLING
	    $arrayData = array("apikey"=>$this->strApiKey, "xml" => rawurlencode($this->buildXml($arrayPreData, $strContext)));

	    // EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling($strContext, 'post', $arrayData, 'json');

	}

	 /**
     * @name postNfService
     * @access public
     * @internal Gera uma Nota de Servico no ERP Bling
     * @author Rafael Cruz
     * @param array $arrayPreData
     * @return string | json
     */

    public function postNfService($arrayPreData){

        // DEFINE O CONTEXTO DO ENVIO
        $strContext = 'notaservico';

        // LIMPA CARACTERES DESNECESSÁRIOS NA STRING CNPJ PARA CLIENTE
        if(isset($arrayPreData['pedido']['cliente']['cnpj']))
            $arrayPreData['pedido']['cliente']['cnpj'] = str_replace(array('.','-','/'), '', $arrayPreData['pedido']['cliente']['cnpj']);

        // LIMPA CARACTERES DESNECESSÁRIOS NA STRING PARA INSCRIÇÃO ESTADUAL PARA CLIENTE
        if(isset($arrayPreData['pedido']['cliente']['ie']))
            $arrayPreData['pedido']['cliente']['ie'] = str_replace(array('.','-','/'), '', $arrayPreData['pedido']['cliente']['ie']);

        // LIMPA CARACTERES DESNECESSÁRIOS NA STRING CEP PARA CLIENTE
        if(isset($arrayPreData['pedido']['cliente']['cep']))
            $arrayPreData['pedido']['cliente']['cep'] = $this->mask($arrayPreData['pedido']['cliente']['cep'], '##.###-###');


        // GERA A ARRAY PADRÃO PARA API 2 BLING
        $arrayData = array("apikey"=>$this->strApiKey, "xml" => rawurlencode($this->buildXml($arrayPreData, $strContext)));

        // EXECUTA O ENVIO DE DADOS PARA O BLING
        return $this->sendDataToBling($strContext, 'post', $arrayData, 'json');
    }

  	/**
	 * @name putOrder
	 * @access public
	 * @internal Atualiza status de um pedido no ERP Bling
	 * @author Geilson Andrade
	 * @param array $arrayPreData
	 * @param int $strOrderNumber
	 * @return string | json
	 */

	public function putOrder($arrayPreData, $strOrderNumber){

		// DEFINE O CONTEXTO DO ENVIO
		$strContext = 'pedido';

		// GERA A ARRAY PADRÃO PARA API 2 BLING
	    $arrayData = array("apikey"=>$this->strApiKey, "xml" => rawurlencode($this->buildXml($arrayPreData, $strContext)));

	    // EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling($strContext, 'put', $arrayData, 'json', $strOrderNumber);
	}

    /**
     * @name sendNfService
     * @access public
     * @internal Emite uma Nota de Servico no ERP Bling
     * @author Rafael Cruz
     * @param int $rps_number, int $serie
     * @return string | json
     */

    public function sendNfService($rps_number, $serie){

        // DEFINE O CONTEXTO DO ENVIO
        $strContext = 'notaservico';

        // GERA A ARRAY PADRÃO PARA API 2 BLING
        $arrayData = array("apikey" => $this->strApiKey, "number" => $rps_number, "serie" => $serie);

        // EXECUTA O ENVIO DE DADOS PARA O BLING
        return $this->sendDataToBling($strContext, 'post', $arrayData, 'json');
	}
	
	/**
     * @name sendNfProduct
     * @access public
     * @internal Emite Nota Fiscal no ERP Bling
     * @author Geilson Andrade
     * @param int $number, int $serie
     * @return string | json
     */

    public function sendNfProduct($number, $serie){

        // DEFINE O CONTEXTO DO ENVIO
        $strContext = 'notafiscal';

        // GERA A ARRAY PADRÃO PARA API 2 BLING
        $arrayData = array("apikey" => $this->strApiKey, "number" => $number, "serie" => $serie);

        // EXECUTA O ENVIO DE DADOS PARA O BLING
        return $this->sendDataToBling($strContext, 'post', $arrayData, 'json');
    }

	/**
	 * @name getProduct
	 * @access public
	 * @internal Consulta um produto no ERP Bling
	 * @author Davi Crystal
	 * @param string $strProductCode
	 * @param string $responseFormat (json|xml)
	 * @return string (json|xml)
	 */

	public function getProduct($strProductCode = NULL, $responseFormat = 'xml', $arrayParams = array(), $page = NULL){

		// EXECUTA O ENVIO DE DADOS PARA O BLING
		if ($strProductCode) {
			$context = 'produto';
		} else {
			$context = 'produtos';
		}

		return $this->sendDataToBling($context, 'get', $strProductCode, $responseFormat, NULL, $arrayParams, $page);
	}

	/**
	 * @name getOrder
	 * @access public
	 * @internal Consulta um pedido no ERP Bling
	 * @author Davi Crystal
	 * @param string $strOrderCode
	 * @param string $responseFormat (json|xml)
	 * @return string (json|xml)
	 */

	public function getOrder($strOrderCode = NULL, $responseFormat = 'xml', $arrayParams = array()){

		// EXECUTA O ENVIO DE DADOS PARA O BLING
		if ($strOrderCode) {
			$context = 'pedido';
		} else {
			$context = 'pedidos';
		}

		return $this->sendDataToBling($context, 'get', $strOrderCode, $responseFormat, NULL, $arrayParams);

	}

	/**
	 * @name getNf
	 * @access public
	 * @internal Consulta uma nota fiscal no ERP Bling
	 * @author Davi Crystal
	 * @param string $strNfNumber
	 * @param string $strNfSerie
	 * @param string $responseFormat (json|xml)
	 * @return string (json|xml)
	 */

	public function getNf($strNfNumber = NULL, $strNfSerie = NULL, $responseFormat = 'xml'){

			// PREPARA O PARÂMETRO PARA CONSULTA DE NOTA FISCAL (NOTA FISCAL + SERIE)
			if($strNfNumber || $strNfSerie)
				$strNfCode = $strNfNumber . "/" . $strNfSerie;
			else 
				$strNfCode = NULL;

		// EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling('notafiscal', 'get', $strNfCode, $responseFormat);

	}

	/**
	 * @name getDeposits
	 * @access public
	 * @internal Consulta depósitos no ERP Bling
	 * @author Geilson Andrade
	 * @param string $strProductCode
	 * @param string $responseFormat (json|xml)
	 * @return string (json|xml)
	 */

	public function getDeposits($strProductCode = NULL, $responseFormat = 'xml', $arrayParams = array()){

		// EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling('depositos', 'get', $strProductCode, $responseFormat, NULL, $arrayParams);

	}

	/**
	 * @name getCategories
	 * @access public
	 * @internal Consulta categorias no ERP Bling
	 * @author Geilson Andrade
	 * @param string $strProductCode
	 * @param string $responseFormat (json|xml)
	 * @return string (json|xml)
	 */

	public function getCategories($strProductCode = NULL, $responseFormat = 'xml'){

		// EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling('categorias', 'get', $strProductCode, $responseFormat);

	}

	/**
	 * @name deleteProduct
	 * @access public
	 * @internal Deleta um produto no ERP Bling
	 * @author Davi Crystal
	 * @param string $strProductCode
	 * @param string $responseFormat (json|xml)
	 * @return string (json|xml)
	 */

	public function deleteProduct($strProductCode, $responseFormat = 'xml'){

		// EXECUTA O ENVIO DE DADOS PARA O BLING
	    return $this->sendDataToBling('produto', 'delete', $strProductCode, $responseFormat);

	}

	/**
    * @name sendDataToBling
    * @access private
    * @internal Executa o envio de dados ao Bling
    * @author Davi Crystal
    * @param string $strContext (produto|pedido|notafiscal)
    * @param string $strAction (get|post)
    * @param mix array|string $arrayData
    * @param string $strResponseFormat (json|xml)
    * @return string
    */

	private function sendDataToBling($strContext, $strAction, $arrayData = NULL, $strResponseFormat = NULL, $strItemCode = NULL, $arrayParams = array(), $page = NULL){

		// INICIA O CURL
		$curl_handle = curl_init();
	
		// SE A REQUISIÇÃO TRATAR-SE DE UM GET DE CONSULTA, PREPARA AS OPÇÕES DA URL
		if($strAction == 'get'){
			$params = '';
			if (is_array($arrayParams)) {
				foreach ($arrayParams as $k_param => $v_param) {
					$params .= '&' . $k_param . '=' . $v_param;
				}
			}

			// SE O PARÂMETRO FOR INFORMADO COMO STRING O INCLUI NA AÇÃO ENVIADA AO BLING
			if(is_string($arrayData) && $arrayData)
				$strOptions = '/' . $arrayData . '/' . $strResponseFormat . '&apikey=' . $this->strApiKey . $params;
			else 
				$strOptions = (is_int($page) ? '/page=' . $page : '') . '/' . $strResponseFormat . '&apikey=' . $this->strApiKey . $params;

		// SE A REQUISIÇÃO TRATAR-SE DE UM POST PREPARA AS OPÇÕES DA URL
		}elseif($strAction == 'post'){

			// SE A REQUISIÇÃO TEM COMO ORIGEM UM POST PREPARA OS DADOS PARA ENVIO
			curl_setopt($curl_handle, CURLOPT_POST, count($arrayData));
    		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $arrayData);

			// SE O PARÂMETRO $arrayData FOR INFORMADO SIGINIFICA QUE HÁ UM CÓDIGO DE PRODUTO
			// E A URL PARA O POST DEVE INCLUIR O CÓDIGO DO MESMO
			if($strItemCode)
				$strOptions = '/' . $strItemCode;
			else
				$strOptions = '';
			// SE O PARÂMETRO $arrayData FOR IGNORADA SIGNIFICA QUE'TRATA-SE DE UM POST DE CRIAÇÃO DE PRODUTO
			// By @RafaelCruz: Fix para setar o formato de resposta também para requisicoes POST
			$strOptions .= ($strResponseFormat) ? '/' . $strResponseFormat : NULL;

		// SE A REQUISIÇÃO TRATAR-SE DE UM PUT PREPARA AS OPÇÕES DA URL
		}elseif($strAction == 'put'){

			// SE A REQUISIÇÃO TEM COMO ORIGEM UM PUT PREPARA OS DADOS PARA ENVIO
			curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
    		curl_setopt($curl_handle, CURLOPT_POST, count($arrayData));
    		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $arrayData);

			$strOptions = '/' . $strItemCode . (($strResponseFormat) ? '/' . $strResponseFormat : NULL);

		// SE A REQUISIÇÃO TRATAR-SE DE UM DELETE PREPARA AS OPÇÕES DA URL
		}elseif($strAction == 'delete'){

			$strOptions = '/' . $arrayData . '/' . $strResponseFormat;

			curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, array('apikey'=>$this->strApiKey));

		}

		// DEFINE A URL DE AÇÃO
		curl_setopt($curl_handle, CURLOPT_URL, $this->strBlingUrl . '/'. $strContext . $strOptions);

		// DEFINE O PARÂMETRO DE RETORNO DO CURL
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);

	    // EXECUTA O ENVIO
	    $response = curl_exec($curl_handle);
	    
	    // LOG
	    if($this->debug){
		    $log = strtoupper($strAction) . ' ' . $this->strBlingUrl . '/'. $strContext . $strOptions;

		    if($arrayData){
		    	$log .= "\r\n> Data:\r\n" . urldecode(print_r($arrayData, true));
		    }

			$log .= "\r\n> Error:\r\n" . curl_error($curl_handle);
			
			$log .= "\r\n> Response:\r\n" . $response;

		    $this->log($log);
	    }

	    // FECHA A CONEXÃO
	    curl_close($curl_handle);

	    // RETORNA A RESPONSTA DO ENVIO
	    return $response;
	}

	/**
    * @name buildXml
    * @access private
    * @internal Costroi o primeiro nível do XML 
    * @author Davi Crystal
    * @param array $array
    * @param string $context
    * @return string | xml
    */

    private function buildXml($array = array(NULL), $context){

    	// DEFINE O PARÂMETRO PARA ENCODING
        $encoding = 'UTF-8';

        // CRIA O CABEÇALHO DO XML
        if($array){
        	$array_keys = array_keys($array);
        	if(count($array_keys) == 1){
        		$xml = new \SimpleXMLElement("<?xml version='1.0' encoding='".$encoding."' ?><".$array_keys[0]." />");
        		$array = $array[$array_keys[0]];
        	}else{
        		$xml = new \SimpleXMLElement("<?xml version='1.0' encoding='".$encoding."' ?><".$context." />");
        	}
        }

		// ITERA CADA ELEMENTO DA ARRAY PARA CRIAÇÃO DOS NÓS DO XML
        foreach($array as $key1 => $value1){

    		// SE O VALOR FOR UMA NOVA ARRAY OU OBJETO, CHAMA A FUNÇÃO PARA CRIAÇÃO DE NÓS
    		// ABAIXO DO NÓ ATUAL
            if(is_array($value1) || is_object($value1)){

            	// CASO A CHAVE NÃO SEJA UM NÚMERO DA ARRAY ADICIONA O NOVO NÓ
            	if(!is_numeric($key1))
            		$newNode = $xml->addChild($key1);
            	else // CASO A CHAVE SEJA UM NÚMERO DA ARRAY DEFINE C NOVO NÓ COMO O NÓ PAI
            		$newNode = $xml;

                // ADICIONA O NOVO NÓ FILHO
    			$this->constructXmlNode($value1,$newNode);

    		// CASO O VALOR EXISTA E SEJA UM STRING, CRIA UM UM NOVO NÓ NO XML, EXISTEM VALORES DEFINIDOS COMO "0"
            } elseif($value1 != "") 
                $xml->addChild($key1,  html_entity_decode($value1));

        }

        // RETORNA O XML
        return $xml->asXML();

    }

    /**
    * @name constructXmlNode()
    * @access private
    * @internal Transforma arrays em XML a nível infinito
    * @author Davi Crystal
    * @param array $array
    * @param object $node
    */

    private function constructXmlNode($array, $node){

    	// ITERA CADA ITEM DA ARRAY RECEBIDA
        foreach ($array as $key => $value){

			// SE O VALOR FOR UMA NOVA ARRAY OU OBJETO, CHAMA A FUNÇÃO PARA CRIAÇÃO DE NÓS
            // ABAIXO DO NÓ ATUAL
            if(is_array($value) || is_object($value)){

            		// CASO A CHAVE NÃO SEJA UM NÚMERO DA ARRAY ADICIONA O NOVO NÓ
                	if(!is_numeric($key))
                		$newNode = $node->addChild($key);
                	else // CASO A CHAVE SEJA UM NÚMERO DA ARRAY DEFINE C NOVO NÓ COMO O NÓ PAI
                		$newNode = $node;

                // INICIA A CONSTRUÇÃO DE UM NOVO NÓ FILHO
        		$this->constructXmlNode($value,$newNode);

        	// CASO O VALOR EXISTA E SEJA UM STRING, CRIA UM UM NOVO NÓ NO XML, EXISTEM VALORES DEFINIDOS COMO "0"
	        }elseif($value != "")
                $node->addChild($key, htmlspecialchars($value));
                                
        }

    }

	/**
    * @name mask
    * @access private
    * @internal Mascara valores de forma universal
    * @see http://blog.clares.com.br/php-mascara-cnpj-cpf-data-e-qualquer-outra-coisa/
    * @author Rafael Clares
    * @param string $val
    * @param string $mask
    * @return string
    */

	private function mask($val, $mask){

		$maskared = '';

		$k = 0;

			for($i = 0; $i<=strlen($mask)-1; $i++){

				 if($mask[$i] == '#'){

					 if(isset($val[$k]))
						 $maskared .= $val[$k++];

				 } else {

				 	if(isset($mask[$i]))
				 		$maskared .= $mask[$i];
				 }

			}

		return $maskared;


	}

	/**
    * @name log
    * @access protected
    * @internal Escreve em arquivo de log
    * @author Rafael Cruz
    * @param string $log
    * @return boolean
    */

	protected function log($log){
		if(!empty($this->log_file)){
			$log = "\r\n\r\n" . date('Y-m-d H:i:s') . "\r\n" . $log;
	        $h = fopen($this->log_file, 'a+');
	        fwrite($h, print_r($log, true));
	        fclose($h);
	        return true;
		}
		return false;
	}


}