<?php

use Illuminate\Support\Facades\Route;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {


	return \App\ElasticJobs\ContentIndexing\ReindexingJob::reindexContent();
	return \App\ElasticJobs\UserIndexing\ReindexingJob::reindexContent();

// 	$client = ClientBuilder::create()->setHosts([['host' =>'localhost','port' => '9200','scheme'=>'http']])->build();

// 	$prev_index ='';
// 	// Get index previndex name
// 	try {
// 		$previous_index_response = $client->indices()->getAliases(['name'=>'content_alias']);
// 		$previous_index = array_key_first($previous_index_response);


// 	} catch (Exception $e) {
// 		echo 'prev index not found';
// 		echo $e->getMessage();
// 		exit;
// 	}

// 	echo $previous_index;
	

// 	$content_index = 'content_index';
// 	try {

// 	$params = ['index' => $content_index];
// 	$previous_index_response = $client->indices()->delete($params);
		
// 	} catch (Exception $e) {
// 		// throw $e;
		
// 	}

	 

	

// 	$create_params =[
//     'index' => $content_index,
//     'body' => [
//         'settings' => [ 
//             'number_of_shards' => 1,
//             'number_of_replicas' => 0,
//         	],
//        'mappings' => [ 
// 		            'properties' => [
// 		                'content_id' => [
// 		                    'type' => 'integer'
// 		                ],
// 		                'age_group_id' => [
// 		                    'type' => 'integer'
// 		                ],
// 		                'category' => [
// 		                    'type' => 'integer'
// 		                ],
// 		                'topic_id' => [
// 		                    'type' => 'integer'
// 		                ],
// 		                'cdate' => [
// 		                    'type' => 'date'
// 		                ]
// 		            ]
// 				]
// 		]
// 	];
// 	$response = $client->indices()->create($create_params);


// 	$content_feed  = DB::table('content_date')->select(['content_date.item_id','content_date.cdate',DB::raw('GROUP_CONCAT( distinct content_age_groups.age_group_id) as age_group_id'),DB::raw('GROUP_CONCAT(distinct content_topics.topic_id) as topic_id')])
// 				->join('content_age_groups', function ($join) {
// 			            $join->on('content_date.item_id', '=', 'content_age_groups.item_id');
// 			        })
// 				->join('content_topics', function ($join) {
// 			            $join->on('content_date.item_id', '=', 'content_topics.item_id');
// 			        })->groupBy('content_date.item_id')->groupBy('content_date.cdate')->get();


// 	foreach ($content_feed as $content) {
			
// 		$content->age_group_id = explode(',', $content->age_group_id);
// 		$content->topic_id = explode(',', $content->topic_id);
// 		$content->date  = $content->cdate ;
// 		$content->cdate = strtotime($content->cdate);
// 		$params = [

// 		    'index' => $content_index,
// 		    'type' => '_doc',
// 		    'id'    => $content->item_id,
// 		    'body'  => $content
// 		];

// 		try {
			


// 		$response = $client->index($params);

// 		print_r($response);
// 		} catch (\Exception $e) {
// 			// print_r($params);exit;
// 			throw $e;
			
		
			
// 		}
// 	}

	





	
	

    
});
