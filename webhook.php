<?php

include('Bot.php');
include ('GestoreMessaggi.php');

$bot = new Bot();

//Per debug 
//putenv('BOT_TOKEN=BOT_TOKEN_HERE');

$update = $bot->getUpdates();
$gestoreMessaggi = new GestoreMessaggi();
$gestoreMessaggi->processaUpdate($update, $bot);


?>
