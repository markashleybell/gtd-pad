<?php
    
// Format the text stored in a list item for display
function pretty_redirect($url)
{
    $obj =& get_instance();
    
    $obj->load->helper('url');
    
    $siteurl = site_url();
    
    redirect(substr($siteurl, 0, strrpos($siteurl, '/')) . $url, 'refresh');
}

?>