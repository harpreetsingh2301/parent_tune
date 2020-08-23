<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;





class FeedController extends Controller
{

    private $user_alias ;
    private $content_alias ;


    
     function __construct() {
       $this->user_alias = config('elastic.user_alias');
       $this->content_alias = config('elastic.content_alias');

    }

    public function index(Request $request)
    {


      $postfeed_config = collect(config('postfeed'));

      // var_dump($postfeed_config);
      // exit;
       // $postfeed_config->get('boost.topic',100);

      $user_id  = $request->input('user_id',0);

      
      $age_group_id_prefrence = [];
      $topic_id_prefrence = [];
      $page_visits_data = [];
      $page_views = [];

      $client = ClientBuilder::create()->setHosts([['host' =>'localhost','port' => '9200','scheme'=>'http']])->build();

      if($user_id!=0){

        $params = [
          'index' => $this->user_alias,
          'id'    => $user_id
          ];

          try {
              $response = $client->get($params);
              $age_group_id_prefrence = $response['_source']['age_group_id']??[];
              $topic_id_prefrence = $response['_source']['topic_id']??[];
              $page_visits_data = $response['_source']['page_visits']??[];
              $page_views = $response['_source']['page_views']??[];
          } catch (\Exception $e) {
            
          }
      }

      $query=["function_score"=>[
            "boost"=> "5",
            "score_mode"=> "sum", 
            "boost_mode"=>"multiply",
            "functions"=>[],

      ]];

      if(count($age_group_id_prefrence)>0){
          $age_group_id_terms=[];
          foreach ($age_group_id_prefrence as $age_group_id) {
            $age_group_id_terms =['term'=>['age_group_id'=>$age_group_id] ];
          }
         $query['function_score']['query']['bool']['must'][] = ['bool'=>['should'=>$age_group_id_terms,'minimum_should_match'=>1,'boost'=>1] ];

      }else
      {
        $query['function_score']['query'] =['match_all'=>(object)[] ];
      }

      if(count($page_views)>0){
         $query['function_score']['functions'][] = [
                                                  "weight"=> $postfeed_config->get('boost.not_viewed',40),
                                                  "filter"=> [
                                                    "bool"=>[
                                                      
                                                          "must_not"=> [ ["terms"=>[ "_id" => $page_views ]] ], 
                                                        
                                                      ]
                                                    ]
                                                ];

      }
    
      // add subscribed topics boost
      foreach ($topic_id_prefrence as $topic_id) {
        $query['function_score']['functions'][] = [
                                                  "weight"=> $postfeed_config->get('boost.topic',100),
                                                  "filter"=> [
                                                    "match"=> [
                                                        "topic_id"=> $topic_id, 
                                                      ]
                                                    ]
                                                ];
      }

      // add view topics boost
      foreach ($page_visits_data as $view_data) {
        // return $view_data;
        $query['function_score']['functions'][] = [
                                                  "weight"=> $postfeed_config->get('boost.page_view',10)*$view_data['view_count'],
                                                  "filter"=> [
                                                    "match"=> [
                                                        "topic_id"=> $view_data['topic_id'], 
                                                      ]
                                                    ]
                                                ];
      }


       // Add date decay 
      $query['function_score']['functions'][] = [
                                                  "weight"=> $postfeed_config->get('boost.date.weight',10),
                                                  "exp"=> [
                                                    "cdate"=> [
                                                        "origin"=> strtotime('now'), 
                                                        "scale"=> "1d",      
                                                        "decay"=> $postfeed_config->get('boost.date.decay',0.09)
                                                      ]
                                                    ]
                                                ];

      $params = [
          'index' => $this->content_alias,
          'body'  => [
              'query' => $query
          ]
      ];


     
      

      return $query;



      $results = $client->search($params);

      return $results;

    }
    
}