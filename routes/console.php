<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('elastic:save', function () {
    ini_set('memory_limit', '-1');
    $outFileName = storage_path('app/data');
    
    $openFile = fopen($outFileName, 'r');
    if (!$openFile) {
        return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
    }
    $readFile = fread($openFile, filesize($outFileName));
    if (!$readFile) {
        return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
    }
    $parseLine = explode("\n", $readFile);
    if (!$parseLine) {
        return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data in a different format.']]);
    }

    $rawData = [];
    foreach ($parseLine as $key => $value) {
        $rawData[$key] = json_decode($value);

        // Add index phonetics words
        $rawData[$key]->index = metaphone($rawData[$key]->title) . metaphone($rawData[$key]->content);
    }
    
    $hosts = [env('ELASTIC_URL')];
    $client = ClientBuilder::create()           // Instantiate a new ClientBuilder
                        ->setHosts($hosts)      // Set the hosts
                        ->build();              // Build the client obje

    $params = [
        'index' => 'data',
        'id'    => 'data-id',
        'body'  => $rawData
    ];
    
    $response = $client->index($params);
})->purpose('Create elastic data from source file');
