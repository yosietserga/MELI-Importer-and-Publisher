<?php 
require_once getcwd()."/src/Phois/Whois/Whois.php";
require_once "NecoyoadDomain.php";

Class verificarDominio extends Whois{

	//public $extensiones_genericas = array('com','net','org','info','biz','aero','museum','pro','travel','tel','mobi','jobs','coop','cat','asia','name','edu','gov','int','mil'); 
	public $extensiones_genericas = array('com'=>'$9.99*','pe'=>'$45','com.pe'=>' $45*','net'=>'$9.99*','org'=>'$9.99*');
	public $extensiones_genericas_precio_antiguo = array('com'=>'$12.99*','pe'=>'' ,'com.pe'=>'','net'=>'$16.99*','org'=>'');
	public $pasa = false;

	public function __construct($sld){
			parent::__construct($sld);
			$whois_answer = $this->info();
		
	}
	function buscarCadenaEn($string, $cadena){

		$cadena_de_texto = $string;
		$cadena_buscada   = $cadena;
		$posicion_coincidencia = strrpos($cadena_de_texto, $cadena_buscada);
		
		//se puede hacer la comparacion con 'false' o 'true' y los comparadores '===' o '!=='
		if ($posicion_coincidencia === false) {
			return false;
		} else {
			return true;
		}
		
		$posicion_coincidencia = strrpos($cadena_de_texto, $cadena_buscada, -20);
		if ($posicion_coincidencia === false) {
			return false;
		} else {
			return true;
		}
	}

}


?>
