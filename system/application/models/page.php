<?php
    
class Page extends Model {

    var $id = 0;
    var $accountid = 0;
    var $title = '';
    var $description = '';
    var $isdefault = false;
    var $items = array();

    function Page()
    {
        parent::Model();
    }
}

?>