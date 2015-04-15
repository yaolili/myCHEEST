<?php

/**
 * star_clustering
 * 
 * 
 */
 
 class Cluster_model extends CI_Model
 {
	private $segma = 0.65;
	
	function __construct()
    {
        parent::__construct();
    }

	public function original_graph($document_number, $score)
	{
		$graph = array();	
		for( $i = 0; $i < $document_number; $i++) $graph[$i] = array();

		for ($i = 0; $i < $document_number; $i++)
		{
			for ($j = $i+1; $j < $document_number; $j++)
			{
				if($score["($i,$j)"] >= $this->segma)
				{
					$graph[$i][] = $j;
					$graph[$j][] = $i;

				}
			}
		}
		return $graph;
	}
	
	public function max_degree($graph, $document_number)
	{
		$max_degree_id = -1;
		$max_degree_count = 0;
		for($i = 0; $i< $document_number; $i++)
		{
			if(array_key_exists($i,$graph) && (count($graph[$i]) >= $max_degree_count))
			{
				$max_degree_count = count($graph[$i]);
				$max_degree_id = $i;
			}
		}
		return $max_degree_id;
	}
	
	public function clustering(&$graph, $document_id, &$clustering_array)
	{
		$graph[$document_id][] = $document_id;
		
		#deep copy of $graph[$document_id]
		foreach($graph[$document_id] as $temp)
		{
			$want_clustered_list[] = $temp;
		}

		$graph_key = array_keys($graph);
		while(list($graph_key_position, $graph_key_value) = each($graph_key))
		{
			$connected_id = $graph[$document_id];
			# if I directly use in this way: each($graph[$document_id]), something will be wrong!
			while(list($key, $value) = each($connected_id))
			{
				$offset = array_search($value, $graph[$graph_key_value]); 
				if( in_array($value, $graph[$graph_key_value]) && ($graph_key_value != $document_id))
				{
					#echo "pre to unset, graph[$graph_key_value]:";echo $graph[$graph_key_value][$offset];
					unset($graph[$graph_key_value][$offset]);
					
				}
			}
		}

		$graph[$document_id] = array_reverse($graph[$document_id]);
		$clustering_array[] = $graph[$document_id];
		foreach($want_clustered_list as $temp)
		{
			unset($graph[$temp]);
		}
		return 1;
	}
	
	public function is_all_marked($clustering_array, $graph_length)
	{
		$marked_number = 0;
		for($i = 0; $i < count($clustering_array); $i++)
		{
			$marked_number += count($clustering_array[$i]);
		}
		if($marked_number == $graph_length) return true;
		else return false;
	}
		
	public function star_clustering_result($document_number,$score)
	{
		$clustering_result = array();
		$graph = $this->original_graph($document_number,$score);
		#echo "original_graph is :";print_r ($graph);
		$graph_length = count($graph);
		while(!$this->is_all_marked($clustering_result,$graph_length))
		{
			$Id = $this->max_degree($graph,$document_number);
			#echo "max_degree_id is: $Id";
			$this->clustering($graph, $Id, $clustering_result);
			#echo "current graph is :";print_r ($graph);
		}
		return $clustering_result;
	}	
}
	
	
	
	
	

	
	