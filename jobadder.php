<?php
/*
Plugin Name: Featured Job From Jobadder
Description: featured job
Version: 1.0.0
Author: 
Author URI: 
Text Domain: my-custom-admin-page
*/
function jobadder_admin_menu() {
    add_menu_page(
            __( 'Job Adder', 'my-textdomain' ),
            __( 'Job Adder', 'my-textdomain' ),
            'manage_options',
            'job-adder',
            'job_adder_admin_page_contents',
            'dashicons-schedule',
            3
    );
    
}
add_action( 'admin_menu', 'jobadder_admin_menu' );




function job_adder_admin_page_contents(){
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
    if(!class_exists('Link_List_Table')){
       require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }
    
    class Link_List_Table extends WP_List_Table {
       /**
        * Constructor, we override the parent to pass our own arguments
        * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
        */
        function __construct() {
           parent::__construct( array(
                'singular'  => 'singular_name',     //singular name of the listed records
                'plural'    => 'plural_name',    //plural name of the listed records
                'ajax'      => false 
          ) );
        }   
            
        /*******
         * get JobAdder Data
         */
        function get_jobadderdata(){
            $jobadderArray = array();
            if( function_exists('get_jobadderads_data')) { 
                $jobadderData = get_jobadderads_data();                     
                    if(!empty($jobadderData)):
                        if(!empty($jobadderData['items'])):
                            $jobID = unserialize(get_option( 'featured_job_data' ));
                            foreach($jobadderData['items'] as $row):
                                if(!empty($jobID) && !empty($row['reference']) && in_array($row['reference'],$jobID) ):
                                    $userfav = 'Yes';
                                else:
                                    $userfav = 'No';
                                endif;
                                $jobadderArray[] = array("jobId"=>$row['reference'], "jobTitle"=>$row['title'], "userFavourite"=>$userfav);
                            endforeach;
                        endif;
                    endif;
            } else { 
            // do nothing
            } 
            return $jobadderArray;
        }
        /*******
         * add checkbox for bulk action
         */
        function column_cb( $item ) {
            $jobID = unserialize(get_option( 'featured_job_data' ));
            return sprintf(		
            '<label class="screen-reader-text" for="user_' . $item['jobId'] . '">' . sprintf( __( 'Select %s' ), $item['user_login'] ) . '</label>'
            . "<input type='checkbox' name='users[]' id='user_{$item['jobId']}' value='{$item['jobId']}' ".(in_array($item['jobId'], $jobID) ? "checked" : "")."/>"					
            );
        }
        /***************
         * set column
         */
        function get_columns(){
            $table_columns = array(
                'cb'		=> '<input type="checkbox" />', // to display the checkbox.			 
                'jobId'	=> __( 'Job Id', $this->plugin_text_domain ),
                'jobTitle'	=> __( 'Job Title', $this->plugin_text_domain ),
                'userFavourite'	=> __( 'Featured', $this->plugin_text_domain ),	
            );		
            return $table_columns;
        }
        
        /*****
         * prepare Item
         */       
        function prepare_items() {
            global $wpdb, $_wp_column_headers;
            $screen = get_current_screen();
            $columns = $this->get_columns();
            $hidden = array();
            $sortable =array();
            $this->_column_headers = array($columns, $hidden, $sortable);
            $this->process_bulk_action();
            $this->items = $this->get_jobadderdata();
        }
        /*******
         * set featured action for job post
         */
        function get_bulk_actions() {
            $actions = array(
            'feature' => 'Mark As A Featured/ Non Featured'
            );
            return $actions;
            }
        /*********
         * Select checkbox and send to jobadder API
         */
        function process_bulk_action(){            
            if ( ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'feature' ) || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'feature' ) ) {                
                $jobadderUser = $_REQUEST['users'];
                if(!empty($jobadderUser)):
                    $dataJobID = array();
                    foreach($jobadderUser as $row):
                        $dataJobID[] = $row; // store jobid into array
                    endforeach;
                        if(!empty($dataJobID)):
                            // lets get back what we added
                            $featuredJobArray = get_option( 'featured_job_data' ); // fetch data from WP_Option table
                            if(is_bool($featuredJobArray)):
                                add_option( 'featured_job_data', $dataJobID ); // add new option name in WP_Option table
                            else:
                                update_option( 'featured_job_data', serialize( $dataJobID ) ); // Updated option value in WP_option table
                            endif;
                        endif;   
                    if(!empty($dataJobID) &&  count($dataJobID) > 0):
                        echo '<div class="notice notice-success is-dismissible"><p>'.count($jobadderUser).' jobs added as a feature in the job adder account...</p></div>';
                    endif;
                else:
                    update_option( 'featured_job_data', '' );                       
                    echo '<div class="notice notice-success is-dismissible"><p>Jobs removed as a feature in the job adder account...</p></div>';
                endif;                
                return;                
            }
        }
        
        /***
         * show default column
         */
        function column_default( $item, $column_name ) {
            switch( $column_name ) {
            case 'jobId':
            case 'jobTitle':
            case 'userFavourite':
            return $item[ $column_name ];
            default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
            }
        }
    }
        
        ?>
        <form id="jobadder-list" method="get">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php 
				$obj = new Link_List_Table;
                $obj->prepare_items();
                $obj->display();
			?>					
		</form>
        <?php

}
function register_my_plugin_scripts() {
    wp_register_style( 'my-plugin', plugins_url( 'ddd/css/plugin.css' ) );
    wp_register_script( 'my-plugin', plugins_url( 'ddd/js/plugin.js' ) );
}
//add_action( 'admin_enqueue_scripts', 'register_my_plugin_scripts' );
function load_my_plugin_scripts( $hook ) {
// Load only on ?page=sample-page
    if( $hook != 'toplevel_page_job-adder' ) {
    return;
    }
// Load style & scripts.
    wp_enqueue_style( 'my-plugin' );
    wp_enqueue_script( 'my-plugin' );
}
//add_action( 'admin_enqueue_scripts', 'load_my_plugin_scripts' );
