<?php
    
// Get a Page object, fully populated with all its items and their subitems
function get_page($page)
{
    $obj =& get_instance();
    
    $obj->load->model('page');
    $obj->load->model('item');
    $obj->load->model('listitem');
    
    $obj->load->helper('markdown');
    $obj->load->helper('format');
    
    $obj->db->order_by('displayorder');
    $query = $obj->db->get_where('item', array('pageid' => $page->id, 'deleted' => false));
    $dbitems = $query->result();
    
    $items = array();
    
    foreach($dbitems as $item)
    {       
        $l = new Item(); 
        $l->id = $item->id;
        $l->pageid = $item->pageid;
        $l->title = $item->title;
        $l->body = markdown($item->body);
        $l->bodymarkdown = $item->body;
        $l->datestamp = $item->datestamp;
        $l->type = $item->type;
        
        $obj->db->order_by('displayorder');
        $query = $obj->db->get_where('listitem', array('itemid' => $item->id, 'deleted' => false));
        
        // If there are list items for this item, add them
        if($query->num_rows() > 0)
        {
            $listitems = $query->result();
            
            foreach($listitems as $listitem)
            { 
                $i = new ListItem();
                $i->id = $listitem->id;
                $i->item = format_listitem($listitem->item);
                $i->itemid = $item->id;
                
                $l->items[] = $i;
            }
        }
        
        $items[] = $l;
    }
    
    $p = new Page(); 
    $p->id = $page->id;
    $p->title = $page->title;
    $p->accountid = $page->accountid;
    $p->description = $page->description;
    $p->isdefault = $page->isdefault;
    $p->items = $items;
    
    return $p;
}

?>