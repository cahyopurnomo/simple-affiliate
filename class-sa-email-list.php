<?php

if( !class_exists('WP_List_Table') ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class SA_Email_List extends WP_List_Table {
    function __construct() {
        parent::__construct( array( 
                'singular' => 'email', 
                'plural' => 'emails',
                'ajax' => false
            ) );
    }

    function get_data($search = '') {
        global $wpdb;

        $table_name = $wpdb->prefix.'blast_email';
        
        $query = "SELECT * FROM $table_name";

        if( !empty($search) ){
            $query .= " WHERE (day LIKE '%{$search}%' OR user_level LIKE '%{$search}%' OR email_msg LIKE '%{$search}%' )";
        }

        $query .= " ORDER BY id, user_level DESC";

        $results = $wpdb->get_results($query, 'ARRAY_A');
        // echo $wpdb->last_query; die();
        return $results;
    }

    function get_columns() {
        $columns = array(
            // 'cb'                => '<input type="checkbox" />',
            'cb'            => '', //remove checkbox in table header
            'id'            => 'ID', 
            'day'           => 'Day', 
            'user_level'    => 'Level', 
            'email_msg'     => 'Email', 
        );
        
        return $columns;
    }

    function prepare_items($search = '') {
        $columns    = $this->get_columns();
        $hidden     = $this->get_hidden_columns();
        $sortable   = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $data = $this->get_data($search);

        foreach ($data as $key => $row) {
            if( $row["day"] ){
                $data[$key]["day"] = 'Day ' . $row["day"];
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
            case 'id':
            case 'day':
            case 'user_level':
            case 'email_msg':
                return $item[ $column_name ];
            default:
                return print_r($item, true);
        }
    }

    function get_hidden_columns() {
        $hidden_columns = array(
            'id'            => array('id', false),
            'day'           => array('day', false),
            'user_level'    => array('user_level', false),
            'email_msg'     => array('email_msg', false),
        );

        return $hidden_columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'id'            => array('id', true),
            'day'           => array('day', true),
            'user_level'    => array('user_level', true),
            'email_msg'     => array('email_msg', false),
        );

        return $sortable_columns;
    }

    function usort_reorder($a, $b) {
        $orderby = ( !empty($_GET['orderby']) ) ? $_GET['orderby'] : 'id';
        $order = ( !empty($_GET['order']) ) ? $_GET['order'] : 'asc';
        $result = strcmp($a[$orderby], $b[$orderby]);

        return ($order === 'asc') ? $result : -$result;
    }

    function column_day($item) {
        $actions = array(
            'edit'      => sprintf('<a href="?page=email_form&id=%s">%s</a>', $item['id'], __('Edit', 'sa_simple_affiliate')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'custom_table_example')),
        );

        return sprintf('%1$s %2$s', $item['day'], $this->row_actions($actions) );
    }

    // no need bulk action, only need single edit/delete
    function get_bulk_actions() {
        $actions = array('delete' => 'Delete');
        return $actions;
    }

    function process_bulk_action() {
        $member_obj->current_action(); die();
        global $wpdb;
        $table_name = $wpdb->prefix . 'blast_email';

        if ( 'delete' === $this->current_action() ) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();

            if ( is_array($ids) )
                $ids = implode(',', $ids);
            
            if ( !empty($ids) ) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

} //class