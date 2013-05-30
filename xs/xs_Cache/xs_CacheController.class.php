<?php

	class CacheController {

		private $cache  = array() ;
		private $status = array() ;
		private $obj    = array() ;
		private $stack  = array() ;
		private $clock  = array() ;
		private $result = array() ;
		
		private $c ;
			
			
				
		function __construct ( $converter = null ) {
			$this->c = new xs_Profiler() ;
		}
		
		function add ( $cache, $obj, $stack = null, $clock = null ) {
			
			$id = $cache->getID() ;
			
			$this->cache[$id] = $cache ;
			$this->obj[$id]   = $obj ;
			
			$this->stack[$id] = $stack ;
			$this->clock[$id]   = $clock ;
			
			$this->c->add("Added [".$id."]") ;
			
		}
		
		function getContent ( $id ) {
			if ( isset ( $this->result[$id] ) )
				return $this->result[$id] ;
			else
				return null ;
		}
		
		function getStatus ( $id ) {
			if ( isset ( $this->status[$id] ) )
				return $this->status[$id] ;
			else
				return null ;
		}
		
		function debug () {
			
			return $this->obj ;
			
		}
		
		function control() {
			
			foreach ( $this->cache as $idx=>$val ) {
				
				// $this->result = "" ;
				$cached = $val->cached() ;
				$cl = "true" ;
				
				if ( $cached == false ) 
					$test_action = $this->obj[$idx]->action() ;
				
				// echo "[".$this->obj[$idx]->getStatus()."] " ;
				
				if ( $cached == false || $this->obj[$idx]->getStatus() != '200' ) {
					
					$this->status[$idx] = $test_action ;
					$this->result[$idx] = $this->obj[$idx]->result() ;
					
					$val->writeCache ( $this->result[$idx] ) ;
					$cl = "false" ;
				} else {
					$this->result[$idx] = $val->readCache () ;
				} 
				
				$this->c->add("Processed '".$idx."', cached-content=[".$cl."]") ;
				
				if ( $this->stack[$idx] != null )
					$this->stack[$idx]->add ( $this->obj[$idx]->getID(), $this->result[$idx] ) ;

				if ( $this->clock[$idx] != null )
					$this->clock[$idx]->add( $this->obj[$idx]->getID()." : ". $val->status() ) ;

			}
			
			$this->c->add("Finished." ) ;
		}
		
		function dump() {
			return $this->c->report() ;
		}

	}
	