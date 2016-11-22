<?php

include('Tools.php');
// update this to the path to the "vendor/"
// directory, relative to this file
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;


define('DEBUG', false);


class GestoreMessaggi
{
	private $tools;


	public function __construct()
	{
		$this->tools = new Tools();
	}

	function parseYaml($elToParse)
	{	
		$formattedString ='';
		//rimuovo i tag <pre> e <yamldata> ad inizio e fine stringa
		$elToParse = strip_tags($elToParse);
		/*Implementazioni con altra libreria
		//richiede yaml >= 0.4.0
		$parsedYaml = yaml_parse($elToParse);
		*/
		try
		{
	    	$parsedYaml = Yaml::parse($elToParse);
		}
		catch (ParseException $e)
		{
    		printf("Unable to parse the YAML string: %s", $e->getMessage());
		}
		$location  = array();
		foreach ($parsedYaml as $key => $value)
		{
			if(isset($value) && is_string($value) && strlen($value)>0)
			{
				str_replace(array('&','<','>'),array('&amp','&lt','&gt'), $value);
				if($key == 'lat' || $key == 'lon')
					$location[$key] = $value;
				else
					$formattedString.="<b>$key</b>: $value\n";
			}
		}
		if(count($location)===2)
			$formattedString.="<a href=\"http://www.openstreetmap.org/?mlat={$location['lat']}&mlon={$location['lon']}&zoom=16\">Posizione sulla mappa</a>";
		return $formattedString;
	}


	function handleGithubMessage($chatId, $text, $page, $bot)
	{
		if(DEBUG)
		{
			$elements = file_get_contents('./alloggi_test.json');
			$results = json_decode($elements,true);
		}
		else
		{
			$filters = array('labels' => "$text,accettato", 'state' => 'open', 'per_page' => 10, 'page' => $page);
    		$results = $bot->githubApiRequest($filters);
    	}
    	if(isset($results))
    	{
    		$elements = $results['content'];
    		$numElem = count($elements);
			for ($i=0; $i <$numElem ; $i++)
			{ 
				$parsed = $this->parseYaml($elements[$i]['body']);
				$parameters = array('chat_id' => $chatId, 'text' => $parsed, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true);
				if($i == $numElem-1 && isset($results['nextPage']))
				{
					$inlineKeyboard = array('inline_keyboard' => array(array(array('text' => 'Mostra altri', 'callback_data' => "$text {$results['nextPage']}"))));
					$parameters['reply_markup'] = $inlineKeyboard; 
				}
				
				$bot->apiRequest('sendMessage', $parameters);
			}
		}
	}

