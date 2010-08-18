<?php
    
// Format the text stored in a list item for display
function format_listitem($text)
{
    
    return $text; // preg_replace('/^((https?|ftp|dict):[^\\\'">\\s]+)$/im', '<a href="\\1">\\1</a>', $text);
}

?>