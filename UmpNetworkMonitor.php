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
private $departmentList = array();
private $request_jabatan;
private $blogs;
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

      add_action( 'wpmu_new_blog', array( $this, 'setDepartment' ) );

      //Get all blogs info
      $this->blogs =  $this->getBlog();
      $this->setAllBlogDepartment();
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

      wp_deregister_script( 'ump-js' );
      wp_register_script( 'ump-js', plugins_url( '/js/ump.js', __FILE__ ));
      wp_enqueue_script('ump-js'); 

    }

    ////////////////////////////////////////////////////////////////
    //Set Department

    function setDepartment($blog_id, $user_id, $domain, $path, $site_id, $meta){

         switch_to_blog( $blog_id );

         update_blog_option(get_current_blog_id(),'department','-'); 

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
    //Set all blogs department for all pre-registration before installation
    
    function setAllBlogDepartment(){
    
      foreach ($this->blogs as $blog) 
        {
           
            //Switch to current blog
            switch_to_blog( $blog->blog_id );
            
            //Dapatkan jabatan blog
            //$current_jabatan = get_blog_option(get_current_blog_id(),'department');

            if(get_blog_option(get_current_blog_id(),'department')){    
        
                 $this->jabatan = get_blog_option(get_current_blog_id(),'department');

            }else{
                
                update_blog_option(get_current_blog_id(),'department','-');   
               // $this->jabatan = '';
            }

        }

    }    
    
    ////////////////////////////////////////////////////////////////
    //Get list jabatan
    
    function getJabatanList(){
       //Get all faculty list form jabatan.json
            $json = file_get_contents(plugins_url( 'jabatan.json', __FILE__ )); 
            $j = json_decode($json,true);

            return $j['JABATAN'];

    }

    ////////////////////////////////////////////////////////////////
    //Tukar jabatan
    
    function changeDepartment(){
    ?>

    <p>Change checked department to <select name="jabatan" id="jabatan">

    <?php 

    foreach($this->jabatan_lists as $j){
      echo "<option value='".$j['ptj_acro']."'>".$j['ptj_acro']."</option>";
    }

      ?>

    <input type="submit" name="changeDepartment" value="Save" class="button button-primary button-large" id="changeDepartment">
    

    <?php
    }

    ////////////////////////////////////////////////////////////////
    //This function generage CSV files
    //
    function generateCSV(){
    
    //var_dump($this->data);
    if (isset($_POST['csv'])){   
     // echo "test";
     
      $fileName = date("Ymd").'-'.$this->request_jabatan.'.csv';
      $fp = @fopen(plugin_dir_path( __FILE__ ).'csv/'.$fileName, 'w') or die('Cannot open the file');
     //$fp = @fopen(plugin_dir_path( __FILE__ ).''.$fileName, 'w') or die('Cannot open the file');
    

      if($fp)
        echo '<a href="'.plugins_url( 'csv/'.$fileName, __FILE__ ).'"> DOWNLOAD </a>';
        //echo '<a href="'.plugins_url($fileName, __FILE__ ).'"> DOWNLOAD </a>';
 
      foreach ($this->departmentList as $fields) {
          fputcsv($fp, $fields);
     }// END FOREACH

      fclose($fp);
      // Make sure nothing else is sent, our file is done
      die();
      //exit;
    }// END IF
    
    }// END FUNCTION

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
                         //echo "No";
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
            </tbody>
        </table>
       

    

        </div>
    <?php

    }
    
    ////////////////////////////////////////////////////////////////
    //create plugin option
    //
    function my_plugin_options() {

        

        // Check if POST Variable is available
        if(isset($_POST['changeDepartment'])){
          if ((isset($_POST['bid'])&&isset($_POST['jabatan']))) {    
           // if($_POST['jabatan']!='-'){
              foreach($_POST['bid'] as $bid){
                 update_blog_option($bid,'department',sanitize_text_field($_POST['jabatan']));
              }//CLOSE FOR
           // }//CLOSE IF            
          } // CLOSE IF
        }
      
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
       <form method="POST">

      

        <h3>UMP BLOGGERS STATISTICS & REPORT (<?php echo $this->request_jabatan; ?>) </h3>

        <table class="widefat">
            <thead>
                <tr>
                 <th><input type="checkbox" name="chk" id="select-all"></th>
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
                <th> </th>
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

                            $this->departmentList[$i][0] = get_current_blog_id();
                            $this->departmentList[$i][1] = get_blog_details(get_current_blog_id())->siteurl.'/wp-admin/admin.php?page=Ump_Blog_Setting';
                            $this->departmentList[$i][2] = $publish_posts;
                            $this->departmentList[$i][3] = $draft_posts;
                            $this->departmentList[$i][4] = $current_jabatan;
                            $this->departmentList[$i][5] = get_bloginfo('admin_email');
                            $this->departmentList[$i][6] = get_blog_details(get_current_blog_id())->siteurl; 
/*
                            //$this->data[$current_jabatan]['jumlah_blog']+=1;
                            $this->departmentList[0][0] = 1;
                            $this->departmentList[0][1] = 2;
                            $this->departmentList[0][2] = 3;
                            $this->departmentList[0][3] = 4;
                            $this->departmentList[0][4] = 5;
                            $this->departmentList[0][5] = 6;
                            $this->departmentList[0][6] = 7; 

*/
                          ?>

                           <tr>
                             <td><input type="checkbox" name="bid[]" value="<?php echo get_current_blog_id(); ?>"></td>
                             <td><?php echo $i; ?></td>
                             <td><?php echo get_current_blog_id(); ?></td>
                             <td><a href="<?php echo get_blog_details(get_current_blog_id())->siteurl.'/wp-admin/admin.php?page=Ump_Blog_Setting'; ?>"><?php echo $blog->path; ?></a></td>
                             <td><?php echo $publish_posts; ?></td>
                             <td><?php echo $draft_posts; ?></td>
                             <td><?php echo $current_jabatan;?></td>
                             <td><?php echo get_bloginfo('admin_email'); ?></td>
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
            <tr>
                <td></td>
                <td></td>
                <td></td>
                 <td><b>Total count</b></td>
               
            <?php if(isset($this->request_jabatan)){ ?>
                 <td><?php echo $total_jabatan_publish_post; ?></td>
                 <td><?php echo $total_jabatan_draft_post; ?></td>
              <td></td>
              <td></td>
              <td></td>
               <?php 
             }else{
            ?>

              <td><?php echo $this->publish_network; ?></td>
                             <td><?php echo $this->draft_network; ?></td>


            <?php
             } 
               ?>

               </tr>
        </tbody>
        </table>
       
        <?php  

         $this->changeDepartment();
        
?>
         <input type="submit" value="GENERATE .CSV" name="csv" class="button button-primary button-large" id="generateCSV">
<?php
          $this->generateCSV();

         //var_dump($this->departmentList);

        ?>


       </form>
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
