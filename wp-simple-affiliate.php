<?php

/*
Plugin Name: WP Simple Affliate
Plugin URL: http://local.worpress
Description: Plugin Web Replika Sederhana
Version: 1.0.0
Author: Cahyo Purnomo
Author URI: http://local.worpress
License: GPLv2 or later
Text Domain: WP Simple Affiliate
*/


/** START ADMIN MENU */

add_action("admin_menu", "sa_menu_settings");

function sa_menu_settings() {
    add_menu_page("Simple Affiliate - SA","Simple Affiliate","manage_options","members","sa_render_list_page","dashicons-groups",80);
    add_submenu_page("members","Member List","All Member","manage_options","members","sa_render_list_page");
    add_submenu_page("members","Add New Member","Add Member","manage_options","members_form","member_form_page_handler");

    add_submenu_page("members","Email List","Email Blast","manage_options","emails","sa_render_email_list_page");
    add_submenu_page("members","Add New Email","Add New Email","manage_options","email_form","email_form_page_handler");
}

/** END ADMIN MENU */


/** START MEMBER HANDLE */

if( !class_exists('SA_Member_List') ) {
    require_once('class-sa-member-list.php');
}

function sa_render_list_page() {

    $member_obj = new SA_Member_List();
    echo '<div class="wrap"><h2>List of Member
    <a class="add-new-h2" href="admin.php?page=members_form">Add New</a></h2>';
    
    $message = '';

    if ( 'delete' === $member_obj->current_action() ) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Member deleted: %d', 'sa_simple_affiliate'), is_array($_REQUEST['id']) ? count($_REQUEST['id']) : $_REQUEST['id']) . '</p></div>';
    }

    echo '<form method="post" >';

    if( isset($_POST['s']) ){
        $member_obj->prepare_items($_POST['s']);
    } else {
        $member_obj->prepare_items();
    }

    $member_obj->search_box('Search', 'search_id');
    $member_obj->display();

    echo '</div>'; 
} // sa_render_list_page

function member_form_page_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'users';
    $table_users_details = $wpdb->prefix . 'users_details';

    $message = '';
    $notice = '';

    $default = array(
        'id'                => 0,
        'user_login'        => '',
        'user_pass'         => '',
        'user_nicename'     => '',
        'user_email'        => '',
        'display_name'      => '',
        'user_registered'   => date('Y-m-d h:i:s')
    );

    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__) )) {
        $item = shortcode_atts($default, $_REQUEST);
        $item_valid = input_member_validation($item);
        
        if ( $item_valid === true ){
            if ( $item['id'] == 0 ) {
                $user_exist = existing_data_validation($item);

                if ( $user_exist === false ) {
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;

                    if ( $result ) {
                        $message = __('Member was successfully saved', 'sa_simple_affiliate');
                        
                        $to = $item['user_email'];
                        $subject = "Selamat Bergabung di SatuKaki.Net";
                        $headers = array('From: Admin SatuKaki.Net <c.purnomo@gmail.com>', 'Content-Type: text/html; charset=UTF-8');
                        $attachment = array();
                        $body = 
                                $item['display_name'].", selamat datang di SatuKaki.Net.<br \>
                                <br \><br \>
                                Salam Sukses,<br \>
                                Admin SatuKaki.Net.";
                    
                        wp_mail($to, $subject, $body, $headers, $attachment);
                    } else {
                        $notice = __('There was an error while saving member', 'sa_simple_affiliate');
                    }
                } else {
                    $notice = __($user_exist, 'sa_simple_affiliate');
                }
            } else {
                $result = $wpdb->update($table_name, $item, array('ID' => $item['id']));

                if ( $result ) {
                    $message = __('Member was successfully updated', 'sa_simple_affiliate');
                    // wp_redirect('admin.php?page=members');
                } else {
                    $notice = __('There was an error while updating member', 'sa_simple_affiliate');
                }
            }
        } else {
            $notice = $item_valid;
        }
    } else {
        $item = $default;

        if ( isset($_REQUEST['id']) ) {
            $sql = "SELECT u.ID, u.user_login, u.user_email, u.user_nicename, u.display_name, u.user_sponsor_id, sp.display_name as sponsor_display_name, ";
            $sql .= "ud.user_city, ud.user_province, user_phone, user_level ";
            $sql .= "FROM $table_name sp ";
            $sql .= "JOIN $table_name u ON sp.ID = u.user_sponsor_id ";
            $sql .= "JOIN $table_users_details ud ON ud.user_id = u.ID ";
            $sql .= "WHERE u.ID = %d";
            $item = $wpdb->get_row($wpdb->prepare($sql, $_REQUEST['id']), ARRAY_A);
            $item['id'] = $item['ID'];

            if ( !$item ) {
                $item = $default;
                $notice = __('Member not found', 'sa_simple_affiliate');
            }
        }
    }

    add_meta_box('members_form_meta_box', 'Data Member', 'members_form_meta_box_handler', 'member', 'normal', 'default');

