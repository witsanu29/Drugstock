<?php

function thai_date($date){
    if(!$date) return '';
    $time = strtotime($date);
    $day   = date('d', $time);
    $month = date('m', $time);
    $year  = date('Y', $time) + 543;
    return "$day/$month/$year";
}

?>