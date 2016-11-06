<?php

/*Questo script deve essere invocato da terminale e serve
 *ad impostare il webhook
 */

define('BOT_TOKEN', 'BOT_TOKEN_HERE');
//settare WEBHOOK_URL = '' se si vuole rimuovere un webhook esistente
define('WEBHOOK_URL', 'APP_ROOT_HERE/webhook.php');

$url = "https://api.telegram.org/bot%s/setWebhook?url=%s";
$url = sprintf($url, BOT_TOKEN, WEBHOOK_URL);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$ret = curl_exec($ch);
curl_close($ch);
if($ret != false)
{
	$ret = json_decode($ret,true);
	echo($ret["description"]."\n");
}
else
	echo("Si è verificato un errore nel settare il webhook\n");

?>
