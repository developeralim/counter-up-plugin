<?php
      namespace Counter;

      class Validator {
            
            public array $errors = [];

            public bool $isErrors = false;

            public array $old = [];

            public static function validate( array $requestInputs,array $rules ){
                  
                  $self = new static;
                  
                  $target_url = $requestInputs['target_url'];
                  $xlsx_file = $requestInputs['xlsx_file'];

                  if ( $target_url  == "") {
                        $self->errors['target_url'][] = "Target URL is required";
                  } else {

                        if (! preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $target_url)) {
                              $self->errors['target_url'][] = "Target URL is not a valid URL";
                        }
                  }

                  if ( $xlsx_file['name'] == "") {
                        $self->errors['xlsx_file'][] = "File is required";
                  } else {
                        $extension = pathinfo($xlsx_file['name'], PATHINFO_EXTENSION);
                        if ($extension !== "xlsx") {
                              $self->errors['xlsx_file'][] = "Excel file is allowed to upload";
                        }
                  }
                  
                  return $self;
      
            }

            public function fails() {
                  $this->isErrors = true;
                  return count($this->errors) > 0;
            }

            public function error ( string $key = null ) {
                  return $this->errors[$key][0] ?? "";
            }

            public function get_errors (  ) {
                  return $this->errors;
            }

            public function old(string $key) {
                  return $this->old[$key] ?? '';
            }

      }