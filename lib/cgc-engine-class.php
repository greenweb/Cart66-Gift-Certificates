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

if (!class_exists("cgcEngine")) {
  class cgcEngine
  {
    //__construct()
    function cgcEngine() 
    {
      add_action('init', array($this,'cgc_add_to_promotions' ),10);
    }

    function cgc_add_to_db_table($orderInfo) {
      if(!isset($orderInfo['id'])) return false;
      global $wpdb;
      $cgc_data_tb            = $wpdb->prefix . CGC_PREFIX . "data";
      $cgc_c66_orders_tb      = $wpdb->prefix . CGC_C66_PREFIX . "orders";
      $cgc_c66_order_items_tb = $wpdb->prefix . CGC_C66_PREFIX . "items";
      $cgc_c66_promotions_tb  = $wpdb->prefix . CGC_C66_PREFIX . "promotions";
      $cgc_c66_orders         = $wpdb->get_row("SELECT * FROM $cgc_c66_orders_tb WHERE id = ".$orderInfo['id'], ARRAY_A);

      // what is extract? See http://ca3.php.net/manual/en/function.extract.php
      extract($orderInfo, EXTR_PREFIX_ALL,'cgc_o_info'); 
      extract($cgc_c66_orders,EXTR_PREFIX_ALL,'cgc_o'); // adds prefix $cgc_o_ to extracted vars
      /*
      returned by $orderInfo
        $coupon = none 
        $shipping = 0.00 
        $trans_id = PP-T1Q6FUVCKJ7Q36 
        $status = paypal_pending 
        $ordered_on = 2012-11-19 21:34:42 
        $shipping_method = None 
        $account_id = 0 
        $ip = 99.12.77.31 
        $discount_amount = 0 
        $id = 7
      */
      /**
       * Note `status` in this table relates to the Gift Certificate
       *  `order_status` relates to the payment status i.e: paypal_pending as returned by the Cart 66 Hook
       * `coupon` and `coupon_amount` relates to any coupons used in this purchase. 
      **/
      $wpdb->query("DELETE FROM `$cgc_data_tb` WHERE `order_id` = $cgc_o_id");
      $cgc_data_tb_data_sql = 
      "INSERT INTO  `$cgc_data_tb`
              (
                `ID` ,
                `order_id` ,
                `product_id` ,
                `quantity` ,
                `coupon` ,
                `coupon_amount` ,
                `order_status` ,
                `status` ,
                `chron_added` ,
                `promo_added`
              )
              VALUES (
                NULL ,
                $cgc_o_id,
                0,
                0,
                '$cgc_o_coupon',
                '$cgc_o_discount_amount',
                '$cgc_o_status',
                '$cgc_o_status',
                0,
                0
              );";
      $cgc_add_data_tb = $wpdb->query($cgc_data_tb_data_sql);
      /*
       order_id       int(18) NOT NULL,
       product_id     int(18) NOT NULL,
       quantity       int(18) NOT NULL,
       coupon         varchar(255) NOT NULL,
       coupon_amount  decimal(12,2) unsigned NOT NULL default 0,
       order_status   varchar(255) NOT NULL,
       status         varchar(255) NOT NULL,
       chron_added    tinyint(1) not null default 0,
       promo_added    tinyint(1) not null default 0,
      */
    }

    function cgc_add_to_promotions() {  
      global $wpdb;
      $cgc_data_tb            = $wpdb->prefix . CGC_PREFIX . "data";
      $cgc_c66_orders_tb      =  $wpdb->prefix . CGC_C66_PREFIX . "orders";
      $cgc_c66_order_items_tb = $wpdb->prefix . CGC_C66_PREFIX . "order_items";
      $cgc_c66_promo_tb       = $wpdb->prefix . CGC_C66_PREFIX . "promotions"; // wp_cart66_promotions
      $cgc_orders_results     = $wpdb->get_results("SELECT * FROM $cgc_c66_orders_tb");
      $message = "Thanks for yada yada etc, your gift certificate coupon code is :"; // this assumes that the site admin will change out the filler message
      $cgc_sql = "SELECT 
                  `$cgc_c66_order_items_tb`.`item_number`,
                  `$cgc_c66_order_items_tb`.`order_id`,
                  `$cgc_c66_order_items_tb`.`product_id`,
                  `$cgc_c66_order_items_tb`.`product_price`,
                  `$cgc_c66_order_items_tb`.`quantity`,
                  `$cgc_c66_orders_tb`.`email`,
                  `$cgc_c66_orders_tb`.`bill_first_name`,
                  `$cgc_c66_orders_tb`.`bill_last_name`,
                  `$cgc_c66_orders_tb`.`ordered_on`,
                  `$cgc_c66_orders_tb`.`custom_field` 
                    FROM `$cgc_c66_order_items_tb`,`$cgc_c66_orders_tb`,`$cgc_data_tb`
                    WHERE `$cgc_c66_order_items_tb`.`order_id`=`$cgc_c66_orders_tb`.`id`
                    AND `$cgc_data_tb`.`order_id` =  `$cgc_c66_order_items_tb`.`order_id`
                    AND `$cgc_data_tb`.`promo_added` = 0
                    AND `$cgc_c66_orders_tb`.`status` = '".CGC_STATUS."'";

      // defaults
      $cgc_time_period = trim(get_option(CGC_S_NAME."time_period"));
      $cgc_time_period = ($cgc_time_period!='') ? date("Y-m-d H:i:s", strtotime("$cgc_time_period")) : "" ;
      // email
      $cgc_debug_email = get_option('admin_email');
      $cgc_from_email = trim(get_option(CGC_S_NAME."gs_email_from_address"));
      $cgc_from_name  = trim(get_option(CGC_S_NAME."gs_email_from_name"));
      $cgc_subject    = trim(get_option(CGC_S_NAME."gs_email_subject"));
      $cgc_email_body = trim(get_option(CGC_S_NAME."gs_email"));
      $headers        = "From: $cgc_from_name <$cgc_from_email>\r\n";

      // add filter to set the email to HTML
      add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));

      $raw_data = $wpdb->get_results($cgc_sql,ARRAY_A);

      foreach ($raw_data as $key) {
        extract($key); // the info
        
        $is_gift = explode('-', trim($item_number));
        

        if ( trim( strtolower($is_gift[0]) ) == 'gift' ) {
          // add the promotion to the wp_cart66_promotions table

          $code = $order_id;
          $code .= uniqid();
          $promo_sql = "INSERT INTO 
                        `$cgc_c66_promo_tb`(
                          `code`,
                          `type`,
                          `amount`,
                          `min_order`,
                          `name`,
                          `enable`,
                          `apply_to`,
                          `auto_apply`,
                          `maximum_redemptions`,
                          `max_uses_per_order`,
                          `min_quantity`,
                          `max_quantity`,
                          `effective_from`,
                          `effective_to`,
                          `stackable`,
                          `max_order`,
                          `exclude_from_products`
                        )
                        VALUES
                        (
                          ',$code,',  
                          'dollar',
                          $product_price,
                          0.00,
                          '".__('Gift Certificate',CGC_LANG)."',
                          1, 
                          'total',
                          0,
                          1,
                          0,
                          0,
                          0,
                          '$ordered_on', 
                          '".$cgc_time_period."',
                          0,
                          0.00,
                          1
                        );";
          $cgc_add_promo_tb = $wpdb->query($promo_sql);

          if ($cgc_add_promo_tb) {
            // update the cgc_data_tb // 
            $updatecgc_data_tb_sql = "UPDATE `$cgc_data_tb` 
                                        SET  `product_id` =  '$product_id', 
                                        `quantity` = '$quantity',
                                        `order_status` = '".CGC_STATUS."',
                                        `promo_added` = 1
                                        WHERE  `order_id` = $order_id;";
            $update_cgc_data_tb = $wpdb->query($updatecgc_data_tb_sql);
            // email the user $email
            $has_code = strpos($cgc_email_body,"#code#");
            if ($has_code!==false) {
              $cgc_email_body = "<h1>".__("This is your gift certificate code: #code# ",CGC_LANG)."</h1>".$cgc_email_body;
            }
            $cgc_email_body   =  str_replace( "#fullname#",   "$bill_first_name $bill_last_name", $cgc_email_body );
            $cgc_email_body   =  str_replace( "#code#",       $code, $cgc_email_body );
            $cgc_email_body   =  str_replace( "#value#",      $product_price, $cgc_email_body );
            if(trim($cgc_time_period)=='') $cgc_time_period = __(" No Expiration Date ",CGC_LANG);
            $cgc_email_body   =  str_replace( "#expiration#", $cgc_time_period, $cgc_email_body );
            $cgc_email_body   =  stripslashes($cgc_email_body);
            
            wp_mail($email,$cgc_subject, $cgc_email_body, $headers); // wp_mail( $to, $subject, $body, $headers);
            
          }
        }
      }// end foreach
      // set the email back to plain text
      add_filter('wp_mail_content_type',create_function('', 'return "text/plain"; '));

    } 



  } // end  class
}// end if class