?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Member', 'sa_simple_affiliate')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=members');?>"><?php _e('back to list', 'SA_Simple_Affiliate')?></a>
        </h2>

        <?php if (!empty($notice)): ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
            <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
            <input type="hidden" name="id" value="<?php echo isset($item['id']) ? $item['id'] : ''; ?>"/>
            <input type="hidden" name="user_sponsor_id" value="<?php echo isset($item['user_sponsor_id']) ? $item['user_sponsor_id'] : 1; ?>"/>
            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <?php /* And here we call our custom meta box */ ?>
                        <?php do_meta_boxes('member', 'normal', $item); ?>
                        <?php $action_label = !empty($item['id']) && $item['id'] > 0 ? 'Update' : 'Save'; ?>
                        <input type="submit" value="<?php _e($action_label, 'sa_simple_affiliate')?>" id="submit" class="button-primary" name="submit">
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php
} //member_form_page_handler

function members_form_meta_box_handler($item){
    ?>
    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tbody>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="sponsor_display_name"><?php _e('Sponsor', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="sponsor_display_name" name="sponsor_display_name" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['sponsor_display_name'] ) ) ? esc_attr( wp_unslash( $item['sponsor_display_name'] ) ) : 'Taufik Kesuma'; ?>" size="100" class="code" placeholder="<?php _e('Sponsor', 'sa_simple_affiliate')?>" required disabled>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_username"><?php _e('Username', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_username" name="user_login" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['user_login'] ) ) ? esc_attr( wp_unslash( $item['user_login'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Username', 'sa_simple_affiliate')?>" required <?php echo isset($item['id']) && $item['id'] > 0 ? 'disabled_' : ''; ?>>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_pass"><?php _e('Password', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_pass" name="user_pass" type="password" style="width: 95%" value="<?php echo ( ! empty( $item['user_pass'] ) ) ? esc_attr( wp_unslash( $item['user_pass'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Password', 'sa_simple_affiliate')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_nicename"><?php _e('Nicename', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_nicename" name="user_nicename" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['user_nicename'] ) ) ? esc_attr( wp_unslash( $item['user_nicename'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Nicename', 'sa_simple_affiliate')?>" required <?php echo isset($item['id']) && $item['id'] > 0 ? 'disabled_' : ''; ?>>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_email"><?php _e('E-Mail', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_email" name="user_email" type="email" style="width: 95%" value="<?php echo ( ! empty( $item['user_email'] ) ) ? esc_attr( wp_unslash( $item['user_email'] ) ) : ''; ?>" size="50" class="code" placeholder="<?php _e('Your E-Mail', 'sa_simple_affiliate')?>" required <?php echo isset($item['id']) && $item['id'] > 0 ? 'disabled_' : ''; ?>>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="display_name"><?php _e('Display Name', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="display_name" name="display_name" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['display_name'] ) ) ? esc_attr( wp_unslash( $item['display_name'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Display Name', 'sa_simple_affiliate')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_phone"><?php _e('Phone', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_phone" name="user_phone" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['user_phone'] ) ) ? esc_attr( wp_unslash( $item['user_phone'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Phone', 'sa_simple_affiliate')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_city"><?php _e('City', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_city" name="user_city" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['user_city'] ) ) ? esc_attr( wp_unslash( $item['user_city'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('City', 'sa_simple_affiliate')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_province"><?php _e('Province', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_province" name="user_province" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['user_province'] ) ) ? esc_attr( wp_unslash( $item['user_province'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Province', 'sa_simple_affiliate')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_level"><?php _e('Level', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="user_level" name="user_level" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['user_level'] ) ) ? esc_attr( wp_unslash( $item['user_level'] ) ) : 'SILVER'; ?>" size="100" class="code" placeholder="<?php _e('Level', 'sa_simple_affiliate')?>" required>
            </td>
        </tr>
        </tbody>
    </table>
    <?php
} // members_form_meta_box_handler

/** END MEMBER HANDLE */


/** START EMAIL HANDLE */

if( !class_exists('SA_Email_List') ) {
    require_once('class-sa-email-list.php');
}

function sa_render_email_list_page() {
    $email_obj = new SA_Email_List();
    echo '<div class="wrap"><h2>Follow Up Email List
    <a class="add-new-h2" href="admin.php?page=email_form">Add New Email</a></h2>';
    
    $message = '';

    if ( 'delete' === $email_obj->current_action() ) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Email deleted: %d', 'sa_simple_affiliate'), is_array($_REQUEST['id']) ? count($_REQUEST['id']) : $_REQUEST['id']) . '</p></div>';
    }

    echo '<form method="post" >';

    if( isset($_POST['s']) ){
        $email_obj->prepare_items($_POST['s']);
    } else {
        $email_obj->prepare_items();
    }

    $email_obj->search_box('Search', 'search_id');
    
    $email_obj->display();

    echo '</div>'; 

    
} // sa_render_email_list_page

function email_form_page_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'blast_email';

    $message = '';
    $notice = '';

    $default = array(
        'id'            => 0,
        'day'           => '',
        'user_level'    => '',
        'email_msg'     => '',
    );

    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__) )) {
        $item = shortcode_atts($default, $_REQUEST);
        $item_valid = input_email_validation($item);
        
        if ( $item_valid === true ){
            if ( $item['id'] == 0 ) {
                $user_exist = existing_data_email_validation($item);

                if ( $user_exist === false ) {
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;

                    if ( $result ) {
                        $message = __('Email content was successfully saved', 'sa_simple_affiliate');
                    } else {
                        $notice = __('There was an error while saving email content', 'sa_simple_affiliate');
                    }
                } else {
                    $notice = __($user_exist, 'sa_simple_affiliate');
                }
            } else {
                $result = $wpdb->update($table_name, $item, array('ID' => $item['id']));

                if ( $result ) {
                    $message = __('Email Content was successfully updated', 'sa_simple_affiliate');
                } else {
                    $notice = __('There was an error while updating email content', 'sa_simple_affiliate');
                }
            }
        } else {
            $notice = $item_valid;
        }
    } else {
        $item = $default;

        if ( isset($_REQUEST['id']) ) {
            $sql = "SELECT * FROM $table_name";
            $sql .= "WHERE u.id = %d";
            $item = $wpdb->get_row($wpdb->prepare($sql, $_REQUEST['id']), ARRAY_A);
            $item['id'] = $item['id'];

            if ( !$item ) {
                $item = $default;
                $notice = __('Email content not found', 'sa_simple_affiliate');
            }
        }
    }

    add_meta_box('emails_form_meta_box', 'Data Email', 'emails_form_meta_box_handler', 'email', 'normal', 'default');

