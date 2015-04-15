<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {
	
    public function search($query, $getfield = null)
    {
        $this->load->model('Twitter_model', 'twitter');
        $settings = array(
            'oauth_access_token' => "1289235073-we4Dll98qSLXNsiU4sc7ELifQMk6M4qgRWEKK0F",
            'oauth_access_token_secret' => "5UIJd7wIzPB9sz35qgt7IjPTRqlwKwTqItjIJ1WIM",
            'consumer_key' => "n6bkCCpuxQO69V7pczZaCA",
            'consumer_secret' => "kzYNoSPZdaxNUpsH7PA47Ai1kEutciJJxFXUx6Bc"
        );
        $url = 'https://api.twitter.com/1.1/search/tweets.json';
        if (!$getfield) {
            //$getfield = "?q=$query";
			$getfield = "?q=$query&lang=zh-cn";
        } else {
            $getfield = urldecode($getfield);
        }
        $requestMethod = 'GET';
        $response = $this->twitter->init($settings)
            ->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();	
        #var_dump( $response);

        $temp = json_decode($response);
        #var_dump($temp->statuses);
        $search_array = $temp->statuses;
        if ($search_array == null) {
            $full_result['statuses'] = null; 
            $result_json = json_encode($full_result);
            echo $result_json;	
            return;
        }

        $full_result['search_metadata'] = $temp->search_metadata;

        #read the stopwords from file 
        $content = file('data/stopword');
        foreach ($content  as $temp)
        {
            $stopwords[] = trim($temp);
        }

        $score = array();
        $number = count($search_array);
        $this->load->model('porterstemmer_model');
        $this->load->model('similarityscore_model');
        for($i = 0; $i < $number; $i++)
        {
            for($j = $i+1; $j < $number; $j++)
            {
                $score["($i,$j)"] = $this->similarityscore_model->score($search_array[$i]->text,$search_array[$j]->text,$stopwords);
            }
        }
        $this->load->model('cluster_model');		
        $clustering_result = $this->cluster_model->star_clustering_result($number, $score);
        #print_r($clustering_result);

        for($i = 0; $i < count($clustering_result); $i++)
        {
            #$center_id is the center id number in $search_array
            $center_id = $clustering_result[$i][0];
            $result[$i]['center'] = $search_array[$center_id];

            if (count($clustering_result[$i]) > 1) $result[$i]['center']->has_children = 1;
            else $result[$i]['center']->has_children = 0;


            for($j = 1; $j < count($clustering_result[$i]); $j++)
            {
                #$children_id is the children id number in $search_array
                $children_id = $clustering_result[$i][$j];
                $result[$i]['children'][] = $search_array[$children_id];
            }

        }
			
		//uasort() can't work, remain TODO
		//so use bubble sort
		for($i = 0; $i < count($result); $i++)
		{
			for($j = $i + 1; $j < count($result); $j++)
			{
				if(strtotime($result[$i]["center"]->created_at) < strtotime($result[$j]["center"]->created_at))
				{
					$tmp = $result[$i];
					$result[$i] = $result[$j];
					$result[$j] = $tmp;
				}
			}
		}

        $full_result['statuses'] = $result;
        $result_json = json_encode($full_result);
        echo $result_json;	

    }

    public function user($query)
    {
        $this->load->model('Twitter_model', 'twitter');
        $settings = array(
            'oauth_access_token' => "350033240-B2ixMMHiaBND7rVmjQecD3YvgZjbHzjpTcwWDPBp",
            'oauth_access_token_secret' => "JQzLpg3YFSHHOFyIbmM4dzl73jzCXWWcdHLtjZGG99k",
            'consumer_key' => "zFs5cu1pKaD8nwZeMdrA",
            'consumer_secret' => "5AOdK204ZZ14wby0NMg29YFE48Me9Zwkf81OpJ1xQ"
        );
        $url = 'https://api.twitter.com/1.1/users/search.json';
        $getfield = "?q=$query";
        $requestMethod = 'GET';
        $response = $this->twitter->init($settings)
            ->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();
        echo $response;
    }
	
	public function nerTagger()
	{
		header("Content-Type: text/json;charset=utf-8");
		$query = urldecode($this->input->get_post("q"));
		$source = $this->input->get_post("source");
		$this->load->model('Nerprocess');
		
		if($source == "news")
		{
			$query = urlencode($query);
			$url = "https://ajax.googleapis.com/ajax/services/search/news?v=1.0&q=$query"."&rsz=8&ned=cn";
			$news = json_decode($this->Nerprocess->curlGetInfo($url));
			$data = $news->responseData->results;
			$sentence = "";
			for($i = 0; $i < count($data); $i++)
			{
				$sentence .= $data[$i]->content . " endend " ;
				$title[] = $data[$i]->title;
			}
			
			//do some preProcess
			$sentence = $this->Nerprocess->preProcess($sentence);
			 
			//use scws to segment
			$sentence = $this->Nerprocess->scwsSeg($sentence);
			//echo $sentence;
			$result = $this->Nerprocess->nerTagger($sentence);
			//echo $result;
			$entity = $this->Nerprocess->pregMatch(1, $result);
			//var_dump($entity);
			//it's important to reindex
			$entity = array_values($entity);
			$entity = $this->Nerprocess->noDuplicated($query, $entity, 1);
			//var_dump($entity);
 			for($i = 0; $i < count($entity); $i++)
			{
				$entity[$i]["time"] = $data[$i]->publishedDate;
				$entity[$i]["title"] = $title[$i];
				$entity[$i]["content"] = $data[$i]->content;
				
			} 
			$entity = $this->Nerprocess->noEventInfo($entity);
		}
		else
		{
			//$source = twitter			
			$this->load->model('Twitter_model', 'twitter');
			$query = urlencode($query);
			$settings = array(
				'oauth_access_token' => "1289235073-we4Dll98qSLXNsiU4sc7ELifQMk6M4qgRWEKK0F",
				'oauth_access_token_secret' => "5UIJd7wIzPB9sz35qgt7IjPTRqlwKwTqItjIJ1WIM",
				'consumer_key' => "n6bkCCpuxQO69V7pczZaCA",
				'consumer_secret' => "kzYNoSPZdaxNUpsH7PA47Ai1kEutciJJxFXUx6Bc"
			);
			$url = 'https://api.twitter.com/1.1/search/tweets.json';
			$getfield = "?q=$query&lang=zh-cn";			
			$requestMethod = 'GET';
			$response = $this->twitter->init($settings)
				->setGetfield($getfield)
				->buildOauth($url, $requestMethod)
				->performRequest();	
			$response = json_decode($response);
			$statuses = $response->statuses;
			$text = "";
			for($i = 0; $i < count($statuses); $i++)
			{
				$text .= $statuses[$i]->text;
			}
			//echo $text;
			$text = $this->Nerprocess->preProcess($text);
			$text = $this->Nerprocess->scwsSeg($text);
			$result = $this->Nerprocess->nerTagger($text);
			//echo $result;
			$entity = $this->Nerprocess->pregMatch(0, $result);
			$entity[0] = $entity;
			//var_dump($entity);
			
			$entity = $this->Nerprocess->noDuplicated($query, $entity, 0);
		}
		//var_dump($entity);
		echo $_GET['callback'] . "(" . json_encode($entity) . ")";
		
						
		
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
