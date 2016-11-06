<?php

class Tools
{
	private $nomiTag = array('defibrillatore' => array('plur' => 'defibrillatori', 'tag' =>array('[emergency=defibrillator]')),
					'estintore' =>array('plur' => 'estintori', 'tag' => array('[emergency=fire_extinguisher]')),
					'idrante' => array('plur' => 'idranti', 'tag' => array('[emergency=fire_hydrant]','[emergency=fire_hose]')),
					'punto di raccolta' => array('plur' => 'punti di raccolta', 'tag' => array('[assembly_point]')));

	private $keyboards = array(
		'mainKeyboard' => array(
       				array('Emergenze'),
       				array('Fabbisogni','Alloggi')),
		'emergencyKeyboard'=> array(
       				array('Defibrillatore','Punto di raccolta'),
       				array('Estintore','Idrante'),
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


	public function getTag($name)
	{
		if(isset($this->nomiTag[$name])){

			return $this->nomiTag[$name]['tag'];
		}
		return null; 
	}


	public function setKeyboard($keyboardName,$resize =false)
	{
		return array('keyboard'=>$this->keyboards[$keyboardName],'resize_keyboard' => $resize);
	}


	function comparaDist($a, $b)
		{
        	if ($a['dist'] == $b['dist'])
        	{
        		return 0;
    		}
    		return ($a['dist'] < $b['dist']) ? -1 : 1;
		}
	
	/* Al momento l'ordinamento viene fatto in base alla distanza in linea d'aria
	 * tra la posizione inviata e i risultati sulla mappa.
	 * Sarebbe meglio tenere conto delle strade
	 */
	function ordinaArray($myLocation, &$src)
	{
		$mylat = $myLocation['latitude'];
		$mylon = $myLocation['longitude'];

		foreach($src as &$elem)
		{	
			$x = $elem['lat'] - $mylat;
			$y = $elem['lon'] - $mylon;
			$dist = sqrt($x*$x + $y*$y);
			$elem['dist'] = $dist;
		}
				
		usort($src, array($this, "comparaDist"));
    }

}

?>