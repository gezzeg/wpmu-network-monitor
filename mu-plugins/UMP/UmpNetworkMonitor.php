<?php
/**
 * Plugin Name: Ump Network Monitor
 * Plugin URI: http://restulestari.com
 * Description: This plugin shows Total Posts & Draft Posts for the Network Admin
 * Version: 1.0.0
 * Author: Ghazali Tajuddin
 * Author URI: http://www.ghazalitajuddin.com
 * License: GPL2
 * 
 * https://premium.wpmudev.org/forums/topic/customizing-the-network-admin-menu
 * https://codex.wordpress.org/Plugin_API/Action_Reference/network_admin_menu
 * https://stackoverflow.com/questions/4586835/how-to-pass-extra-variables-in-url-with-wordpress
 * https://code.tutsplus.com/articles/how-to-include-javascript-and-css-in-your-wordpress-themes-and-plugins--wp-24321
 * http://www.chartjs.org/docs/latest/
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Start Class
if ( ! class_exists( 'UmpNetworkMonitor' ) ) {


class UmpNetworkMonitor {

//Declare var for store data temporary
private $data = array();
private $request_jabatan;
private $blogs;
private $total_blog;
private $total_publish;
private $total_draft;
private $total_jabatan_publish_post;
private $total_jabatan_draft_post;
private $publish_posts;
private $draft_posts;
private $publish_network;
private $draft_network;
private $jabatan_lists;

//private $total_publish_post;


////////////////////////////////////////////////////////////////

    function __construct() {
      // add_action( 'admin_menu', array( $this, 'admin_menu' ) );
      //Add admin menu
      // add_action('network_admin_menu', 'add_network_menu_1234');
      add_action('network_admin_menu', array($this,'add_network_menu_rl'));

      //Get all blogs info
      $this->blogs =  $this->getBlog();
      $this->script();

      

      //check if GET access
       if(isset($_GET['jabatan'])){
        $this->request_jabatan = filter_input( INPUT_GET, "jabatan", FILTER_SANITIZE_STRING );
        }

      //Populate all variables
        $this->total_jabatan_draft_post = 0;
        $this->total_jabatan_publish_post = 0;

       // add_action( 'admin_menu', array( $this, 'admin_menu' ) );
       
    }

    ////////////////////////////////////////////////////////////////
    //Add Script
    
    function script(){

    // wp_register_script( 'chart-bundle', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.bundle.min.js');
    
    wp_deregister_script( 'chart-bundle' );
    wp_register_script( 'chart-bundle', plugins_url( '/js/Chart.bundle.min.js', __FILE__ ));
      wp_enqueue_script('chart-bundle'); 

     // wp_register_script( 'chart-min', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js' );
      wp_deregister_script( 'chart-min' );
      wp_register_script( 'chart-min', plugins_url( '/js/Chart.min.js', __FILE__ ));
      wp_enqueue_script('chart-min'); 

    }

    ////////////////////////////////////////////////////////////////
    //Add Menu Page
    
    function add_network_menu_rl() {
     // add_menu_page( "page_title", "UMP Bloggers", 'capability', 'menu_slug', 'my_plugin_options');
     add_menu_page( "page_title", "UMP Network Monitor", 'manage_network_options', 'menu_slug', array($this, 'my_plugin_options'));
    }
    ////////////////////////////////////////////////////////////////
    //Get all blogs info
    
    function getBlog(){
      global $wpdb;
            $query = $wpdb->get_results( $wpdb->prepare( 
                    "SELECT * FROM {$wpdb->blogs} WHERE  spam = '0' 
                    AND deleted = '0' AND archived = '0' 
                    ORDER BY registered DESC, 5", ARRAY_A ) );

      return $query;
    }
    ////////////////////////////////////////////////////////////////
    //Get list jabatan info

    function getJabatanList(){
       //Get all faculty list form jabatan.json
            $json = file_get_contents(plugins_url( 'jabatan.json', __FILE__ )); 
            $j = json_decode($json,true);

            return $j['JABATAN'];

    }

    ////////////////////////////////////////////////////////////////
    //Get Jabatan
    
    function generateData(){
    /*
    This function will generate data structure which is contain all data related to blog base on department.
     */

    foreach ($this->blogs as $blog) 
        {
           
            switch_to_blog( $blog->blog_id );
            
            //Dapatkan jabatan blog
            $current_jabatan = get_blog_option(get_current_blog_id(),'department');

            $this->total_publish+=wp_count_posts()->publish;
            $this->total_draft+=wp_count_posts()->draft;

            
            //Get all faculty list form jabatan.json
            $this->jabatan_lists=$this->getJabatanList();

        
            //Dapatkan bilangan blog dan total post            
                foreach ($this->jabatan_lists as $jabatan)
                {

                //jika akronim terkini == jabatan 
                  if($jabatan['ptj_acro']==$current_jabatan){

                    //jika key exist dalam array data
                      if (array_key_exists($current_jabatan,$this->data)){
                      
                        $this->data[$current_jabatan]['jumlah_blog']+=1;
                        $this->data[$current_jabatan]['jumlah_blog_post_publish']+=wp_count_posts()->publish;
                        $this->data[$current_jabatan]['jumlah_blog_post_draft']+=wp_count_posts()->draft;
                    

                        // echo $current_jabatan;    
                      }else{
                        $this->data[$current_jabatan]['jumlah_blog']=1;
                        $this->data[$current_jabatan]['jumlah_blog_post_publish']=wp_count_posts()->publish;
                        $this->data[$current_jabatan]['jumlah_blog_post_draft']=wp_count_posts()->draft;
                         
                      }

                  }else{
                    // echo "Next";
                  }

                } // END FOREACH JABATAN


        }//END FOREACH BLOG  

        //SORT DESC
        array_multisort($this->data, SORT_DESC);

    }
    ////////////////////////////////////////////////////////////////
    //Generate Chart
    
    function generateChart(){

      $data = array();

      foreach($this->data as $value){
          $data[] = $value['jumlah_blog_post_publish'];
          //echo $value['jumlah_blog_post_publish'];
      }

      ?>

       <canvas id="myChart"></canvas>
      
      <script>
      var ctx = document.getElementById('myChart').getContext('2d');
      var chart = new Chart(ctx, {
          // The type of chart we want to create
          type: 'line',

          // The data for our dataset
          data: {
              labels: <?=json_encode(array_keys($this->data)); ?>,
              datasets: [{
                  label: "Publish Post",
                  backgroundColor: 'rgb(255, 99, 132)',
                  borderColor: 'rgb(255, 99, 132)',
                  data: <?=json_encode(array_values($data)); ?>,
              }]
          },

          // Configuration options go here
          options: {}
      });
      </script>

      <?php

    //var_dump($data);

    }

    ////////////////////////////////////////////////////////////////
    //Generate Table
    
    function generateTable($title,$header,$var = NULL){


      ?>

       <div class="wrap">
        <h3>UMP BLOGGERS STATISTICS & REPORT</h3>

      <?php
        // Generate Chart
       $this->generateChart();
      ?>

      <br />
        <table class="widefat">
            <thead>
                <tr>
                   <?php

                  foreach($header as &$value){

                    echo '<th>'.$value.'</th>';
                  }

                   ?>
                </tr>
            </thead>

            <tfoot>
                <tr>
                   <?php

                  foreach($header as &$value){

                    echo '<th>'.$value.'</th>';
                  }

                   ?>
                </tr>
            </tfoot>
            <tbody>

      <?php

        $i=1;
        foreach ($var as $key => $value) {
           

            echo '
                  <tr>
                     <td>'.$i.'</td>
                     <td><a href="'. esc_url( add_query_arg( 'jabatan', $key )) .'">'.$key.'</a></td>
                      <td>'.$this->data["$key"]["jumlah_blog"].'</td>
                     <td>'.$this->data["$key"]["jumlah_blog_post_publish"].'</td>
                    <td>'.$this->data["$key"]["jumlah_blog_post_draft"].'</td>
                   </tr>
            ';
           
            $i++;
        }// END FOREACH
  ?>


            </tr>

            <tr style="background-color: lightgrey;"><td></td><td><strong>Total</strong></td><td><?php echo get_blog_count();?></td><td><?php echo $this->total_publish; ?></td><td><?php echo $this->total_draft; ?></td></tr>

            </tbody>
        </table>
       

    

        </div>
    <?php

    }
    
    ////////////////////////////////////////////////////////////////
    //create plugin option
    //
    function my_plugin_options() {
      
        //Check if no blog exist
        if ( empty( $this->blogs ) ) 
        {
            echo '<p>No blogs!</p>';
            break;
        }

        //Generate Data Base On Blog Category
         $this->generateData();


      if(isset($this->request_jabatan)){
        ?>     

       <div class="wrap">   

        <h3>UMP BLOGGERS STATISTICS & REPORT (<?php echo $this->request_jabatan; ?>) </h3>
        <table class="widefat">
            <thead>
                <tr>
                 <th>No.</th>
                 <th>ID</th>
                    <th>Blog Setting</th>
                    <th>Publish posts</th>       
                    <th>Draft posts</th>
                    <th>Jabatan</th>
                    <th>Admin Email</th>
                    <th>Visit</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                <th>No.</th>
                 <th>ID</th>
                <th>Blog Setting</th>
                <th>Publish posts</th>
                <th>Draft posts</th>
                <th>Jabatan</th>
                <th>Admin Email</th>
                <th>Visit</th>
                </tr>
            </tfoot>
            <tbody>
           
            <?php
        
        //var_dump($this->data);

        //Start Counter $i
        $i = 1;

        foreach ($this->blogs as $blog) 
        {
           
            //Switch to current blog
            switch_to_blog( $blog->blog_id );
            
            //Dapatkan jabatan blog
            $current_jabatan = get_blog_option(get_current_blog_id(),'department');
            
            //Count publish post
            $publish_posts = wp_count_posts()->publish;
            $this->publish_network += $publish_posts;

            //Count draft post
            $draft_posts = wp_count_posts()->draft;       
            $this->draft_network += $draft_posts;

           // echo $current_jabatan;
           
                 //if get request is set
                  if(isset($this->request_jabatan)){

                         //if jabatan is same as get jabatan 
                          if($this->request_jabatan==$current_jabatan){
                            $total_jabatan_publish_post+=$publish_posts;
                            $total_jabatan_draft_post+=$draft_posts;

                          ?>

                           <tr>
                             <td><?php echo $i; ?></td>
                             <td><?php echo get_current_blog_id(); ?></td>
                             <td><a href="<?php echo get_blog_details(get_current_blog_id())->siteurl.'/wp-admin/admin.php?page=Ump_Blog_Setting'; ?>"><?php echo $blog->path; ?></a></td>
                             <td><?php echo $publish_posts; ?></td>
                             <td><?php echo $draft_posts; ?></td>
                             <th><?php echo $current_jabatan;?></th>
                             <th><?php echo get_bloginfo('admin_email'); ?></th>
                             <td><a href="<?php echo get_blog_details(get_current_blog_id())->siteurl; ?>"> Visit </a></td>
                           </tr>
                        <?php 
                        
                        $i++;

                        }
                    
                }else{
                 ?>
                     <tr>
                       <td><?php echo $i; ?></td>
                       <td><?php echo get_current_blog_id(); ?></td>
                       <td><?php echo $blog->path; ?></td>
                       <td><?php echo $publish_posts; ?></td>
                       <td><?php echo $draft_posts; ?></td>
                       <th><?php echo $current_jabatan;?></th>
                       <th><?php echo get_bloginfo('admin_email'); ?></th>
                     </tr>
                  <?php 

                  $i++;

                }

                
        }//END FOREACH BLOG

       
        ?>
            <tr style="background-color: lightgrey;">
                <td></td>
                <td></td>
                 <td><b>Total</b></td>
               
            <?php if(isset($this->request_jabatan)){ ?>
                 <td><?php echo $total_jabatan_publish_post; ?></td>
                 <td><?php echo $total_jabatan_draft_post; ?></td>
              
               <?php 
             }else{
            ?>

              <td><?php echo $this->publish_network; ?></td>
                             <td><?php echo $this->draft_network; ?></td>


            <?php
             }
               ?>

                <td></td>
                <td></td>
                <td></td>
               </tr>
        </tbody>
        </table>
       
        </div>



        <?php 

      }else{


          $header = array("No.","Jabatan", "Total Blog", "Total Publish", "Total Draft");
          $this->generateTable('UMP Bloggers', $header, $this->data);
        
      }// endif


    }//close function

}//close class

}//close if

if( is_admin() )
  $my_settings_page = new UmpNetworkMonitor();


?>
