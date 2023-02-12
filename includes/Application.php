<?php

      namespace Counter;

      require_once plugin_dir_path(__DIR__) . "vendor/autoload.php";

      use Counter\Request;
      use Counter\Validator;
      use Counter\Crawler;
      use Counter\Matcher;
      use PhpOffice\PhpSpreadsheet\IOFactory;
      use PhpOffice\PhpSpreadsheet\Spreadsheet;

      final class Application {
            
            /* Match strictly */
            public const MATCH_STRICT = false;

            /* History automatically delete after */
            private const HISTORY_DELETE_AFTER = 1;

            /* View path */
            public string $viewPath;

            /* Upload Path */
            public string $uploadPath;

            /* History Path */
            public string $historyPath;

            /* Plugin's shortcode */
            public string $shortcode;

            /* table name */
            private string $table;

            /* Current logged in user */
            public string $user;

            /* tool page permalink */
            private string $toolPermalink;

            /* Shortcode position */
            public int $shortcodePosition;

            /* Shortcode match pattern */
            public string $shortcodeMatchPatter;

            public function __construct(protected Request $request) 
            {
                  /* Set some default property */
                  $this->uploadPath           = plugin_dir_path( __DIR__ ) . 'uploads';
                  $this->historyPath          = plugin_dir_path( __DIR__ ) . 'views/history';
                  $this->user                 = wp_get_current_user()->user_login;
                  $this->table                = get_option('tool_table');
                  $this->toolPermalink        = get_permalink(get_option('tool_page_id'));
                  $this->shortcode            = get_option('tool_shortcode');
                  $this->shortcodePosition    = (int) get_option('tool_shortcode_position');
                  $this->shortcodeMatchPatter = get_option('shortcode_match_pattern');
            }

            public function __destruct ( ) 
            {
                  /* Clear uploads */

                  foreach( glob($this->uploadPath.'/*') as $file) :
                        if ( is_file($file) && pathinfo($file,PATHINFO_EXTENSION) !== 'php') unlink($file);
                        unset($file);
                  endforeach;

                  /* Clear history after a month */
                  $this->clearHistory();
            }

            /**
             * Grab submitted data and validate
             * @param null no params
             * @return void nothing to return
             */

            public function handleSubmit () {

                  //check _wp_nonce
                  if ( ! isset ( $_POST['_wpnonce'] ) ) {
                        //TODO when request is invalid
                        return;
                  }

               
                  // verify _wp_nonce

                  if ( ! wp_verify_nonce($_POST['_wpnonce'],__FUNCTION__)) 
                  {
                        throw new \RuntimeException("Bad Request");
                  } 
                  
                  // request input
                  $requestInputs = $this->request->all();
      
                  // validate inputs
                  $validator = Validator::validate($requestInputs,[
                        'target_url' => 'required|url',
                        'xlsx_file'  => 'required|file:type,xlsx,xls'
                  ]);


                  // add this errors as the class property

                  if ( $validator->fails() ) {
                       
                        // set all currnt request value as old value
                        array_walk ( $requestInputs,function ( $value,$key ) use($validator) {
                              
                              $value = is_array($value) ? $value['name'] ?? $value : $value;
                              
                              // check if $value is an array
                              $validator->old[$key] = $value;
                              
                        });

                        // render submit page again with errors and old values
                        return $validator;
                  }

                  // if validation is successful
                  return $this->actionAfterValidation( $requestInputs );
                  
            }

            /**
             * when validation successful then we need to upload file as temporarily and
             * crawl target url 
             * @param array $inputs all validated input
             * @return 
             */
            
            private function actionAfterValidation( array $inputs ) 
            {
                  global $wpdb;

                  $file = $inputs['xlsx_file'];

                  $name = pathinfo($file['name'],PATHINFO_BASENAME);

                  $path = $this->upload($file);
                    
                  $crawl_content = (new Crawler())($inputs['target_url']);

                  if ( empty ( $crawl_content ) ) return ['error' => 'This URL is not allowed to be read'];

                  $xlsx_content = $this->read_xlsx($path);
                  
                  /* Check if $xlsx_content array is empty or not */
                  if ( empty ( $xlsx_content ) ) return null;

                  // match crawl content with targetd keyword
                  $matche_content = (new Matcher($this))($xlsx_content,$crawl_content);

                  $thead = $this->tableHead($matche_content);
                  $tbody = $this->tableBody($matche_content);
                  
                  $table = <<< TEXT
                        <table class="table">
                              <thead>
                                    {$thead}
                              </thead>
                              <tbody>
                                    {$tbody}
                              </tbody>
                        </table>
                  TEXT;

                 
                  /* A reference pass in database to grab later base on user logged in  */

                  if ( $this->user ) {


                        /* save data in a history file to load them later */
                        $historyFile  = pathinfo($file['name'],PATHINFO_FILENAME) . '_' . time();
                        $fopen        = fopen($this->historyPath . '/' . $historyFile . '.html','w');
                        fwrite($fopen,$table);
                        fclose($fopen);
 

                        $data = array(
                              'user' => $this->user,
                              'ref'  => $historyFile,
                        );

                        $format = array(
                              '%s',
                              '%s'
                        );

                        $wpdb->insert( $this->table, $data, $format );

                        /* Generate excel file to download next */
                        $this->generateExcel( $matche_content,$historyFile);
                  }

                 

                  return $table;
            }
 
            /**
             * Read Excel file and return its output as an array
             * @param string path/to/file of excel
             * @return array containing excel file data array
             */

            private function read_xlsx( string $xlsxFile ) 
            {                  
                  
                  $objPHPExcel = IOFactory::load($xlsxFile);
                  //  Get worksheet dimensions
                  $sheet          = $objPHPExcel->getSheet(0); 
                  $highestRow     = $sheet->getHighestRow(); 
                  $highestColumn  = $sheet->getHighestColumn();
                  
                  //  Loop through each row of the worksheet in turn
                  for ($row = 1; $row <= $highestRow; $row++) {
                        for ($column = 'A'; $column <= $highestColumn; $column++) {

                              $cell = $sheet->getCell($column . $row);
                              $style = $sheet->getStyle($column . $row);
                              $value = $cell->getValue();
                              $format = $style->getNumberFormat()->getFormatCode();
                              
                              $font = $style->getFont();
                              $fontName = $font->getName();
                              $fontSize = $font->getSize();
                              $fontBold = $font->getBold();

                              $data[$row][] = [
                                    'value' => $value,
                                    'bold' => $fontBold,
                                    'size'  => $fontSize,
                                    'format' => $format,
                              ];
                        }
                  }

                  return $data;

            }
        
            /**
             * Create Table Heading By Given Data 
             * @param array $data referes to an array 
             * @return string table heading markup
             */

            private function tableHead( array $data ) {

                  $keys = array_shift($data);

                  $th = array_map(function( $key ){
                        return <<< TEXT
                              <th class="col filter" style="font-size:{$key['size']};">{$key['value']}</th>
                        TEXT;
                  },$keys);
            
                  return sprintf('<tr>%s</tr>',implode(" ",$th));

            }
            
             /**
             * Create Table Body By Given Data 
             * @param array $data referes to an array 
             * @return string table body markup
             */

            private function tableBody( array $data ) {

                  $values = array_slice($data,1);

                  $tbody = '';
                  
                  foreach ( $values as $value ) {
                        $tbody .= '<tr>';
                        foreach($value as $_value) {
                              // check if format contains % sign
                  
                              if ( \str_contains($_value['format'],'%')) {
                                    $_value['value'] = (float) $_value['value'] * 100;
                                    $_value['value'] = \number_format($_value['value'],2,'.',',').'%';
                              }
                              $tbody .= <<< TEXT
                                    <td class='col' style="font-size:{$_value['size']};">{$_value['value']}</td>
                              TEXT;
                              
                        }
                        $tbody .= "</tr>";
                  }
            
                return $tbody;
            }

             /**
             * Upload file and get file directory 
             * @param array $file
             * @return string immidiately uploaded file path
             */

            protected function upload( array $file ) 
            {
                  extract($file);
                  
                  if ( move_uploaded_file($tmp_name, $path = $this->uploadPath . "/$name") ){
                        return $path;
                  }

                  return null;
            }


            protected function generateExcel( array $data,string $filename ) {

                  $excelArray = [];

                  foreach ( $data as $key => $value ) {
                        foreach ( $value as $k => $v ) {

                              $column = $v['value'];
                              
                              if ( \str_contains($v['format'] , '%') ) {
                                    $column = (string) ($v['value'] * 100) . '%'; 
                              }
                              $excelArray[$key][] = $column;
                        }
                  }

                  //generate and save as xlsx file
                  $spreadsheet = new Spreadsheet();
                  $sheet = $spreadsheet->getActiveSheet();

                  // Your data stored in an array

                  $sheet->fromArray($excelArray, null, 'A1');

                  // Save the Excel file
                  $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                  $writer->save($this->historyPath . "/$filename.xlsx");

            }

            /**
             * Read targeted hostory 
             * @param string $filename as a databse ref of file
             * @return bool|string return $file content 
             */

            public function readHistory ( string $filename = null ) 
            {
                  global $wpdb;

                  if ( is_null($filename) ) return;

                  /* Check if record available on databae or not */

                  $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE user = %s AND ref = %s ",[
                        $this->user,$filename
                  ]));

                  if ( $row && file_exists( $file = $this->historyPath."/$filename.html") ) {
                        return file_get_contents($file);
                  }

                  return false;
            }

             /**
             * get hostory file to create history menu
             * @param null no params
             * @return void nothing to return
             */


            public function getHistory ( ) 
            {
                  global $wpdb;
                  $rows = $wpdb->get_results( "SELECT * FROM $this->table WHERE user = '$this->user'" );

                  $data = [];

                  foreach( $rows as $row ) {
                        $data[] = $row->ref;
                  }
                  
                  return $data;

            }

            /**
             * Get logged in user name
             * @param null no params
             * @return string user name
             */

            public function userName() {
                  return wp_get_current_user()->display_name;
            } 

            /**
             * Get logged in user profile avatar image
             * @param null no params
             * @return string avatar link
             */

            public function getProfile ( ) {
                  return get_avatar_url($this->user);
            }


            /**
             * Download history file
             * @param $filename targeted file name from url 
             * @return string file location
             */

             public function download( string $filename ) : mixed 
             {
                  //check if file exist in histroy
                  if ( file_exists (  $this->historyPath. "/$filename.xlsx" ) ) {
                        return plugins_url( "counter/views/history/$filename.xlsx" );
                  }

                  return null;
             }

            /**
             * Get tool's used page permalink
             * @param null no params
             * @return void nothing to return
             */

            public function toolPermalink() {
                  return $this->toolPermalink;
            }

            /**
             * Clear history
             * @param null no params
             * @return void nothing to return
             */
            public function clearHistory() 
            {
                  global $wpdb;
                  
                  /* Clear history after a month */
                  foreach ( glob($this->historyPath .'/*') as $file) : 

                        /* check if $file is not pointing index.php file  */
                        if ( pathinfo($file,PATHINFO_EXTENSION) === 'php' ) continue;

                        if ( ! isset($_GET['clear']) ) {
                              $created_before = (int) date_diff(
                                    date_create(date('Y-m-d',fileatime($file))),
                                    date_create()
                              )->format('%a');
                              if ( $created_before >= self::HISTORY_DELETE_AFTER ){
                                    unlink($file);
      
                                    /* Delete database reference */
                                    $dbRef = pathinfo($file,PATHINFO_FILENAME);
                                    $wpdb->query("DELETE FROM {$this->table} WHERE ref = '$dbRef' AND user = '{$this->user}'");
                                    unlink($file);
                              }; 
                        } else {
                             
                              $dbRef = pathinfo($file,PATHINFO_FILENAME);
                              $wpdb->query("DELETE FROM {$this->table} WHERE ref = '$dbRef' AND user = '{$this->user}'");
                              unlink($file);
                        }

                  endforeach;

                  if ( isset($_GET['clear']) ) {
                        /* Redirect after deletion */
                        wp_redirect($this->toolPermalink,301);
                  }
            }
      }

      return $app = new Application(new Request);