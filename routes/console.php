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

Artisan::command('reindex_content', function () {
    echo ' starting ';
     \App\ElasticJobs\ContentIndexing\ReindexingJob::reindexContent();
     echo ' finished ';
     
})->describe('Reindex the content');

Artisan::command('reindex_user', function () {
    echo ' starting ';
	 \App\ElasticJobs\UserIndexing\ReindexingJob::reindexContent();
     echo ' finished ';
})->describe('Reindex the user');
