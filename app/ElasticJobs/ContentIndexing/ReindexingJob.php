<?php

namespace App\ElasticJobs\ContentIndexing;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;


class ReindexingJob {

   
    private static $index_prefix = 'content_index';
   
    private static $settings = [ 
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
            ];
    private static $mappings = [ 
                    'properties' => [
                        'content_id' => [
                            'type' => 'integer'
                        ],
                        'age_group_id' => [
                            'type' => 'integer'
                        ],
                        'topic_id' => [
                            'type' => 'integer'
                        ],
                        'cdate' => [
                            'type' => 'date'
                        ]
                    ]
                ];

	

   


    public static function reindexContent(){


        
        $client = ClientBuilder::create()->setHosts([['host' =>config('elastic.host'),'port' => config('elastic.port'),'scheme'=>config('elastic.scheme')]])->build();
         
        $alias_name = config('elastic.content_alias');

        $prev_index ='';
        // Get index previndex name
        try {
            $previous_index_response = $client->indices()->getAliases(['name'=>$alias_name]);
            $previous_index = array_key_first($previous_index_response);


        } catch (\Exception $e) {
            echo "prev index not found \n";
            echo $e->getMessage();
            exit;
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


    


    

            $content_feed  = DB::table('content_date')->select(['content_date.item_id','content_date.cdate',DB::raw('GROUP_CONCAT( distinct content_age_groups.age_group_id) as age_group_id'),DB::raw('GROUP_CONCAT(distinct content_topics.topic_id) as topic_id')])
                        ->join('content_age_groups', function ($join) {
                                $join->on('content_date.item_id', '=', 'content_age_groups.item_id');
                            })
                        ->join('content_topics', function ($join) {
                                $join->on('content_date.item_id', '=', 'content_topics.item_id');
                            })->groupBy('content_date.item_id')->groupBy('content_date.cdate')->get();


            foreach ($content_feed as $content) {
                    
                $content->age_group_id = explode(',', $content->age_group_id);
                $content->topic_id = explode(',', $content->topic_id);
                $content->date  = $content->cdate ;
                $content->cdate = strtotime($content->cdate);
                $params = [

                    'index' => $index_name,
                    'type' => '_doc',
                    'id'    => $content->item_id,
                    'body'  => $content
                ];

                try {
                    


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
                                                        'alias' => $alias_name
                                                    ]
                                                ],
                                                [
                                                    'remove' => [
                                                        'index' => $previous_index,
                                                        'alias' => $alias_name
                                                    ]
                                                ]
                                            ]
                                        ]
                ];

                
                $client->indices()->updateAliases($index_params);
                
            } catch (Exception $e) {
                echo $e->getMessage();
            }

                

            try {

                $params = ['index' => $previous_index];
                 $client->indices()->delete($params);
                    
                } catch (Exception $e) {
                    echo " prev index deletion failed";
                    
                }




    }

}

