<?php
require '../vendor/autoload.php';

use OpenAI\Client;
use OpenAI\Factory;

include('../inc/app_data.php');
include '../database/connection.php';

// Your API key from platform.openai.com
$openai_api_key = 'sk-your_openai_api_key_here';

// Create reusable OpenAI client
$openai = (new Factory())
    ->withApiKey($openai_api_key)
    ->make();
?>
