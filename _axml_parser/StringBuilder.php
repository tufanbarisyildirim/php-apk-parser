<?php
  class StringBuilder
  {
      private $raw = "";
      
      public function append($string)
      {
          $this->raw .= $string;
      }
      
      public function __toString()
      {
          return $this->raw;
      }
      
      public function toString()
      {
          return $this->raw;
      }
  }
?>