?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Email', 'sa_simple_affiliate')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=emails');?>"><?php _e('back to list', 'SA_Simple_Affiliate')?></a>
        </h2>

        <?php if (!empty($notice)): ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
            <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
            <input type="hidden" name="id" value="<?php echo isset($item['id']) ? $item['id'] : ''; ?>"/>
            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <?php /* And here we call our custom meta box */ ?>
                        <?php do_meta_boxes('email', 'normal', $item); ?>
                        <?php $action_label = !empty($item['id']) && $item['id'] > 0 ? 'Update' : 'Save'; ?>
                        <input type="submit" value="<?php _e($action_label, 'sa_simple_affiliate')?>" id="submit" class="button-primary" name="submit">
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php
} // email_form_page_handler

function emails_form_meta_box_handler($item){
    ?>
    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tbody>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="day"><?php _e('Day', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <input id="day" name="day" type="number" style="width: 95%" value="<?php echo ( ! empty( $item['day'] ) ) ? esc_attr( wp_unslash( $item['day'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Day', 'sa_simple_affiliate')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="user_level"><?php _e('Member Level', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <?php  
                $selected_silver = !empty( $item['user_level'] ) && $item['user_level'] == 'SILVER' ? 'selected' : ''; 
                $selected_premium = !empty( $item['user_level'] ) && $item['user_level'] == 'premium' ? 'selected' : ''; 
                ?>
                <select name="user_level">
                    <option value="SILVER" <?=$selected_silver?>>SILVER</option>
                    <option value="PREMIUM" <?=$selected_premium?>>PREMIUM</option>
                </select>
                <!-- <input id="user_level" name="user_level" type="text" style="width: 95%" value="<?php echo ( ! empty( $item['user_level'] ) ) ? esc_attr( wp_unslash( $item['user_level'] ) ) : ''; ?>" size="100" class="code" placeholder="<?php _e('Member Level', 'sa_simple_affiliate')?>" required> -->
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="email_msg"><?php _e('Email Message', 'sa_simple_affiliate')?></label>
            </th>
            <td>
                <textarea id="email_msg" name="email_msg" rows="15" placeholder="<?php _e('Email Content', 'sa_simple_affiliate')?>" required>
                <?php echo ( ! empty( $item['email_msg'] ) ) ? esc_attr( wp_unslash( $item['email_msg'] ) ) : ''; ?>
                </textarea>
                
            </td>
        </tr>
        </tbody>
    </table>
    <?php
} // emails_form_meta_box_handler

/** END EMAIL HANDLE */




/** START GLOBAL FUCNTION */

function input_email_validation($item){
    global $wpdb;

    $table_name = $wpdb->prefix . 'blast_email';

    $messages = array();

    if ( empty($item['day']) )
        $messages[] = __('Day is required', 'sa_simple_affiliate');
    
    if ( empty($item['user_level']) )
        $messages[] = __('Member Level is required', 'sa_simple_affiliate');
    
    if ( empty($item['email_msg']) )
        $messages[] = __('Email Message is required', 'sa_simple_affiliate');
    
    if ( empty($messages) )
        return true;
    
    return implode('<br />', $messages);
}

function existing_data_email_validation($item){
    global $wpdb;

    $table_name = $wpdb->prefix . 'blast_email';

    $messages = array();

    $day = $item['day'];
    $user_level = $item['user_level'];

    $check_row = $wpdb->get_row("SELECT * FROM $table_name WHERE day = '$day' AND user_level = '$user_level'", ARRAY_A);

    if ( isset($check_row['day']) && $check_row['day'] == $day )
        $messages[] = __('Day & Member Level sudah ada.', 'sa_simple_affiliate');
    
    if ( empty($messages) )
        return false;
    
    return implode('<br />', $messages);
}

function input_member_validation($item){
    global $wpdb;

    $table_name = $wpdb->prefix . 'users';

    $messages = array();

    if ( empty($item['user_login']) )
        $messages[] = __('Username is required', 'sa_simple_affiliate');
    
    if ( empty($item['user_pass']) )
        $messages[] = __('Password is required', 'sa_simple_affiliate');
    
    if ( empty($item['user_nicename']) )
        $messages[] = __('Nicename is required', 'sa_simple_affiliate');
    
    if ( empty($item['user_email']) )
        $messages[] = __('Email is required', 'sa_simple_affiliate');
    
    if ( empty($item['display_name']) )
        $messages[] = __('Display Name is required', 'sa_simple_affiliate');
    
    if ( empty($messages) )
        return true;
    
    return implode('<br />', $messages);
}

function existing_data_validation($item){
    global $wpdb;

    $table_name = $wpdb->prefix . 'users';

    $messages = array();

    $email = $item['user_email'];
    $username = $item['user_login'];

    $check_user_email = $wpdb->get_row("SELECT * FROM $table_name WHERE user_email = '$email'", ARRAY_A);
    $check_user_login = $wpdb->get_row("SELECT * FROM $table_name WHERE user_login = '$username'", ARRAY_A);

    if ( isset($check_user_email['user_email']) && $check_user_email['user_email'] == $item['user_email'] )
        $messages[] = __('Email sudah terdaftar.', 'sa_simple_affiliate');
    
    if ( isset($check_user_login['user_login']) && $check_user_login['user_login'] == $item['user_login'] )
        $messages[] = __('Username sudah terdaftar.', 'sa_simple_affiliate');
    
    if ( empty($messages) )
        return false;
    
    return implode('<br />', $messages);
}

/** END GLOBAL FUCNTION */


/** START CRON FOLLOW UP EMAIL */
function sent_email_interval($schedules) {
    $schedules['one-minutes'] = array(
        'interval' => 60,
        'display'  => 'Every Minutes'
    );

    return $schedules;
}

add_filter('cron_schedules', 'sent_email_interval');

if ( !wp_next_scheduled('sent_email_hook') ) {
    wp_next_scheduled(time(), 'one-minutes', 'sent_email_hook');
}

// add_action('sent_email_hook', 'send_email_follow_up');
add_action('init', 'send_email_follow_up');

function send_email_follow_up() {
    global $wpdb, $email_blast_id, $user_id, $send_date;

    // get data user except admin
    $table_users = $wpdb->prefix . 'users';
    $table_users_details = $wpdb->prefix . 'users_details';
    $table_blast_log = $wpdb->prefix . 'blast_log';
    $table_blast_email = $wpdb->prefix . 'blast_email';

    $sql = "SELECT * FROM $table_blast_email WHERE user_level='SILVER' ORDER BY id DESC LIMIT 1";
    $data_silver = $wpdb->get_row($sql, ARRAY_A);
    $day_silver = $data_silver['day'];

    $sql = "SELECT * FROM $table_blast_email WHERE user_level='PREMIUM' ORDER BY id DESC LIMIT 1";
    $data_premium = $wpdb->get_row($sql, ARRAY_A);
    $day_premium = $data_premium['day'];

    $sql = "SELECT u.*, ud.user_level ";
    $sql .= "FROM $table_users u ";
    $sql .= "JOIN $table_users_details ud ON ud.user_id = u.ID ";
    $sql .= "WHERE u.user_login != 'admin'";
    $users = $wpdb->get_results($sql, ARRAY_A);

    foreach( $users as $key => $user ) {
        //get last log for users
        $sql1 = "SELECT bl.*, be.id as email_id, be.day, be.user_level FROM $table_blast_log bl ";
        $sql1 .= "JOIN $table_blast_email be ON be.id = bl.email_blast_id ";
        $sql1 .= "WHERE bl.user_id=" . $user['ID'] . " AND be.user_level='" . $user['user_level'] . "' ORDER BY id DESC LIMIT 1";
        $last_log = $wpdb->get_row($sql1, ARRAY_A);
        $count_last_log = $wpdb->num_rows;
        $day = $user['user_level'] == 'SILVER' ? $day_silver : $day_premium;
        
        if ( $last_log['day'] < $day ) {
            $current_day = $count_last_log == 0 ? 1 : $last_log['day'] + 1;
            $sql_be = "SELECT * FROM $table_blast_email WHERE user_level='" . $user['user_level'] . "' AND day='" . $current_day. "' LIMIT 1";
            $blast_email = $wpdb->get_row($sql_be, ARRAY_A);

            $to = $user['user_email'];
            $subject = "Follow Up Email Hari - " . $blast_email['day'];
            $headers = array('From: Admin SatuKaki.Net <admin@satukaki.net>', 'Content-Type: text/html; charset=UTF-8');
            $body = $blast_email['email_msg'];
            $attachment = array();
            wp_mail($to, $subject, $body, $headers, $attachment);

            //insert log
            $data = array(
                'email_blast_id' => $blast_email['id'],
                'user_id' => $user['ID'],
                'sent_date' => date('Y-m-d h:i:s')
            );
            
            $wpdb->insert($table_blast_log, $data);
        } //endif
    } //foreach
}

/** END CRON FOLLOW UP EMAIL */

function wp5673fg_redirect()
{
    if (is_user_logged_in()) {
        // echo 'loggedin';
    } else {
        wp_safe_redirect('https://www.google.co.id');
    }
}
add_action('init', 'wp5673fg_redirect');