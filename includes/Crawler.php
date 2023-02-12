<?php
      namespace Counter;

      class Crawler {

            /**
             * Fetch data from target url and remove script,style and head tag
             * then return only text
             * @param string $url refers to target URL
             * @return string content 
             */
            public function __invoke ( string $url ) 
            {     
                  ini_set('display_errors',0);
                  $content = "";
                  
                  $curl = curl_init();

                  curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                  ));

                  $content = curl_exec($curl);
                  if (curl_errno($curl)) {
                        $error = curl_error($curl);
                        echo <<< TEXT
                              <style>
                                    * {
                                          padding:0;
                                          margin:0;
                                    }
                                    .crawl-error {
                                          width: 100%;
                                          display: flex;
                                          align-items: center;
                                          justify-content: center;
                                          flex-direction:column;
                                          height: 100vh;
                                          background: #666;
                                          font-size: 30px;
                                          color: #fff;
                                          font-weight: bold;
                                    }

                                    .crawl-error a {
                                          text-decoration: none;
                                          color: ;
                                          font-size: 15px;
                                          padding: 10px;
                                          background: #fff;
                                          margin-top: 10px;
                                    }

                              </style>
                              <div class="crawl-error">
                                    <p>{$error}</p>
                                    <a href="">Go Back</a>
                              </div>
                        TEXT;
                        exit;
                  }
                  
                  curl_close($curl);
                  
                  $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
                  $content = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $content);
                  $content = preg_replace('#<head>(.*?)</head>#is', '', $content);
                  $content = strip_tags($content);
                  return $content;
            }
      } 