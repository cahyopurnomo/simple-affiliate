<?php

if( !class_exists('WP_List_Table') ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class SA_Simple_Affiliate extends WP_List_Table {
    function __construct() {
        global $status, $page;

        parent::__construct( 
            array( 'singular' => 'member', 'plural' => 'members', 'ajax' => false )
        );
    }

    public static function get_users() {
        global $wpdb;

        $table_name = $wpdb->prefix.'users';
        $results = $wpdb->get_results("SELECT * FROM $table_name", 'ARRAY_A');
        
        return $results;
    }

    function get_columns() {
        $columns = array(
            'ID'            => 'ID', 
            'user_login'    => 'Username', 
            'user_email'    => 'Email', 
            'display_name'  => 'Surename'
        );
        
        return $columns;
    }

    function prepare_items() {
        global $wpdb;

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $this->_columns_headers = array($columns, $hidden, $sortable);
        $this->items = $this->get_users();
    }

    function get_hidden_columns() {
        // $sortable_columns = array(
        //     'ID'              => array('ID', false),
        //     'user_login'      => array('user_login', false),
        //     'user_email'      => array('user_email', false),
        //     'display_name'    => array('display_name', false)
        // );

        $hidden_columns = array();

        return $hidden_columns;
    }

    function get_sortable_columns() {
        // $sortable_columns = array(
        //     'ID'              => array('ID', false),
        //     'user_login'      => array('user_login', false),
        //     'user_email'      => array('user_email', false),
        //     'display_name'    => array('display_name', false)
        // );

        $sortable_columns = array();

        return $sortable_columns;
    }

    function column_default($item, $column_name) {
        switch( $column_name ) {
            case 'ID':
            case 'user_login':
            case 'user_email':
            case 'display_name':
                return $item[ $column_name ];
            default:
                return print_r($item, true);
        }
    }


} //class