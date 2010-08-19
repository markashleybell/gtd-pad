<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <title><?=$page->title?> - BpClone</title>
        <style type="text/css">
            
            body { font-family: Arial, Helvetica, sans-serif; }
            
            #container { width: 900px; margin: 0 auto; overflow: auto; }
            #header { width: 900px; background-color: #000000; overflow: auto; }
            #header a { color: #fff; }
            #admin-nav { float: right; }
            .page { width: 600px; float: left; background-color: #e0e0e0; }
            #page-nav { width: 300px; float: right; background-color: #999999; }
            
        </style>
        <!-- <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/jquery-ui.min.js"></script> -->
        <script type="text/javascript" src="/script/jquery-1.4.2.min.js"></script>
        <script type="text/javascript" src="/script/jquery-ui-1.8.2.min.js"></script>
        <script type="text/javascript">
            
            //<![CDATA[
            
            function insertHtml(html, id, cls)
{
    return html.replace(/\[\{ID\}\]/gi, id).replace(/\[\{CLASS\}\]/gi, cls);
}

// Keep all the HTML in variables so we can easily build it up when a new item is added
var listFormHtml = '<form id="[{ID}]" class="[{CLASS}]" action="/main/update_item/" method="post">' +
                   '    <p><label>Title<\/label> <input type="text" name="title" value="" \/><\/p>' +
                   '    <input type="hidden" name="id" value="" \/>' +
                   '    <input type="hidden" name="pageid" value="" \/>' +
                   '    <input type="hidden" name="type" value="0" \/>' + 
                   '    <input type="submit" value="Save" \/> <a href="#" class="hideparent">Close</a><\/p>' +
                   '<\/form>';

var noteFormHtml = '<form id="[{ID}]" class="[{CLASS}]" action="/main/update_item/" method="post">' +
                   '    <p><label>Title<\/label> <input type="text" name="title" value="" \/><\/p>' +
                   '    <p><label>Body<\/label> <textarea name="body"><\/textarea><\/p>' +
                   '    <input type="hidden" name="id" value="" \/>' +
                   '    <input type="hidden" name="pageid" value="" \/>' +
                   '    <input type="hidden" name="type" value="1" \/>' + 
                   '    <input type="submit" value="Save" \/> <a href="#" class="hideparent">Close</a><\/p>' +
                   '<\/form>';

var itemFormHtml = '<form id="[{ID}]" class="[{CLASS}]" action="/main/update_listitem/" method="post">' +
                   '    <p><textarea name="item"><\/textarea>' +
                   '    <input type="hidden" name="id" value="" \/>' +
                   '    <input type="hidden" name="itemid" value="" \/>' +
                   '    <input type="submit" value="Save" \/> <a href="#" class="hideparent">Close</a><\/p>' +
                   '<\/form>';

var addItemHtml = '<span id="[{ID}]" class="[{CLASS}]"><a href="#">Add Item<\/a><\/span>';

var itemControlsHtml = '<span class="controls">' +
                       '    <a href="#" class="control-delete">Delete<\/a>' +
                       '    <a href="#" class="control-edit">Edit<\/a>' +
                       '    <a href="#" class="control-move">Move<\/a>' +
                       '<\/span>';

var listitemControlsHtml = '<span class="controls">' +
                           '    <a href="#" class="control-delete">Delete<\/a>' +
                           '    <a href="#" class="control-edit">Edit<\/a>' +
                           '    <a href="#" class="control-move">Move<\/a>' +
                           '    <input type="checkbox" id="check-[{ID}]" name="check-[{ID}]" value="1" \/>' +
                           '<\/span>';

$(function(){
        
    // Initially hide all the edit forms
    // TODO: Do this in CSS? JS is going to be required anyway... or do it earlier than onload
    $('.item-edit-form, .item-add-form, .listitem-add-form, .listitem-edit-form, .page-edit-form').hide();
    
    // Autolink any plain text links in list items
    $('.item li div.content').each(function(){
        var li = $(this);
        var item = autoLink(li.html());
        li.html(item);
    });
        
    // Click handler for add list item
    $('.control-new-list-item a').live('click', function() {
        
        var span = $(this).parent();
        var id = span.attr('id').split('-');
        var elem = id.slice(0, (id.length - 1));
        var itemid = elem[elem.length];
        
        var f = $('#' + elem.join('-'));
        
        f.find('[name=item]').val('');
        f.show();
        span.hide();
        
        return false; 
        
    });
        
    // Click handler for page title edit
    $('#control-rename').bind('click', function() {
        
        var pageContainer = $(this).parent().parent();
        
        pageContainer.children('.content').hide();
        pageContainer.children('.page-edit-form').show();
        
        return false; 
        
    });
    
    // Click handler for adding a note
    $('#control-addnote').bind('click', function() {
        
        var form = $('#add-note');
        $('input[name=title], textarea[name=body]', form).val('');
        form.show();
        return false;
    
    });
    
    // Click handler for adding a list
    $('#control-addlist').bind('click', function() {
        
        var form = $('#add-list');
        $('input[name=title], input[name=listitem]', form).val('');
        form.show();
        return false;
    
    });
    
    // Handle edit click for an item
    $('.control-edit').live('click', function() {
        
        var itemContainer = $(this).parent().parent();
        
        itemContainer.children('.content'). hide();
        itemContainer.children('.item-edit-form, .listitem-edit-form').show();
        
        return false; 
        
    });
    
    $('.page-edit-form').live('submit', function() {
        
        var form = $(this);
        
        $.ajax({
            url: '/main/update_page/',
            data: form.serialize(),
            dataType: 'json',
            type: 'POST',
            success: function(data, status, request) { 
                                   
                form.parent().children('.content').html('<h1>' + form.find('input[name=title]').val() + '</h2>').show();
                $('#pagenav-' + form.find('input[name=id]').val() + ' a:first').text(form.find('input[name=title]').val());
                form.hide();
                
            },
            error: function(request, status, error) { alert(status); }
        });

        return false;        

    });
    
    $('.item-add-form').live('submit', function() {
        
        var form = $(this);

        $.ajax({
            url: '/main/update_item/',
            data: form.serialize(),
            dataType: 'json',
            type: 'POST',
            success: function(data, status, request) { 

                var html = '<div id="item-' + data.id + '" class="item">' + itemControlsHtml;

                if(parseInt(data.type, 10) == 0)
                {	
                    // We're just editing the title of a list
                    html += insertHtml(listFormHtml, 'edit-item-' + data.id, 'item-edit-form') + '<div class="content"><h2>' + form.find('input[name=title]').val() + '<\/h2><\/div>';
                    html += '<ul><li id="listitem-' + data.itemid + '">' + insertHtml(listitemControlsHtml, data.itemid, '') + '<div class="content">' + autoLink(form.find('input[name=listitem]').val()) + '<\/div>';
                    html += insertHtml(itemFormHtml, 'edit-listitem-' + data.itemid, 'listitem-edit-form') + '<\/li><\/ul>';
                    html += insertHtml(itemFormHtml, 'add-listitem-to-' + data.id, 'listitem-add-form');
                    html += insertHtml(addItemHtml, 'add-listitem-to-' + data.id + '-addlink', 'control-new-list-item');
                }
                else
                {
                    html += insertHtml(noteFormHtml, 'edit-item-' + data.id, 'item-edit-form') + '<div class="content"><h2>' + form.find('input[name=title]').val() + '<\/h2>' + data.bodyhtml + '<\/div>';
                }
                
                html += '<\/div>';

                if($('.item').length)
                    $('.item:first').before(html);
                else
                    $('.page').append(html);
                    
                // Change field in add listitem form from textarea back to text type
                $('#add-listitem-to-' + data.id + ' textarea').replaceWith('<input type="text" name="item" value="" \/>');

                var f = $('.item:first');

                f.find('.item-edit-form input[name=id]').val(data.id);
                
                f.find('.listitem-edit-form input[name=id]').val(data.itemid);
                f.find('.listitem-edit-form input[name=itemid]').val(data.id);
                f.find('.listitem-edit-form textarea[name=item]').val(form.find('input[name=listitem]').val());
                
                f.find('.item-edit-form input[name=pageid]').val(form.parent().attr('id').split('-')[1]);
                f.find('.item-edit-form input[name=title]').val(form.find('input[name=title]').val());
                
                if(parseInt(data.type, 10) == 1)
                    f.find('.item-edit-form input[name=body]').val(form.find('input[name=body]').val());
                    
                f.find('.listitem-add-form input[name=itemid]').val(data.id);

                f.find('form').hide();
                form.hide();
                
                f.find('ul').sortable({
                    handle: '.control-move',
                    update: updateListItemOrder
                });
            },
            error: function(request, status, error) { alert(status); }
        });

        return false;        

    });

    $('.item-edit-form').live('submit', function() {
        
        var form = $(this);

        $.ajax({
            url: '/main/update_item/',
            data: form.serialize(),
            dataType: 'json',
            type: 'POST',
            success: function(data, status, request) { 

                form.hide();
                
                var c = form.parent().children('.content');
                
                if(data.type == 0) 
                    c.html('<h2>' + form.find('input[name=title]').val() + '<\/h2>')
                else
                    c.html('<h2>' + form.find('input[name=title]').val() + '<\/h2>' + data.bodyhtml);
                
                c.show();
            },
            error: function(request, status, error) { alert(status); }
        });

        return false;        

    });
    
    $('.listitem-add-form').live('submit', function() {
        
        var form = $(this);

        $.ajax({
            url: '/main/update_listitem/',
            data: form.serialize(),
            dataType: 'json',
            type: 'POST',
            success: function(data, status, request) { 
                
                var ul = form.parent().find('ul');
                ul.append('<li id="listitem-' + data.id + '">' + insertHtml(listitemControlsHtml, data.id, '') + '<div class="content">' + autoLink(form.find('input[name=item]').val()) + '<\/div>' + insertHtml(itemFormHtml, 'edit-item-' + data.id, 'listitem-edit-form') + '<\/li>');
                
                ul.sortable('refresh');
                
                var f = $('#edit-item-' + data.id);
                
                var itemid = form.parent().attr('id').split('-')[1];
                f.find('input[name=itemid]').val(itemid);
                f.find('input[name=id]').val(data.id);
                f.find('textarea[name=item]').val(form.find('input[name=item]').val());
                f.hide();
                form.hide();
                $('#add-listitem-to-' + itemid + '-addlink').show();

            },
            error: function(request, status, error) { alert(status); }
        });
        
        return false;        

    });
    
    $('.listitem-edit-form').live('submit', function() {
        
        var form = $(this);
        
        $.ajax({
            url: '/main/update_listitem/',
            data: form.serialize(),
            dataType: 'json',
            type: 'POST',
            success: function(data, status, request) {
                form.hide();
                var c = form.parent().children('.content').html(autoLink(form.find('textarea[name=item]').val()));
                c.show();
            },
            error: function(request, status, error) { alert(status); }
        });
        
        return false;
    
    });
    
    $('#items').sortable({
        handle: '.control-move',
        update: updateItemOrder
    });
    
    $('.item ul').sortable({
        handle: '.control-move',
        update: updateListItemOrder,
        connectWith: ['.item ul']
    });
    
    $('#page-nav ul').sortable({
        handle: '.control-move',
        update: updatePageOrder
    });
    
    $('.control-move').live('click', function() {
        
        // TODO: Somehow hide the deit form if move is dragged?
        // $(this).parent().parent().children('form').hide();
        // $(this).parent().parent().children('.content').show();
        
        return false;
        
    });
    
    $('.hideparent').live('click', function() {
        
        var frm = $(this).parent().parent();
        frm.hide();
        frm.parent().children('.content').show();
        return false;
        
    });
    
    // Click handler for page title edit
    $('.control-delete').bind('click', function() {
        
        var item = $(this).parent().parent();
        var itemid = item.attr('id').split('-')[1];
            
        $.ajax({
            url: ((item.is('li')) ? '/main/delete_listitem/' : '/main/delete_item/'),
            data: { id: itemid },
            dataType: 'json',
            type: 'POST',
            success: function(data, status, request) {
                item.remove();
            },
            error: function(request, status, error) { alert(status); }
        });
        
        return false; 
        
    });
    
    $('#control-delete').bind('click', function() {
        
        $(this).closest('form').submit();
        
        return false;
        
    });
    
    $('.item input[type=checkbox]').live('click', function() {
        
        var cb = $(this);
        
        // TODO: Alter this so that there is a field which represents whether to check/show items or delete them on check
        $.ajax({
            url: '/main/delete_listitem/',
            data: { id: cb.attr('id').split('-')[1] },
            dataType: 'json',
            type: 'POST',
            success: function(data, status, request) {
                cb.parent().parent().remove();
            },
            error: function(request, status, error) { alert(status); }
        });
        
    });

});

function updatePageOrder()
{
    var data = [];
    
    $('#page-nav ul li').each(function(index){
        data.push('item[]=' + $(this).attr('id').split('-')[1]);
    });
    
    $.ajax({
        url: '/main/reorder_page/',
        data: data.join('&'),
        // dataType: 'json',
        type: 'POST',
        success: function(data, status, request) {
            
            // console.log('done');
            // Do something here?
            
        },
        error: function(request, status, error) { alert(status); }
    });
}

function updateItemOrder()
{
    var data = [];
    
    $('#items .item').each(function(index){
        data.push('item[]=' + $(this).attr('id').split('-')[1]);
    });
    
    $.ajax({
        url: '/main/reorder_item/',
        data: data.join('&'),
        // dataType: 'json',
        type: 'POST',
        success: function(data, status, request) {
            
            // console.log('done');
            // Do something here?
            
        },
        error: function(request, status, error) { alert(status); }
    });
}

function updateListItemOrder()
{
    var data = [];
    
    // Update the item's list id in case it's been dropped from another list
    data.push('parentid=' + $(this).parent().attr('id').split('-')[1]);
    
    $(this).find('li').each(function(index){
        data.push('item[]=' + $(this).attr('id').split('-')[1]);
    });
    
    $.ajax({
        url: '/main/reorder_listitem/',
        data: data.join('&'),
        // dataType: 'json',
        type: 'POST',
        success: function(data, status, request) {
            
            // console.log('done');
            // Do something here?
            
        },
        error: function(request, status, error) { alert(status); }
    });
    
}

// Replace plain text urls with links
function autoLink(input)
{
    return input.replace(/^((https?|ftp|dict):[^\'">\s]+)$/img, '<a href="$1">$1</a>');
}
            
            //]]>
    
        </script>
    </head>
    <body>

    <div id="container">
        
        <div id="header">
            
            <ul id="admin-nav">
                <li><a href="/auth/logout/">Log Out</a></li>
            </ul>
            
        </div>
        
        <div class="page" id="page-<?=$page->id?>">
            <div class="page-controls">
                <a href="#" id="control-addlist">Add List</a>
                <a href="#" id="control-addnote">Add Note</a>
                <a href="/main/update_page/" id="control-addpage">New Page</a>
            </div>
            
            <div class="controls">
                <a href="#" id="control-rename">Rename</a>
                
                <? if(!$page->isdefault) { ?>
                <form action="/main/delete_page/" method="post">
                    <input type="hidden" name="pageid" value="<?=$page->id?>" />
                    <input type="hidden" name="accountid" value="<?=$page->accountid?>" />
                    <a href="#" id="control-delete">Delete</a>
                </form>
                <? } ?>
                
            </div>
            
            <div class="content"><h1><?=$page->title?></h1></div>
                
            <form id="update-page-<?=$page->id?>" class="page-edit-form" action="/main/update_page/" method="post">

                <p><label>Title</label> <input type="text" name="title" value="<?=$page->title?>" />
                
                <input type="hidden" name="id" value="<?=$page->id?>" /></p>
               
                <p><input type="submit" value="Save" /> <a href="#" class="hideparent">Close</a></p>
            </form>
            
            <form id="add-note" class="item-add-form" action="/main/update_item/" method="post">
                <h2>Add Note</h2>
                <p><label>Title</label> <input type="text" name="title" value="" /></p>
                <p><label>Body</label> <textarea name="body"></textarea>
                <input type="hidden" name="id" value="" />
                <input type="hidden" name="pageid" value="<?=$page->id?>" />
                <input type="hidden" name="type" value="1" />
                <input type="submit" value="Save" /> <a href="#" class="hideparent">Close</a></p>
            </form>

            <form id="add-list" class="item-add-form" action="/main/update_item/" method="post">
                <h2>Add List</h2>
                <p><label>Title</label> <input type="text" name="title" value="" /></p>
                <p><label>Item</label> <input type="text" name="listitem" value="" />
                <input type="hidden" name="id" value="" />
                <input type="hidden" name="pageid" value="<?=$page->id?>" />
                <input type="hidden" name="type" value="0" />
                <input type="submit" value="Save" /> <a href="#" class="hideparent">Close</a></p>
            </form>
               
            <div id="items">
            <?
            foreach ($page->items as $item)
            {    
            ?>
                <div id="item-<?=$item->id?>" class="item">
                    <div class="controls">
                        <a href="#" class="control-delete">Delete</a>
                        <a href="#" class="control-edit">Edit</a>
                        <a href="#" class="control-move">Move</a>
                    </div>
                    <?
                    // if it's a list
                    if($item->type == 0) 
                    {
                        if(count($item->items) > 0)
                        {
                            ?>
                            <div class="content">
                                <h2><?=$item->title?></h2>
                            </div>
                            <form id="edit-item-<?=$item->id?>" class="item-edit-form" action="/main/update_item/" method="post">
                                <p><label>Title</label> <input type="text" name="title" value="<?=$item->title?>" /> 
                                <input type="hidden" name="id" value="<?=$item->id?>" />
                                <input type="hidden" name="pageid" value="<?=$item->pageid?>" />
                                <input type="hidden" name="type" value="<?=$item->type?>" />
                                <input type="submit" value="Save" /> <a href="#" class="hideparent">Close</a></p>
                            </form>
                            <ul>
                            <?
                            foreach($item->items as $li)
                            {
                                ?>
                                <li id="listitem-<?=$li->id?>">
                                    <div class="controls">
                                        <a href="#" class="control-delete">Delete</a>
                                        <a href="#" class="control-edit">Edit</a>
                                        <a href="#" class="control-move">Move</a>
                                        <input type="checkbox" id="check-<?=$li->id?>" name="check-<?=$li->id?>" value="1" />
                                    </div>
                                    <div class="content"><?=$li->item?></div>
                                    <form id="edit-listitem-<?=$li->id?>" class="listitem-edit-form" action="/main/update_listitem/" method="post">
                                        <p><textarea name="item"><?=$li->item?></textarea>
                                        <input type="hidden" name="itemid" value="<?=$li->itemid?>" />
                                        <input type="hidden" name="id" value="<?=$li->id?>" />
                                        <input type="submit" value="Save" /> <a href="#" class="hideparent">Close</a></p>
                                    </form>
                                </li>
                                <?
                            }
                            ?>
                            </ul>
                            <form id="add-listitem-to-<?=$li->itemid?>" class="listitem-add-form" action="/main/update_listitem/" method="post">
                                <p><input type="text" name="item" value="" />
                                <input type="hidden" name="itemid" value="<?=$li->itemid?>" />
                                <input type="hidden" name="id" value="" />
                                <input type="submit" value="Save" /> <a href="#" class="hideparent">Close</a></p>
                            </form>
                            <span id="add-listitem-to-<?=$li->itemid?>-addlink" class="control-new-list-item"><a href="#">Add Item</a></span>
                            <?
                        }
                    }
                    else if($item->type == 1)  // It's a note
                    {
                        ?>
                        <div class="content">
                            <h2><?=$item->title?></h2>
                            <?=$item->body?>
                        </div>
                        <form id="edit-item-<?=$item->id?>" class="item-edit-form" action="/main/update_item/" method="post">
                            <p><label>Title</label> <input type="text" name="title" value="<?=$item->title?>" /></p>
                            <p><label>Body</label> <textarea name="body"><?=$item->bodymarkdown?></textarea>
                            <input type="hidden" name="id" value="<?=$item->id?>" />
                            <input type="hidden" name="pageid" value="<?=$item->pageid?>" />
                            <input type="hidden" name="type" value="<?=$item->type?>" />
                            <input type="submit" value="Save" /> <a href="#" class="hideparent">Close</a></p>
                        </form>
                        <?
                    }  
                    ?>
                </div>
                <?      
            }
            ?>
            </div>
            </div>
            <div id="page-nav">
                <ul>
                <?
                foreach ($pages as $p)
                {
                    ?>
                    <li id="pagenav-<?=$p->id?>"><a href="/main/page/<?=$p->id?>/"><?=$p->title?></a><a class="control-move" href="#">Mv</a></li>
                    <?
                }
                ?>
                </ul>
            </div>
        </div>
    </body>
</html>