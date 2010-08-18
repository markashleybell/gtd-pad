<?php

class Main extends Controller {

    function Main()
    {
        parent::Controller();
        
        $this->load->helper('url');
        $this->load->library('tank_auth');
    }
    
    function index()
    {
        if (!$this->tank_auth->is_logged_in())
        {
			redirect('/auth/login/');
		}
        else
        {
            $userid = $this->tank_auth->get_user_id();
            
            $this->load->helper('page');
            
            // Get the id of the page marked as default in the db
            $query = $this->db->get_where('page', array('deleted' => false, 'accountid' => $userid, 'isdefault' => 1));
            $page = $query->row();
            
            $data['page'] = get_page($page);
            
            $this->db->order_by('displayorder', 'asc');
            $query = $this->db->get_where('page', array('deleted' => false, 'accountid' => $userid));
            
            $data['pages'] = $query->result();
        
            $this->load->view('main', $data);
        }
    }
    
    function page()
    {
        if (!$this->tank_auth->is_logged_in())
        {
			redirect('/auth/login/');
		}
        else
        {
            $userid = $this->tank_auth->get_user_id();
            
            $this->load->helper('page');
            
            // Get the id of the page marked as default in the db
            $query = $this->db->get_where('page', array('deleted' => false, 'accountid' => $userid, 'id' => $this->uri->segment(3)));
            $page = $query->row();
            
            $data['page'] = get_page($page);
            
            $this->db->order_by('displayorder', 'asc');
            $query = $this->db->get_where('page', array('deleted' => false, 'accountid' => $userid));
            
            $data['pages'] = $query->result();
           
            $this->load->view('main', $data);
        }
    }
    
    function get_item()
    {
        $query = $this->db->get_where('item', array('id' => $_POST['id']));
        $item = $query->row();
        
        echo json_encode($item);
    }
   
    function update_page()
    {
        $id = (isset($_POST['id'])) ? $_POST['id'] : '';
        $title = (isset($_POST['title'])) ? $_POST['title'] : 'New Page';
        $description = (isset($_POST['description'])) ? $_POST['description'] : '';
        $userid = $this->tank_auth->get_user_id();
        
        if($id != '')
        {
            $data = array(
                'title' => $title,
                'description' => $description
            );
                
            $this->db->where('id', $id);
            $this->db->update('page', $data); 
            
            $output = array('id'=>$id);
            
            echo json_encode($output);
        }
        else
        {
            $query = $this->db->query('SELECT MIN(displayorder) as displayorder FROM page where deleted = 0 and accountid = ?', array((int)$userid));
            $row = $query->row();
            $order = $row->displayorder;
            
            if($order == 0) $order = 100;
            
            $data = array(
                'title' => $title,
                'description' => $description,
                'accountid' => (int)$userid,
                'displayorder' => ($order - 1)
            );
            
            $this->db->insert('page', $data); 
            $id = $this->db->insert_id();
            
            redirect('/main/page/' . $id . '/');
        }
    }
    
    function delete_page()
    {
        $id = $_POST['pageid'];
        $this->db->where('id', $id);
        $this->db->update('page', array('deleted'=>true));
        
        redirect('/');
    }
    
    function reorder_page()
    {
        foreach($_POST['item'] as $i=>$id )
        {
            // For our first record, $i is 0, and $id is 2.
            $data = array(
                'displayorder' => (($i + 1) * 100)
            );
            
            $this->db->update('page', $data, array('id' => $id));
        }
    }
    
    function update_item()
    {
        $this->load->helper('markdown');

        $id = $_POST['id'];
        $pageid = $_POST['pageid'];
        $title = $_POST['title'];
        $body = (isset($_POST['body'])) ? $_POST['body'] : '';
        $type = $_POST['type'];
        $itemid = 0;
        
        $newlist = ($id == '' && $type == 0) ? true : false;

        if($id != '')
        {
            $data = array(
                'pageid' => $pageid,
                'title' => $title,
                'body' => $body,
            );
            
            $this->db->where('id', $id);
            $this->db->update('item', $data); 
        }
        else
        {
            $query = $this->db->query('SELECT MIN(displayorder) as displayorder FROM item where deleted = 0 and pageid = ?', array($pageid));
            $row = $query->row();
            $order = $row->displayorder;
            
            if($order == 0) $order = 100;
            
            $data = array(
                'pageid' => $pageid,
                'title' => $title,
                'body' => $body,
                'deleted' => 0,
                'displayorder' => ($order - 1),
                'type' => $type
            );
            
            $this->db->insert('item', $data); 
            $id = $this->db->insert_id();
        }
        
        if($newlist)
        {
            $data = array(
               'itemid' => $id,
               'item' => $_POST['listitem'],
               'displayorder' => 100,
               'deleted' => 0
            );
            
            $this->db->insert('listitem', $data); 
            $itemid = $this->db->insert_id();
        }
        
        $output = array('id'=>$id,'itemid'=>$itemid,'bodyhtml'=>markdown($body),'type'=>$type);
        
        echo json_encode($output);
    }
    
    function delete_item()
    {
        $id = $_POST['id'];
        $this->db->where('id', $id);
        $this->db->update('item', array('deleted'=>true));
        echo json_encode(array('id'=>$id));
    }
    
    function reorder_item()
    {
        foreach($_POST['item'] as $i=>$id )
        {
            // For our first record, $i is 0, and $id is 2.
            $data = array(
                'displayorder' => (($i + 1) * 100)
            );
            
            $this->db->update('item', $data, array('id' => $id));
        }
    }
    
    function update_listitem()
    {
        $this->load->helper('markdown');
        
        $id = $_POST['id'];
        $itemid = $_POST['itemid'];
        $item = $_POST['item'];

        if($id != '')
        {
            $data = array(
                'itemid' => $itemid,
                'item' => $item,
            );
            
            $this->db->where('id', $id);
            $this->db->update('listitem', $data); 
        }
        else
        {
            $query = $this->db->query('SELECT MAX(displayorder) as displayorder FROM listitem where itemid = ?', array($itemid));
            $row = $query->row();
            $order = $row->displayorder;
            
            $data = array(
                'itemid' => $itemid,
                'item' => $item,
                'displayorder' => ($order + 100),
                'deleted' => 0
            );
            
            $this->db->insert('listitem', $data); 
            $id = $this->db->insert_id();
        }
        
        $output = array('id'=>$id,'itemhtml'=>markdown($item));
        
        echo json_encode($output);
    }
    
    function delete_listitem()
    {
        $id = $_POST['id'];
        $this->db->where('id', $id);
        $this->db->update('listitem', array('deleted'=>true));
        echo json_encode(array('id'=>$id));
    }
    
    function reorder_listitem()
    {
        foreach($_POST['item'] as $i=>$id )
        {
            // For our first record, $i is 0, and $id is 2.
            $data = array(
                'displayorder' => (($i + 1) * 100)
            );
            
            $this->db->update('listitem', $data, array('id' => $id));
        }
    }
}

?>