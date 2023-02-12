<?php 
      namespace Counter;

      class Request {
            public array $query = [];
            public array $files = [];
            public array $inputs = [];

            public function __construct() {
                  $this->query = $_GET;
                  $this->files = $_FILES;
                  $this->inputs = $_POST;
            }

            public function all () {
                  return [...$this->query,...$this->files,...$this->inputs];
            }
      }