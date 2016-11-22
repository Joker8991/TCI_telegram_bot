<?php

class Tools
{
	private $nomiTag = array('defibrillatore' => array('plur' => 'defibrillatori', 'tag' =>array('[emergency=defibrillator]'), 'distance' => 500),
					'estintore' =>array('plur' => 'estintori', 'tag' => array('[emergency=fire_extinguisher]'),'distance' => 500),
					'idrante' => array('plur' => 'idranti', 'tag' => array('[emergency=fire_hydrant]','[emergency=fire_hose]'),'distance' => 1000),
					'ospedale' => array('plur' => 'ospedali', 'tag' => array('[amenity=hospital][emergency=yes]'),'distance' => 10000), 
					'area di emergenza' => array('plur' => 'aree di emergenza', 'tag' => array('[emergency=assembly_point]'), 'distance' => 500));

	private $keyboards = array(
		'mainKeyboard' => array(
       				array('Emergenze'),
       				array('Fabbisogni','Alloggi')),
		'emergencyKeyboard'=> array(
       				array('Defibrillatore','Estintore','Idrante'),
       				array('Area di emergenza', 'Ospedale'),
       				array('Annulla')),
		'locationKeyboard' => array(
       				array(array('text'=>'Invia la mia posizione', 'request_location' => true)),
       				array('Annulla')));

	
	
	public function pluralize($name, $count)
	{
		if($count!=1)
			return $this->nomiTag[$name]['plur'];
		return $name;
	}

	//Ritorna il nome del tag OSM associato all'oggetto da cercare
	public function getTag($name)
	{
		if(isset($this->nomiTag[$name])){

			return $this->nomiTag[$name]['tag'];
		}
		return null; 
	}

	//Ritorna la distanza massima cui cercare l'oggetto ripetto alla posizione dell'utente
	public function getDistance($name)
	{
		if(isset($this->nomiTag[$name])){

			return $this->nomiTag[$name]['distance'];
		}
		return null; 
	}

	public function getDistString($dist)
	{
		if($dist>999)
			return (($dist/1000).' km');
		return "$dist m";
	}


	public function setKeyboard($keyboardName,$resize =false)
	{
		return array('keyboard'=>$this->keyboards[$keyboardName],'resize_keyboard' => $resize);
	}
}

?>