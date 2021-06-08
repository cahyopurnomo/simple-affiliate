<?php

if( !class_exists('WP_List_Table') ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class SA_Member_List extends WP_List_Table {
    function __construct() {
        parent::__construct( array( 
                'singular' => 'member', 
                'plural' => 'members',
                'ajax' => false
            ) );
    }

    function get_data($search = '') {
        global $wpdb;

        $table_name = $wpdb->prefix.'users';
        $table_users_details = $wpdb->prefix.'users_details';
        
        $query = "SELECT u.*, ud.* FROM $table_name u ";
        $query .= "JOIN $table_users_details ud ON ud.user_id = u.ID";

        if( !empty($search) ){
            $query .= " WHERE (u.user_login LIKE '%{$search}%' OR u.display_name LIKE '%{$search}%' OR u.user_email LIKE '%{$search}%' OR u.user_nicename LIKE '%{$search}%' )";
        }

        $results = $wpdb->get_results($query, 'ARRAY_A');
        
        return $results;
    }

    function get_columns() {
        $columns = array(
            // 'cb'                => '<input type="checkbox" />',
            'cb'                => '', //remove checkbox in table header
            'ID'                => 'ID', 
            'user_login'        => 'Username', 
            'user_nicename'     => 'Nicename', 
            'user_email'        => 'Email', 
            'user_registered'   => 'Registered', 
            'display_name'      => 'Display Name',
            'user_city'         => 'City',
            'user_province'     => 'Province',
            'user_phone'        => 'Phone',
            'user_level'        => 'Level'
        );
        
        return $columns;
    }

    function prepare_items($search = '') {
        $columns    = $this->get_columns();
        $hidden     = $this->get_hidden_columns();
        $sortable   = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $data = $this->get_data($search);
        
        //formating date of user_registered
        foreach ($data as $key => $row) {
            if( $row["user_registered"] ){
                $data[$key]["user_registered"] = date("d-m-Y", strtotime($row["user_registered"]));
            }
        }

        usort($data, array(&$this, 'usort_reorder'));
        
        // pagination
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $this->set_pagination_args( array('total_items' => $total_items, 'per_page' => $per_page) );
        $data = array_slice($data,(($current_page-1)*$per_page), $per_page);

        $this->items = $data;
    }

    function column_default($item, $column_name) {
        switch( $column_name ) {
            case 'ID':
            case 'user_login':
            case 'user_nicename':
            case 'user_email':
            case 'user_registered':
            case 'user_city':
            case 'user_province':
            case 'user_phone':
            case 'user_level':
            case 'display_name':
                return $item[ $column_name ];
            default:
                return print_r($item, true);
        }
    }

    function get_hidden_columns() {
        $hidden_columns = array(
            'ID'              => array('ID', false),
            'user_login'      => array('user_login', false),
            'user_nicename'   => array('user_nicename', false),
            'user_email'      => array('user_email', false),
            'user_registered' => array('user_registered', false),
            'display_name'    => array('display_name', false),
            'user_city'       => array('user_city', false),
            'user_province'   => array('user_province', false),
            'user_phone'      => array('user_phone', false),
            'user_level'      => array('user_level', false)
        );

        return $hidden_columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'ID'              => array('ID', false),
            'user_login'      => array('user_login', false),
            'user_nicename'   => array('user_nicename', false),
            'user_email'      => array('user_email', false),
            'user_registered' => array('user_registered', false),
            'display_name'    => array('display_name', false),
            'user_city'       => array('user_city', false),
            'user_province'   => array('user_province', false),
            'user_phone'      => array('user_phone', false),
            'user_level'      => array('user_level', false)
        );

        return $sortable_columns;
    }

    function usort_reorder($a, $b) {
        $orderby = ( !empty($_GET['orderby']) ) ? $_GET['orderby'] : 'ID';
        $order = ( !empty($_GET['order']) ) ? $_GET['order'] : 'asc';
        $result = strcmp($a[$orderby], $b[$orderby]);

        return ($order === 'asc') ? $result : -$result;
    }

    function column_user_login($item) {
        $actions = array(
            'edit'      => sprintf('<a href="?page=members_form&id=%s">%s</a>', $item['ID'], __('Edit', 'sa_simple_affiliate')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['ID'], __('Delete', 'custom_table_example')),
        );

        return sprintf('%1$s %2$s', $item['user_login'], $this->row_actions($actions) );
    }

    // no need bulk action, only need single edit/delete
    function get_bulk_actions() {
        $actions = array('delete' => 'Delete');
        return $actions;
    }

    function process_bulk_action() {
        $member_obj->current_action(); die();
        global $wpdb;
        $table_name = $wpdb->prefix . 'users';
        $table_users_details = $wpdb->prefix . 'users_details';

        if ( 'delete' === $this->current_action() ) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();

            if ( is_array($ids) )
                $ids = implode(',', $ids);
            
            if ( !empty($ids) ) {
                $wpdb->query("DELETE FROM $table_name WHERE ID IN($ids)");
                $wpdb->query("DELETE FROM $table_users_details WHERE user_id IN($ids)");
            }
        }
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="user[]" value="%s" />', $item['ID']);
    }

} //class