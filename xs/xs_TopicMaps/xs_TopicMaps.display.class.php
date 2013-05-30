<?php

    class xs_TopicMaps_Display {

        private $tm = null ;
        private $topics = null ;
        private $assocs = null ;

	function __construct ( $tm = null ) {

            // Inject or prepare TM engine
            $this->tm = $tm ;
            $t = $tm->tm ;
            $this->topics = $t[TOPIC] ;
            $this->assocs = $t[ASSOC] ;
            // print_r ( $this->topics ) ;
	}

        function label ( $what ) {
            $label = $what ;
            $l = $what ;
            
            if ( isset ( $this->topics[$what] ) ) {
                // print_r ( $this->topics[$what][NAME_TYPE][0] ) ;
                $l = $this->topics[$what][NAME_TYPE][0] ;
            }
            $label = '<i>'.$l.'</i>' ;
            return $l ;
        }

        function item ( $item, $what, $link = false ) {

            global $HOME ;
            $ret = '' ;
            
            $in = $item["$what"] ;

            if ( isset ( $in ) && count ( $in )  > 1  ) {

               $ret .= "<div class='topic_".$what."s'>".$this->label($what)."(s) [".count($in)."]</div>\n" ;
                foreach ( $in as $idx => $th ) {
                        if ( $link ) $ret .= "<a href='$HOME/$link".$idx."'>" ;
                        $ret .= "<div class='topic_$what'>".$this->label($th)." " ;
                        if ( $idx !== 0 )
                            $ret .= "(".$this->label($idx).")" ;
                        $ret .= "</div>\n" ;
                        if ( $link ) $ret .= "</a>" ;
                }
            }
            return $ret ;
        }

        function out ( $something ) {

            global $HOME ;

            $ret = '' ;

            $ret .= "<style> .topic { padding:2px;margin:2px;border:dotted 1px #ccc; } .topic_names, .topic_occs, .topic_types { font-weight:bold; } </style> \n" ;
            $ret .= "<style> .topic_occ, .topic_name, .topic_type { padding-left:12px; } </style> \n" ;

            foreach ( $something as $idx => $item ) {

                $what = xs_TopicMaps::ASSOC ;
                if ( isset ( $item[ID] ) )
                    $what = xs_TopicMaps::TOPIC ;


                switch ( $what ) {

                    case xs_TopicMaps::TOPIC :

                        $ret .= "<div class='topic'> [<a href='$HOME/topic/$idx'>$idx</a>]\n" ;

                        $ret .= $this->item ( $item, xs_TopicMaps::NAME_TYPE ) ;
                        $ret .= $this->item ( $item, xs_TopicMaps::TOPIC_TYPE, 'topic/' ) ;
                        $ret .= $this->item ( $item, xs_TopicMaps::OCC_TYPE ) ;
                        
                        $ret .= "</div>\n" ;
                        break ;

                    case xs_TopicMaps::ASSOC :

                        $ret .= "<div class='assoc'>\n" ;

                        $ret .= $this->item ( $item, xs_TopicMaps::TOPIC_TYPE, 'topic/' ) ;
                        $ret .= $this->item ( $item, xs_TopicMaps::MEMBER ) ;

                        $ret .= "</div>\n" ;
                        break ;

                    default: break ;
                }
            }
            return $ret ;
        }

        function stats () {

            // Display some statistics about our map

            echo "<div>Total topics: ". count ( $this->tm->get_topics() ) ."</div>" ;
            echo "<div>Total types: ". count ( $this->tm->get_topic_types() ) ."</div>" ;
            echo "<div>Total name types: ". count ( $this->tm->get_name_types() ) ."</div>" ;
            echo "<div>Total occurrence types: ". count ( $this->tm->get_occurrence_types() ) ."</div>" ;
            echo "<div>Total role types: ". count ( $this->tm->get_role_types() ) ."</div>" ;
            echo "<div>Total association types: ". count ( $this->tm->get_association_types() ) ."</div>" ;
            echo "<div>Total associations: ". count ( $this->tm->get_assocs() ) ."</div>" ;

        }

        function cloud ( $tags ) {

            global $HOME ;
            
            $result = '' ;

            $max_size = 42; // max font size in pixels
            $min_size = 10; // min font size in pixels

            // largest and smallest array values
            $max_qty = max(array_values($tags));
            $min_qty = min(array_values($tags));

            // find the range of values
            $spread = $max_qty - $min_qty;
            if ($spread == 0) { // we don't want to divide by zero
                    $spread = 1;
            }

            // set the font-size increment
            $step = ($max_size - $min_size) / ($spread);

            // loop through the tag array
            foreach ($tags as $key => $value) {
                    // calculate font-size
                    // find the $value in excess of $min_qty
                    // multiply by the font-size increment ($size)
                    // and add the $min_size set above
                    $size = round($min_size + (($value - $min_qty) * $step));

                    $result .= "<a href='$HOME/topic/$key' style='font-size: " . $size . "px'
                     title='" . $value . " things tagged with " . $key . "'>" . $this->label ( $key ) . "</a> \r";
            }
            return $result ;
        }

// $tags = array('weddings' => 32, 'birthdays' => 41, 'landscapes' => 62, 
// 'ham' => 51, 'chicken' => 23, 'food' => 91, 'turkey' => 47, 'windows' => 82, 'apple' => 27);

// printTagCloud($tags);


}


class xs_TopicRepresentation {

    private $ar ;

    function __construct ( $topic = null ) {

        if ( $topic !== null )
            $this->ar = $topic ;

    }

    function label () {
        // return $this->ar[]
    }
}