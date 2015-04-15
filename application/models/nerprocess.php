<?php

class Nerprocess extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function curlGetInfo($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, 
			'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($ch === FALSE){
			throw new Exception('cURL not supported');             
		}

		$content = curl_exec($ch);
		if ($content === FALSE) {
			throw new Exception('cURL error: '.curl_error($ch));
		}
		curl_close($ch);
		return $content;
	}
	
	public function preProcess($text)
	{
		//remove html tags;
		$sentence = strip_tags($text);
		
		//remove English & Chinese punct
		$sentence = preg_replace("/[[:punct:]\s]/",' ',$sentence);
		
		//notice: cannot break into several lines, keep it in one line!
		$sentence = urlencode($sentence);
		$sentence = preg_replace("/(%EF%BC%8C|%E3%80%82|%E2%80%9D|%E2%80%9C|%EF%BC%9B|%E3%80%90|%E3%80%91|%EF%BC%9F|%E3%80%8A|%E3%80%8B|%EF%BC%88|%EF%BC%89|%E3%80%81)/",' ',$sentence);
		$sentence = urldecode($sentence);
		return $sentence;
	}
	
	public function scwsSeg($text)
	{
		
		$so = scws_new();
		$so->set_charset('utf8');
		$so->send_text($text);
		$sentence = "";
		while ($segmentation = $so->get_result())
		{
			for($i = 0; $i < count($segmentation); $i++)
			{
				$sentence .= " ".$segmentation[$i]["word"];		
			}
		}
		$so->close();
		return $sentence;
	}
	
	public function voiceCloud($text)
	{
		//$text = utf8_encode($text);
		$uri = "http://ltpapi.voicecloud.cn/analysis/?";
		$apikey = "p3k379Q42i0LgfoUojEKQpZfnPhMEbdSFz6zGkQb";
		$pattern = "all";
		$format = "json";
		
		$url = ($uri 
				. "api_key=" . $apikey . "&"
				. "text=" . $text . "&"
				. "pattern=" . $pattern . "&"
				. "format=" . $format);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		// grab URL and pass it to the browser
		$response = curl_exec($ch);
		echo $response;
		curl_close($ch);
		
	}
	
	public function nerTagger($text)
	{
		$text = urlencode($text);
		$data = array(
					"classifier=chinese.misc.distsim.crf.ser.gz",
					"outputFormat=inlineXML",
					"preserveSpacing=yes",
					"input=$text",
				);
	    $data = implode('&',$data);
		//var_dump($data);
		$url='http://nlp.stanford.edu:8080/ner/process';  
  
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_POST, 1);  
		curl_setopt($ch, CURLOPT_URL,$url);  
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
		ob_start();  
		curl_exec($ch);  
		$result = ob_get_contents() ;  
		ob_end_clean();  
		//echo $result;
		return $result;  
	}

	public function pregMatch($type, $result)
	{
		if($type == 0)
		{
			//twitter
			
			preg_match_all("/\&lt;LOC\&gt;(.*?)\&lt;\/LOC/", $result, $location, PREG_PATTERN_ORDER);
			preg_match_all("/\&lt;PERSON\&gt;(.*?)\&lt;\/PERSON/", $result, $person, PREG_PATTERN_ORDER);
			preg_match_all("/\&lt;ORG\&gt;(.*?)\&lt;\/ORG/", $result, $organization, PREG_PATTERN_ORDER);
			preg_match_all("/\&lt;GPE\&gt;(.*?)\&lt;\/GPE/", $result, $gpe, PREG_PATTERN_ORDER);
			//preg_match_all("/\&lt;MISC\&gt;(.*?)\&lt;\/MISC/", $result, $matches, PREG_PATTERN_ORDER);

			if($location[1]) $entity["location"] = $location[1];
			if($person[1]) $entity["person"] = $person[1];
			if($organization[1]) $entity["organization"] = $organization[1];
			if($gpe[1]) $entity["gpe"] = $gpe[1];
		}
		else
		{
			//news
			$array = explode("endend",$result);
			for($i = 0; $i < count($array); $i++)
			{
				preg_match_all("/\&lt;LOC\&gt;(.*?)\&lt;\/LOC/", $array[$i], $location, PREG_PATTERN_ORDER);
				preg_match_all("/\&lt;PERSON\&gt;(.*?)\&lt;\/PERSON/", $array[$i], $person, PREG_PATTERN_ORDER);
				preg_match_all("/\&lt;ORG\&gt;(.*?)\&lt;\/ORG/", $array[$i], $organization, PREG_PATTERN_ORDER);
				preg_match_all("/\&lt;GPE\&gt;(.*?)\&lt;\/GPE/", $array[$i], $gpe, PREG_PATTERN_ORDER);
				
				if($location[1]) $entity[$i]["location"] = $location[1];
				if($person[1]) $entity[$i]["person"] = $person[1];
				if($organization[1]) $entity[$i]["organization"] = $organization[1];
				if($gpe[1]) $entity[$i]["gpe"] = $gpe[1];
			}
			
		}
		return $entity;
	}
	
	
	public function noDuplicated($query, $entity, $type)
	{
		//var_dump($entity);
		$noDuplicatedEntity = array();
		$currentEntity = array();
		
		for($i = 0; $i < count($entity); $i++)
		{
			//The entity array doesn't necessary have every index(0-7)
			if (array_key_exists($i,$entity))
			{
				foreach(array_keys($entity[$i]) as $property)
				{
					
					if($type == 1) $currentEntity = array();
					for($j = 0; $j < count($entity[$i][$property]); $j++)
					{								
						
						//var_dump($noDuplicatedEntity);
						if(($entity[$i][$property][$j] != $query) && (!in_array($entity[$i][$property][$j],$currentEntity)))
						{
							if($type == 0)
							{
								$noDuplicatedEntity[$i][$property][$entity[$i][$property][$j]] = 1;
							}
							else $noDuplicatedEntity[$i][$property][] = $entity[$i][$property][$j];					
							$currentEntity[] = $entity[$i][$property][$j];
						}
						else if(in_array($entity[$i][$property][$j],$currentEntity))
						{
							if($type == 0)
							{
								//same phrase sometimes belongs to diff property
							
								if(array_key_exists($property, $noDuplicatedEntity[$i])
								   && array_key_exists($entity[$i][$property][$j], $noDuplicatedEntity[$i][$property]))
								$noDuplicatedEntity[$i][$property][$entity[$i][$property][$j]]++;
							}
						}
						
					}
				}
			}		
		}
		return $noDuplicatedEntity;
	}
	
	//$entity is: in current tag type, no duplicated, with title & content
	public function noEventInfo($entity)
	{
		$noEventEntity = array();
		for($i = 0; $i < count($entity); $i ++)
		{
			foreach(array_keys($entity[$i]) as $property)
			{
				if(($property != "title") && ($property != "content") && ($property != "time"))
				{
					for($j = 0; $j < count($entity[$i][$property]); $j++)
					{
						$noEventEntity[$property][$entity[$i][$property][$j]][] = $entity[$i]["time"];
						$noEventEntity[$property][$entity[$i][$property][$j]][] = $entity[$i]["title"];
						$noEventEntity[$property][$entity[$i][$property][$j]][] = $entity[$i]["content"];
					}
				}
			}
		}
		//return $noEventEntity;
		//bubble resort depend on news time
		foreach(array_keys($noEventEntity) as $property)
		{
			foreach(array_keys($noEventEntity[$property]) as $key)
			{
				$length = count($noEventEntity[$property][$key]);			 
				for($i = 0; $i < $length; $i += 3)
				{
					for($j = $i + 3; $j < $length; $j += 3)
					{
						if( strtotime($noEventEntity[$property][$key][$i])
						  < strtotime($noEventEntity[$property][$key][$j]) )
						{
							$tmpTime = $noEventEntity[$property][$key][$i];
							$tmpTitle = $noEventEntity[$property][$key][$i+1];
							$tmpContent = $noEventEntity[$property][$key][$i+2];
							
							$noEventEntity[$property][$key][$i] = $noEventEntity[$property][$key][$j];
							$noEventEntity[$property][$key][$i+1] = $noEventEntity[$property][$key][$j+1];
							$noEventEntity[$property][$key][$i+2] = $noEventEntity[$property][$key][$j+2];
							
							$noEventEntity[$property][$key][$j] = $tmpTime;
							$noEventEntity[$property][$key][$j+1] = $tmpTitle;
							$noEventEntity[$property][$key][$j+2] = $tmpContent;
						}
					}
				}
				if($length > 9)
				{
					$noEventEntity[$property][$key] = array_slice($noEventEntity[$property][$key],0,9);
				}
			}		
		}			
		return $noEventEntity;
	}

	public function noSingleWord($entity)
	{
		foreach(array_keys($entity) as $property)
		{
			foreach(array_keys($entity[$property]) as $currentEntity)
			{
				if(strlen($currentEntity) == 3) unset($entity[$property][$currentEntity]);
			}
		}
		return $entity;
	}
	
}