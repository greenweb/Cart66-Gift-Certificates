<?php
/**
 * Copyright (c) November 19, 2012 Andrew Rixom and BeforeSite LLC. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

// for future use

if (!class_exists("cgcAdmin")) {
  class cgcAdmin
  {
    function cgcAdmin() //__construct()
    {
     add_action('admin_init', array( $this,'cgc_admin_init'    ) );
     add_action('admin_menu',  array( $this,'cgc_add_pages'    ),100 );
    }
    
    //register cgc stylesheet
    function cgc_admin_init() 
    {
      if(!isset($_GET['page'])) return;
      $page  = $_GET['page'];
      $cgc_page = explode("_", $page);
      if( $cgc_page[0] == "cgc" )
      {
        wp_register_style( 'cgcStylesheet', CGC_URL . 'css/stylesheet.css', false, CGC_VERSION,'all' );
        wp_enqueue_style( 'cgcStylesheet' );
      }
    }
    
    //add menu to admin page
    function cgc_add_pages()
    {
      //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
      if (!class_exists("Cart66Common")) return;
      add_submenu_page(
        'cart66_admin', 
        __('Gift Certificates Options', CGC_LANG), 
        __('Gift Certificates', CGC_LANG), 
        Cart66Common::getPageRoles('orders'), 
        'cgc_options_pg', 
        array($this,'cgc_options_pg')
      );      
    } //End cgc_add_pages()

    //create the options page
    function cgc_options_pg()
    {
      //options saved message
      $this->cgc_options_saved_message();
      $this->cgc_options_pg_html();
    }
    
    function cgc_options_saved_message($value='c_gift_c_saved')
    {
      //options saved message
      if (isset($_REQUEST['action']) && $value == $_REQUEST['action'] ) echo '<div id="message" class="updated fade"><p><strong>'.__('Settings saved.').'</strong></p></div>';
    }
    //this is the html for the cgc options page
    function cgc_options_pg_html()
    { 
      $e_options = $this->cgc_options_array();
      // save plugin's options
      
      if ( isset($_REQUEST['action']) &&  'c_gift_c_saved' == $_REQUEST['action'] ) {
          foreach ($e_options as $value) {
            $temp_val = ( isset($_REQUEST[ $value['id'] ]) ) ? $_REQUEST[ $value['id'] ] : '' ;
            update_option( $value['id'], $temp_val );
          }
        foreach ($e_options as $value) {
          if( isset( $_REQUEST[ $value['id'] ] ) ) { update_option( $value['id'], $_REQUEST[ $value['id'] ]  ); } else { delete_option( $value['id'] ); } 
        }
      }
      ?>
      <div> 
          <h2><?php  echo(CGC_NAME);_e(" Options"); ?></h2>
          <?php /*START THE FORM WRAPPING DIV*/ ?>
          <div class="metabox-holder">
          
          <form method="post" action="">
            <table class="form-table" id="c_gift_c_form_table">
                <?php 
                foreach ($e_options as $value) {

                  switch ( $value['type'] ) {
                    case 'text':
                      ?>
                      <tr valign="top"> 
                        <th scope="row"><label for="<?php echo $value['id']; ?>"><?php _e($value['name']); ?></label></th>
                        <td>
                          <input name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" class="widefat"
                            type="<?php echo $value['type']; ?>" 
                            value="<?php 
                              if ( get_option( $value['id'] ) != "") { 
                                echo get_option( $value['id'] ); 
                              } else { 
                                echo $value['std']; 
                              } ?>" maxlength="<?php echo $value['maxlength']; ?>" size="<?php echo $value['size']; ?>" />
                          <?php _e($value['desc']); ?>
                        </td>
                      </tr>
                      <?php
                    break;

                    case 'select':
                      ?>
                      <tr valign="top"> 
                        <th scope="row"><label for="<?php echo $value['id']; ?>"><?php _e($value['name']); ?></label></th>
                        <td>
                          <label for="<?php echo $value['id']; ?>"><?php echo $value['name']; ?></label>
                          <select name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>">
                            <?php foreach ($value['options'] as $option) { ?>
                            <option <?php if (get_option( $value['id'] ) == $option) { echo 'selected="selected"'; } ?>><?php echo $option; ?></option>
                            <?php } ?>
                          </select>
                          <?php _e($value['desc']); ?>
                        </td>
                      </tr>
                      <?php
                    break;

                    case 'wp_editor':
                    if(isset($_REQUEST['options'])) $ta_options = $value['options'];
                    ?>
                    <tr valign="top"> 
                      <th scope="row"><label for="<?php echo $value['id']; ?>"><?php _e($value['name']); ?></label></th>
                      <td>
                        <?php
                        if( get_option($value['id']) != "") {
                            $_body = stripslashes( get_option($value['id']) );
                          }else{
                            $_body =$value['std'];
                          }
                            wp_editor(
                              $_body,
                              "receipt_body",
                               array(
                                'media_buttons' => 1,
                                
                                'textarea_name' => $value['id']
                                )
                              );
                          ?>
                        <br />
                        <?php _e($value['desc']); ?>
                      </td>
                    </tr>
                    <?php
                    break;
                    case 'checkbox':
                    if(isset($_REQUEST['options'])) $ta_options = $value['options'];
                    ?>
                    <tr valign="top"> 
                      <th scope="row"><label for="<?php echo $value['id']; ?>"><?php _e($value['name']); ?></label></th>
                      <td>
                        <?php
                          if( get_option($value['id']) == true) {
                              $ischecked = 'checked="checked"';
                            }else{
                              $ischecked = '';
                          }
                        ?>
                        <input name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="checkbox" value="true" <?php echo($ischecked); ?>>
                        <?php _e($value['desc']); ?>
                      </td>
                    </tr>
                    <?php
                    break;
                    case 'nothing':
                    $ta_options = $value['options'];
                    ?>
                    </table>
                      <?php _e($value['desc']); ?>
                    <table class="form-table">
                    <?php
                    break;

                    default:

                    break;
                  }
                }
                ?>
                <tr valign="top">
                <th scope="row">&nbsp;</th>
                <td>
                      <p class="submit">
                          <input type="hidden" name="action" value="c_gift_c_saved" />
                          <input class="button-primary" name="save" type="submit" value="<?php _e('Save Your Changes'); ?>" />    
                      </p>
                    </td>
              </tr>
            </table>
          </form>
          </div> <?php /*END THE FORM WRAPPING DIV*/ ?>
          <br style="float:none;clear:both;">
      </div>
      <!-- END WRAP -->
    <?php
    }
   
    function cgc_options_array()
    {
      $e_options = array (  
            array("name" => __('Email From and Reply to Address',CGC_LANG),
                  "desc" => __('This is the email address that appears in the from field.',CGC_LANG),
                  "id" => CGC_S_NAME."gs_email_from_address",
                  "std" => get_option('admin_email'), 
                  "type" => "text",
                  "size" => '100',
                  'maxlength' => '225'), 

            array("name" => __('Email From Name',CGC_LANG),
                  "desc" => __('The from name as seen by the customer in their email client.',CGC_LANG),
                  "id" => CGC_S_NAME."gs_email_from_name",
                  "std" => get_option('blogname'),
                  "type" => "text",
                  "size" => '100',
                  'maxlength' => '225'),  
                        
            array("name" => __('Email Subject',CGC_LANG),
                  "desc" => __('The subject of the email sent to your customer.',CGC_LANG),
                  "id" => CGC_S_NAME."gs_email_subject",
                  "std" => __('Gift Certificate Code',CGC_LANG), 
                  "type" => "text",
                  "size" => '100',
                  'maxlength' => '225'),

            array("name" => __('Gift Certificate Code to Client',CGC_LANG),
                  "desc" => '<p><code>#fullname#</code>'.__(' is the placeholder for the purchasers name.',CGC_LANG)
                              .'</p><p><code>#code#</code>'.__(' is the placeholder for the gift certificate code.',CGC_LANG)
                              .'</p><p><code>#value#</code>'.__(' is the placeholder for dollar value of the gift certificate.',CGC_LANG)
                              .'</p><p><code>#expiration#</code>'.__(' is the placeholder for the gift certificate expiration date.',CGC_LANG).'</p>',
                  "id" => CGC_S_NAME."gs_email",
                  "std" => __("<p>Hi #fullname#,</p><ul><li>This is your gift certificate code: #code#</li><li>Your gift certificate has a total value of $ #value# US Dollars</li><li>Your gift certificate is valid for the for one use only</li><li>Your gift certificate expires on #expiration#</li></ul><p>Thank you for your purchase.</p>",CGC_LANG),
                  "cols"=> "70",
                  "rows"=> "12",
                  "type" => "wp_editor"),


            array("name" => __('Gift Certificate Time Period',CGC_LANG),
                  "desc" => __('Time Period that the Gift Certificate is valid for.',CGC_LANG),
                  "id" => CGC_S_NAME."time_period",
                  "std" => '', // nothing by default
                  "options" => array('','+1 months','+2 months','+3 months','+4 months','+5 months','+6 months','+7 months','+8 months','+9 months','+10 months','+11 months','+12 months' ),
                  "type" => "select"),
            array("name" => __('Delete Setting on Deactivation',CGC_LANG),
                  "desc" => __('Before you deactivate this plugin check this box if you want to remove all the options above.',CGC_LANG),
                  "id" => CGC_S_NAME."delete_settings",
                  "std" => '', // off by default
                  "type" => "checkbox"),
      );
      
      return $e_options;
    }
    
   
    // End right col widgets

  } //End Class cgcAdmin

} //End if Class cgcAdmin

// eof