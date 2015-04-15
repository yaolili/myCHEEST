<?php

class Similarityscore_model extends CI_Model 
{

	#TO DO
	#require_once "PorterStemmer_model.php";
	#$this->load->model('porterstemmer_model');
	#$CI = &get_instance();


	function __construct()
	{
		parent::__construct();
	}

	# some pre process & return stemmer result
	public function pre_process($doc,$stopwords)
	{       
		$no_punctuation_result = preg_replace("/[[:punct:]\s]/",' ', strtolower($doc));
		$explode_result = explode(" ",$no_punctuation_result);
		$filter_result = array_filter($explode_result);
		$remove_result = array_diff($filter_result, $stopwords);
		foreach($remove_result as $temp)
		{
			$stem_result[] = Porterstemmer_model::Stem($temp);
		}

		return $stem_result;
	}


	#return the bag of words 
	public function words_bag($doc1, $doc2)
	{
		$dictionary = array();

		foreach($doc1 as $temp)
		{
			if(!in_array($temp, $dictionary)) $dictionary[] = $temp;
		}
		foreach($doc2 as $temp)
		{
			if(!in_array($temp, $dictionary)) $dictionary[] = $temp;
		}

		return $dictionary;

	}

	public function vector_space($doc,$dictionary)
	{
		$vector = array();
		foreach($dictionary as $temp)
		{
			$vector[$temp] = 0;
		}

		foreach($doc as $temp)
		{
			if(in_array($temp,$dictionary)) $vector[$temp]++;
		}

		return $vector;
	}

	public function score($doc1,$doc2, $stopwords)
	{
		$pre_doc1 = $this->pre_process($doc1, $stopwords);
		$pre_doc2 = $this->pre_process($doc2, $stopwords);
		$bags = $this->words_bag($pre_doc1, $pre_doc2);
		$vector1 = $this->vector_space($pre_doc1, $bags);
		$vector2 = $this->vector_space($pre_doc2, $bags);

		$numerator = 0;
		$length1 = 0;
		$length2 = 0;
		foreach($bags as $temp)
		{
			$numerator += $vector1[$temp] * $vector2[$temp];
			$length1 += pow($vector1[$temp], 2);
			$length2 += pow($vector2[$temp], 2);
		}

		$similar_score = $numerator / (sqrt($length1) * sqrt($length2));
		return $similar_score;
	}
}
