<?php
/**
 * Plugin Name: Ump Blog Setting
 * Plugin URI: http://www.restulestari.com
 * Description: This plugin is to setting the blog by blog admin
 * Version: 1.0.0
 * Author: Ghazali Tajuddin
 * Author URI: http://www.ghazalitajuddin.com
 * License: GPL2
 * 
 * https://codex.wordpress.org/Function_Reference/plugins_url
 * https://developer.wordpress.org/reference/functions/add_menu_page/
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Start Class
if ( ! class_exists( 'UmpBlogSetting' ) ) {

class UmpBlogSetting {

    private $jabatan;

    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    function admin_menu() {
      
    add_menu_page( "Ump Blog Setting Plugin", "Ump Blog Setting", 'manage_options', 'Ump_Blog_Setting', array($this, 'settings_page'));

    }

    function  settings_page() {

        if (isset($_POST['jabatan'])) {    
            update_blog_option(get_current_blog_id(),'department',sanitize_text_field($_POST['jabatan']));
            $this->jabatan = $_POST['jabatan'];
        }else{

            if(get_blog_option(get_current_blog_id(),'department')){    
        
                 $this->jabatan = get_blog_option(get_current_blog_id(),'department');

            }else{
                
                update_blog_option(get_current_blog_id(),'department','');   
                $this->jabatan = '';
            }
        }

        ?>
             <div class="wrap">
                <h1>My Settings (Blog ID : <?php echo get_current_blog_id(); ?> )</h1>
                <form method="post">
                
                Jabatan 
            
                <?php 


                    $json = file_get_contents(plugins_url( 'jabatan.json', __FILE__ )); 
                    $data = json_decode($json,true);
                    $jabatans=$data['JABATAN'];

                    printf('<select id="jabatan" name="jabatan">');
                        foreach ($jabatans as $jabatan)
                        {
                        printf(
                            '<option value="'.$jabatan['ptj_acro'].'" '. ( $this->jabatan == $jabatan['ptj_acro'] ? "selected" : "" ).'  >'.esc_attr($jabatan['ptj_name']).'</option>'
                        );
                        
                        }
                    printf('</select>');


                ?>


                 <input type="submit" value="Save" class="button button-primary button-large">

                </form>
            </div>
        <?php

    } // End Setting Page
}// End Class

}// End Iff

if( is_admin() )
    $my_settings_page = new UmpBlogSetting();


?>