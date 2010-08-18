<?php
    
class Item extends Model {
    
    var $id = 0;
    var $pageid = 0;
    var $title = '';
    var $body = '';
    var $bodymarkdown = '';
    var $items = array();
    var $datestamp = '';
    var $type = 0;

    function Item()
    {
        parent::Model();
    }
}

?>