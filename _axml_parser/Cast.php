<?php
    class Cast {

        public static function toCharSequence($string) {
            if ($string == null) {
                return null;
            }
            return new CSString($string);
        }
    }

?>
