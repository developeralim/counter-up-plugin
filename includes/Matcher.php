<?php
      namespace Counter;
      use Counter\Application;

      class Matcher {

            public function __construct( protected Application $app) 
            {
                  
            }

            /**
             * Match to a key word with a content and return array containing how many times 
             * target key word exit in the content
             * @param string|array $keyword refers to which key word sould match
             * @return string $content 
             */
            public function __invoke ( array|string $keywords,string $content ) : array 
            {
                  $keywords = array_values($keywords);
                  
                  foreach ( $keywords as $key => $keyword ) {
                        if ( $key == 0 ) {
                              $keywords[$key][] = [
                                    "value" => "Mention",
                                    "bold" => '',
                                    "size" => 11,
                                    "format" => "General"
                              ];

                              continue;
                        }

                        array_walk($keyword,function($value,$k) use($content,&$keywords,$key){
                              if ( $k == 0 ) {
                                    $matchWordName = $value['value'] ?? '';

                                    $counter = 0;

                                    if ( preg_match_all("/$matchWordName/i",$content,$matches) ) {

                                          $counter = count($matches[0]);

                                    }

                                    $keywords[$key][] = [
                                          "value" => $counter,
                                          "bold" => '',
                                          "size" => 11,
                                          "format" => "General"
                                    ];

                              }
                        });
                  }

                 return $keywords;
            }
      } 