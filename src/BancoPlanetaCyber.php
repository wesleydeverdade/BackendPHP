<?php

namespace Reweb\Job\Backend;

/**
 * Classe Banco do PlanetaCyber
 *
 * @author Wesley Magalhães <wesleymagalhaes5@hotmail.com>
 * criei um json para ter contas para manipular, conseguir salvar, alterar e etc,
 A estrutura básica para as contas é essa
	{		 
	"123456":{ // id da conta 
		"tipo_conta":"0", // 0 corrente. 1 poupança
		"saldo":12000 // float com o valor da conta
		"senha": '1919' // string com o senha para as operações
 		}
	} 	
**/

class BancoPlanetaCyber{
   
	const limite_saque_conta_corrente = 600;
	const limite_saque_conta_poupanca = 1000;
	const taxa_operacao_saque_corrente = 2.50;
	const taxa_operacao_saque_poupanca = 0.80;
	private $contas = "";

    /* Para evitar setters e getters, utilizei os magic methodes __get e __set */

  	public function __get($property) {
    	if (property_exists($this, $property)) {
    	  	return $this->$property;
    	}
  	}

  	public function __set($property, $value) {
    	if (property_exists($this, $property)) {
      		$this->$property = $value;
    	}

    	return $this;
  	}

  	/* no construct da classe, é lido o json e armazendo em memória */

  	function __construct(){
		$this->listaContas(); 
  	}

    public function listaContas(){
    	$this->contas = json_decode(file_get_contents('src/contas.json'));
    }

    /* esse método é para salvar alterações realizadas no json, e atualizar a variável em memória após isso*/

    public function salvaContas(){
    	if( file_put_contents('src/contas.json', json_encode($this->contas) ) ){
    		$this->listaContas();
    		return true;
    	}else{
    		return false;
    	}
    }

	/**
     * verifica se a conta existe
     *
     * @return bool
     */

    public function contaExiste($id_conta=""){
     	if(property_exists($this->contas, $id_conta)) 
     		return true;
     	else
     		return false;		
    }

    /**
     * verifica se a senha bate
     *
     * @return bool
     */

    public function valida_senha($id_conta="", $senha=""){
     	if($this->contas->$id_conta->senha == $senha) 
     		return true;
     	else
     		return false;		
    }

    /* faz deposito na conta, fazendo algumas validações */

    public function deposito($conta_destino="", $senha="", $valor=0){

    	$ret = new \stdClass();
    	$ret->err = false;
    	$ret->msg = "Operação realizada com sucesso.";

    	if($valor<=0){
    		$ret->err = true;
    		$ret->msg = "Você está tentando depositar um valor inválido. Deposite valores acima de zero!";
    		return $ret;
    	}

    	if($conta_destino=="" || $valor==""){
    		$ret->err = true;
    		$ret->msg = "Conta ou valor a ser depositado não informados!";
    		return $ret;
    	}

        if($this->contaExiste($conta_destino)===TRUE){

        	if($this->valida_senha($conta_destino, $senha) === FALSE){
				$ret->err = true;
	    		$ret->msg = "A senha informada é inválida.";
	    		return $ret;
			}

      		$this->contas->$conta_destino->saldo += $valor; 
     		
     		if(!$this->salvaContas()){
     			$ret->err = true;
    			$ret->msg = "Ocorreu um problema ao realizar operação.";
     		}   

        }else{
         	$ret->err = true;
    		$ret->msg = "A conta informada não existe.";
        }

        return $ret;
    }

    /* faz a saque da conta, verificando se a primeira tem saldo suficiente e se não esbarra no limite+taxa  */

