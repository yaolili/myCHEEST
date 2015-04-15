<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Search extends CI_Controller {

    public function index()
    {
        $data['query'] = $this->input->get_post("q");
        $query = urlencode($data['query']);		
        //echo $query;
		
        $this->load->model('Twitter_model', 'twitter');		
        $settings = array(
            'oauth_access_token' => "1289235073-we4Dll98qSLXNsiU4sc7ELifQMk6M4qgRWEKK0F",
            'oauth_access_token_secret' => "5UIJd7wIzPB9sz35qgt7IjPTRqlwKwTqItjIJ1WIM",
            'consumer_key' => "n6bkCCpuxQO69V7pczZaCA",
            'consumer_secret' => "kzYNoSPZdaxNUpsH7PA47Ai1kEutciJJxFXUx6Bc"
        );
        $url = 'https://api.twitter.com/1.1/users/search.json';
        //$getfield = "?q=$query";
		$getfield = "?q=$query&lang=zh-cn";
        $requestMethod = 'GET';

        $response = $this->twitter->init($settings)
            ->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest();

		$user_array = json_decode($response);
		$data['user'] = null;
		if ($response != null && $user_array != null) {	
				$data['user'] = $user_array[0];
			$data['user']->profile_image_url = str_replace("normal", "400x400", $data['user']->profile_image_url);
		}
		
        $this->load->view('inc/header', $data);
        $this->load->view('query');
        $this->load->view('inc/footer');
    }
}

/* End of file Search.php */
/* Location: ./application/controllers/Search.php */
