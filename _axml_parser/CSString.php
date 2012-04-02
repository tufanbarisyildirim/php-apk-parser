<?php
    class CSString /*implements CharSequence*/ 
    {

        private $m_string;

        public function _construct($string) 
        {
            if ($string == null) 
            {
                $string="";
            }
            $this->m_string = $string;
        }

        public function length() {
            return strlen($this->m_string);
        }

        public function charAt($index) 
        {
            return $this->m_string[$index];
        }

        public  function subSequence($start,$end) 
        {
            return new CSString(substr($this->m_string,$start,$end));
        }

        public function toString() {
            return $this->m_string;
        }


}