	public function saque($conta_destino="", $senha="", $valor=0){
 
        $ret = new \stdClass();
    	$ret->err = false;
    	$ret->msg = "Operação realizada com sucesso.";

    	if($valor<=0){
    		$ret->err = true;
    		$ret->msg = "Você está tentando sacar um valor inválido. Saque valores acima de zero!";
    		return $ret;
    	}

    	if($conta_destino=="" || $valor==""){
    		$ret->err = true;
    		$ret->msg = "Conta ou valor a ser depositado não informados!";
    		return $ret;
    	}

        if($this->contaExiste($conta_destino)===TRUE){

        	if($this->valida_senha($conta_destino, $senha) === FALSE){
				$ret->err = true;
	    		$ret->msg = "A senha informada é inválida.";
	    		return $ret;
			}

        	// conta corrente
        	if($this->contas->$conta_destino->tipo_conta==0){
        		$taxa_operacao = self::taxa_operacao_saque_corrente;
        		$limite_operacao = self::limite_saque_conta_corrente;
        	// senao conta poupança	
        	}elseif($this->contas->$conta_destino->tipo_conta==1){
				$taxa_operacao = self::taxa_operacao_saque_poupanca;
        		$limite_operacao = self::limite_saque_conta_poupanca;
        	}

        	if($valor > $limite_operacao ){
        		$ret->err = true;
    			$ret->msg = "O valor informado para a transação é maior do que o limite. Faça um saque de menor valor.";
    			return $ret;
        	}

     		if($this->contas->$conta_destino->saldo >= ($valor + $taxa_operacao) ) {
     			$this->contas->$conta_destino->saldo -= $valor + $taxa_operacao; 
     		
	     		if(!$this->salvaContas()){
	     			$ret->err = true;
	    			$ret->msg = "Ocorreu um problema ao realizar operação.";
	     		}  
        	}else{
        		$ret->err = true;
    			$ret->msg = "O valor informado para a operação é maior do que o saldo. Faça um saque de menor valor. ";
        	}      		 

        }else{
         	$ret->err = true;
    		$ret->msg = "A conta informada não existe.";
        }

        return $ret;

    }
	
    /* faz a transferência de conta a para conta b, verificando se a primeira tem saldo suficiente  */


	public function transferencia($conta_inicial="", $senha="", $conta_final="", $valor=0){

		$ret = new \stdClass();
    	$ret->err = false;
    	$ret->msg = "Operação realizada com sucesso.";
        
		if($valor<=0){
    		$ret->err = true;
    		$ret->msg = "Você está tentando transferir um valor inválido. Transfira valores acima de zero!";
    		return $ret;
    	}

		if($this->contaExiste($conta_inicial) === FALSE){
			$ret->err = true;
    		$ret->msg = "A conta informada não existe (retirada).";
    		return $ret;

		}elseif($this->contaExiste($conta_final) === FALSE){
			$ret->err = true;
    		$ret->msg = "A conta informada não existe (depósito).";
    		return $ret;

		}

		if($this->valida_senha($conta_inicial, $senha) === FALSE){
			$ret->err = true;
    		$ret->msg = "A senha informada é inválida.";
    		return $ret;
		}

		if($valor > $this->contas->$conta_inicial->saldo ){
        	$ret->err = true;
    		$ret->msg = "O valor informado para a transação é maior do que o limite. Transfira um valor menor.";
    		return $ret;
        }else{

        	$this->contas->$conta_inicial->saldo -= $valor;
        	$this->contas->$conta_final->saldo += $valor;

        	if(!$this->salvaContas()){
     			$ret->err = true;
    			$ret->msg = "Ocorreu um problema ao realizar operação.";
     		} 

        }

        return $ret;		 
    }


    /* retorna o saldo da conta */

    public function exibeSaldo($conta_destino="", $senha=""){

    	$ret = new \stdClass();
    	

    	if($this->contaExiste($conta_destino) === FALSE){
			$ret->err = true;
    		$ret->msg = "A conta informada não existe (retirada).";
    		return $ret;

		}elseif($this->valida_senha($conta_destino, $senha) === FALSE){
			$ret->err = true;
    		$ret->msg = "A senha informada é inválida.";
    		
		}else{
			$ret->err = false;
    		$ret->msg = 'Seu saldo é de B$ '.$this->contas->$conta_destino->saldo;
		}

		return $ret;

    }



}