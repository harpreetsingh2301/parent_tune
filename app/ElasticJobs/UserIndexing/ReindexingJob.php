<?php

namespace App\ElasticJobs\UserIndexing;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;


class ReindexingJob {

    private static $alias_name = 'user_alias';
    private static $index_prefix = 'user_index';
   
    private static $settings = [ 
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
            ];
    private static $mappings = [ 
                    'properties' => [
                        'user_id' => [
                            'type' => 'integer'
                        ],
                        'age_group_id' => [
                            'type' => 'integer'
                        ],
                        'topic_id' => [
                            'type' => 'integer'
                        ]
                    ]
                ];

	

   


    public static function reindexContent(){


        $client = ClientBuilder::create()->setHosts([['host' =>config('elastic.host'),'port' => config('elastic.port'),'scheme'=>config('elastic.scheme')]])->build();


       

        $previous_index ='';
        // Get index previndex name
        try {
            $previous_index_response = $client->indices()->getAliases(['name'=>self::$alias_name]);
            $previous_index = array_key_first($previous_index_response);


        } catch (\Exception $e) {
            echo "prev index not found \n";
            // echo $e->getMessage();
            // exit;
        }


        

        $index_name =  self::$index_prefix.date('-Y-m-d-H-i-s');
         try {

            $create_params =[
            'index' => $index_name,
            'body' => [
                'settings' => self::$settings,
               'mappings' => self::$mappings
                ]
            ];
            $response = $client->indices()->create($create_params);


        } catch (\Exception $e) {
            echo "index creation failed \n";
            echo $e->getMessage();
            exit;
        }   


    


        echo 'indexing user data';

            $user_feed  = DB::table('user')->select(['user.user_id',DB::raw('GROUP_CONCAT( distinct user_age_groups.age_group_id) as age_group_id'),DB::raw('GROUP_CONCAT(distinct user_topics.topic_id) as topic_id')])
                        ->leftJoin('user_age_groups', function ($join) {
                                $join->on('user_age_groups.user_id', '=', 'user.user_id');
                            })
                        ->leftJoin('user_topics', function ($join) {
                                $join->on('user_topics.user_id', '=', 'user.user_id');
                            })->groupBy('user.user_id')->get()->keyBy('user_id');


            $user_visits  = DB::table('page_visits')->select(['page_visits.user_id','content_topics.topic_id',DB::raw('GROUP_CONCAT( distinct page_visits.item_id) as item_ids'),DB::raw('count(*) as view_count') ])
                        ->join('content_topics', function ($join) {
                                $join->on('content_topics.item_id', '=', 'page_visits.item_id');
                            })->groupBy('page_visits.user_id')->groupBy('content_topics.topic_id')->get();
            
            foreach ($user_visits as $row) {

                if(isset($user_feed[$row->user_id]) && !property_exists($user_feed[$row->user_id], 'page_visits') ){
                    $user_feed[$row->user_id]->page_visits =[]; 
                }
                if(isset($user_feed[$row->user_id]) && !property_exists($user_feed[$row->user_id], 'page_views') ){
                    $user_feed[$row->user_id]->page_views =[]; 
                }

                $user_feed[$row->user_id]->page_views = array_merge($user_feed[$row->user_id]->page_views,explode(',', $row->item_ids));
                $user_feed[$row->user_id]->page_visits[]=['topic_id'=>$row->topic_id,'view_count'=>$row->view_count ];
               
            }
           

            foreach ($user_feed as $user) {
             try {
                $user->age_group_id = is_null($user->age_group_id)?[]:explode(',', $user->age_group_id); 
                $user->topic_id = is_null($user->topic_id)?[]:explode(',', $user->topic_id);
                if(!property_exists($user, 'page_visits')){
                    $user->page_visits = [];
                }
                if(!property_exists($user, 'page_views')){
                    $user->page_views = [];
                }
               
                $params = [

                    'index' => $index_name,
                    'type' => '_doc',
                    'id'    => $user->user_id,
                    'body'  => $user
                ];


                $response = $client->index($params);
               
                
                } catch (\Exception $e) {
                    echo "content indexing failed \n" ;
                    exit;           
                }
            }

            try {


                $index_params = ['body'=>
                                        [
                                            'actions' => [
                                                [
                                                    'add' => [
                                                        'index' => $index_name,
                                                        'alias' => self::$alias_name
                                                    ]
                                                ]
                                            ]
                                        ]
                ];


                if($previous_index!=''){
                    $index_params['body']['actions'][]=[
                                                    'remove' => [
                                                        'index' => $previous_index,
                                                        'alias' => self::$alias_name
                                                    ]
                                                ];
                }

                
                $client->indices()->updateAliases($index_params);
                
            } catch (Exception $e) {
                echo $e->getMessage();
            }

                

            try {
                if($previous_index!=''){
                    $params = ['index' => $previous_index];
                    $client->indices()->delete($params);
                 }
                    
                } catch (Exception $e) {
                    echo " prev index deletion failed";
                    
                }




    }

}