	function processaMessaggio($chatId, $text, $page, $firstname, $bot)
	{
		$parseMode = '';
		switch ($text)
		{
			case '/start':
			case 'annulla':
				$response = "Ciao $firstname, cosa posso fare per te?\nClicca una delle seguenti opzioni o invia /help per ricevere informazioni sulle funzionalità del bot.";
				$keyboard = $this->tools->setKeyboard('mainKeyboard');
				break;
			case '/help':
				$response = "Clicca su:\n<b>Emergenze:</b> per ricevere informazioni sui dispositivi e luoghi utili in caso di emergenza.\n"
				."<b>Alloggi:</b> per vedere la lista degli alloggi messi a disposizione dei terremotati\n"
				."<b>Fabbisogni:</b> se vuoi renderti utile soddisfacendo alcune delle richieste in lista\n";
				$keyboard = $this->tools->setKeyboard('mainKeyboard');
				$parseMode = 'HTML';
				break;
   			case 'emergenze':
   				$response = 'Clicca su uno dei seguenti elementi, e successivamente su "Invia la mia posizione" per ricevere la posizione di questi ultimi, se presenti, più vicini a te.';
   				$keyboard = $this->tools->setKeyboard('emergencyKeyboard');
       			break;
   			case 'fabbisogni':
       		case 'alloggi':
       			$this->handleGithubMessage($chatId,$text,$page,$bot);	
       			return;
    		case 'defibrillatore':
    		case 'estintore':
    		case 'idrante':
    		case 'area di emergenza':
    		case 'ospedale':
    			$response = $text;
    			$keyboard = $this->tools->setKeyboard('locationKeyboard',true);
    			break;
   			default:
   				$response = 'Comando non valido';
   				$keyboard = $this->tools->setKeyboard('mainKeyboard');
   		}
   		$parameters = array('chat_id' => $chatId, 'text' => $response, 'reply_markup' => $keyboard, 'parse_mode' => $parseMode);
   		$bot->apiRequest('sendMessage', $parameters);
 	}

	
    //file_put_contents('php://stderr', print_r($replytoMessage, TRUE));
	function processaLocationMsg($chatId, $replytoMessage, $location, $bot)
	{
		$keyboard = $this->tools->setKeyboard('mainKeyboard');
		$parameters = array('chat_id' => $chatId, 'reply_markup' => $keyboard);
		$tag = $this->tools->getTag($replytoMessage);
		$dist = $this->tools->getDistance($replytoMessage);
		$distString = $this->tools->getDistString($dist);
		if(isset($tag))
		{
			if(DEBUG)
			{
				$results = file_get_contents('./map_test_response.json');
				$elements = json_decode($results,true);
				$elements = $elements['elements'];
			}
			else
				$elements = $bot->overpassMapRequest($location['latitude'], $location['longitude'], $tag, $dist);

			$numelem = count($elements);
			$parameters['text'] = "Ho trovato $numelem {$this->tools->pluralize($replytoMessage, $numelem)} nel raggio di $distString.";
			$bot->apiRequest('sendMessage',$parameters);

			if($numelem>0)
			{
				foreach ($elements as $key)
				{
					if($key['type']!= 'node')
					{
						$lat = $key['center']['lat'];
						$lon = $key['center']['lon'];					
					}
					else
					{
						$lat = $key['lat'];
						$lon = $key['lon'];	
					}
					$locParameters = array('chat_id' => $chatId, 'latitude' => $lat, 'longitude' => $lon);
					$bot->apiRequest('sendLocation', $locParameters);
					if(isset($key['tags']['name']))
					{
						$nameAddress = $key['tags']['name'];
						if(isset($key['tags']['addr:street']))
							$nameAddress.="\n".$key['tags']['addr:street'];
						if(isset($key['tags']['addr:housenumber']))
							$nameAddress.=' '.$key['tags']['addr:housenumber'];
						
						$parameters['text'] = "$nameAddress";
						$bot->apiRequest('sendMessage', $parameters);			
					}
				}
			}
		}
		else
		{
			$parameters['text'] = 'comando non valido';
			$bot->apiRequest('sendMessage', $parameters);
		}
	}	


	function processaUpdate($update, $bot)
	{
		$callbackQuery = isset($update['callback_query']) ? $update['callback_query'] : null; 
		if(isset($callbackQuery))
		{
			$message = isset($callbackQuery['message']) ? $callbackQuery['message'] : ''; 
			$pageText = explode(' ', $callbackQuery['data']);
			$text = $pageText[0];
			$page = $pageText[1];
		}
		else
		{
			$message = isset($update['message']) ? $update['message'] : '';
			$text = isset($message['text']) ? $message['text'] : '';
			$text = trim($text);
			$text = strtolower($text);
			$page = '1';
		}

		$chatId = isset($message['chat']['id']) ? $message['chat']['id'] : '';

		if(isset($message['reply_to_message']) && isset($message['location']))
		{	
			$this->processaLocationMsg($chatId, $message['reply_to_message']['text'], $message['location'], $bot);
			return;
		}
		$firstname = isset($message['chat']['first_name']) ? $message['chat']['first_name'] : '';
		//$messageId = isset($message['message_id']) ? $message['message_id'] : '';
		//$lastname = isset($message['chat']['last_name']) ? $message['chat']['last_name'] : '';
		//$username = isset($message['chat']['username']) ? $message['chat']['username'] : '';
		//$date = isset($message['date']) ? $message['date'] : '';
		
		$this->processaMessaggio($chatId, $text, $page, $firstname, $bot);
	}	
}

?>