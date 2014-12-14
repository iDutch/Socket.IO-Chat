<?php
$salt = 'Wh4t1s4s4lt?';
$hash = hash('sha256', '!Limecat22'.$salt);
var_dump($hash);