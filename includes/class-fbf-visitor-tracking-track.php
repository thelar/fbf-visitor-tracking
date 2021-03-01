<?php


class Fbf_Visitor_Tracking_Track
{
    public static function track($type, $user=null, $order=null, $data=null)
    {
        // Get the cookie values
        $id = WC()->session->get_customer_id();

        $d = self::data($type, $user, $order, $data);
        if($d){
            $data = serialize($d);
            if(is_user_logged_in()){
                $user = wp_get_current_user();
                $user_id = $user->ID;
                $user_email = $user->user_email;
                $user_telephone = get_user_meta($user_id, 'billing_phone', true);
                $session_cookie = WC()->session->get('_fbf_initial_session');

                $insert = [
                    'action' => $type,
                    'session_cookie' => $session_cookie,
                    'user_id' => $id,
                    'customer_phone' => $user_telephone,
                    'customer_email' => $user_email,
                    'data' => $data
                ];
            }else{
                if(!is_null($user)){
                    $user_id = $user->ID;
                    $user_email = $user->user_email;
                    $user_telephone = get_user_meta($user_id, 'billing_phone', true);
                    $session_cookie = WC()->session->get('_fbf_initial_session');

                    $insert = [
                        'action' => $type,
                        'session_cookie' => $session_cookie,
                        'user_id' => $user_id,
                        'customer_phone' => $user_telephone,
                        'customer_email' => $user_email,
                        'data' => $data
                    ];
                }else{
                    if(!empty(WC()->session->get('_fbf_session_anon_contact'))){
                        $user_email = WC()->session->get('_fbf_session_anon_contact')['email'];
                        $user_telephone = WC()->session->get('_fbf_session_anon_contact')['phone'];
                        $insert = [
                            'action' => $type,
                            'session_cookie' => $id,
                            'customer_phone' => $user_telephone,
                            'customer_email' => $user_email,
                            'data' => $data
                        ];
                    }else{
                        $insert = [
                            'action' => $type,
                            'session_cookie' => $id,
                            'data' => $data
                        ];
                    }
                }
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'fbf_visitor_tracking';
            $wpdb->insert(
                $table_name,
                $insert
            );
        }
    }

    public static function output($id)
    {
        if(isset($id)){
            $data = [];
            global $wpdb;
            $table_name = $wpdb->prefix . 'fbf_visitor_tracking';
            $query = "SELECT * FROM $table_name WHERE session_cookie = '" . $_GET['id'] . "'";
            $results = $wpdb->get_results($query);
            if(!empty($results)){
                foreach($results as $result){
                    $data[] = [
                        'timestamp' => $result->timestamp,
                        'user_id' => $result->user_id,
                        'order_id' => $result->order_id,
                        'customer_phone' => $result->customer_phone,
                        'customer_email' => $result->customer_email,
                        'action' => $result->action,
                        'data' => unserialize($result->data)
                    ];
                }
            }
            return $data;
        }else{
            return false;
        }
    }

    private static function data($type, $user, $order, $data)
    {
        global $wp_query;
        $req = $_SERVER['REQUEST_URI'];
        switch($type){
            case '404':
                //Not sure this is the best way of doing it but need to not record 404's for missing images
                if(strpos($req, '/app/uploads/')===false){
                    return [
                        'request' => $req
                    ];
                }else{
                    return false;
                }
                break;
            case 'page':
                if(is_front_page()||is_page()){
                    if(is_account_page()){
                        if(is_wc_endpoint_url('edit-account')){
                            $title = 'My Account - Edit Account';
                        }else if(is_wc_endpoint_url('dashboard')){
                            $title = 'My Account - Dashboard';
                        }else if(is_wc_endpoint_url('orders')){
                            $title = 'My Account - Orders';
                        }else if(is_wc_endpoint_url('downloads')){
                            $title = 'My Account - Downloads';
                        }else if(is_wc_endpoint_url('edit-address')){
                            $title = 'My Account - Edit Address';
                        }else if(is_wc_endpoint_url('payment-methods')){
                            $title = 'My Account - Payment Methods';
                        }else if(is_wc_endpoint_url('customer-logout')){
                            $title = 'My Account - Customer Logout';
                        }else if(is_wc_endpoint_url('lost-password')){
                            $title = 'My Account - Lost Password';
                        }else if(is_wc_endpoint_url('view-order')){
                            $title = 'My Account - View Order';
                        }else{
                            $title = 'My Account - Login';
                        }
                        return [
                            'request' => $req,
                            'title' => $title,
                            'type' => 'account page'
                        ];
                    }else if(is_order_received_page()){
                        if(is_wc_endpoint_url('order-pay')){
                            $title = 'Checkout - Pay';
                        }else if(is_wc_endpoint_url('order-received')){
                            $title = 'Checkout - Order Received';
                        }else if(is_wc_endpoint_url('add-payment-method')){
                            $title = 'Checkout - Add Payment Method';
                        }else if(is_wc_endpoint_url('delete-payment-method')){
                            $title = 'Checkout - Delete Payment Method';
                        }else if(is_wc_endpoint_url('set-default-payment-method')){
                            $title = 'Checkout - Set Default Payment Method';
                        }else{
                            $title = 'Checkout';
                        }
                        return [
                            'request' => $req,
                            'title' => $title,
                            'type' => 'account page'
                        ];
                    }else{
                        return [
                            'request' => $req,
                            'title' => $wp_query->post->post_title,
                            'type' => 'page'
                        ];
                    }
                }else if(is_search()){
                    return [
                        'request' => $req,
                        'title' => 'Search Results',
                        'type' => 'search',
                        's' => $wp_query->query_vars['s']
                    ];
                }else if(is_singular()){
                    if($wp_query->get('post_type')==='product'){
                        $terms = get_the_terms( $wp_query->get_queried_object_id(), 'product_cat' );
                        $term = $terms[0]->name;
                        return [
                            'request' => $req,
                            'title' => $wp_query->get_queried_object()->post_title,
                            'type' => $wp_query->get('post_type'),
                            'product_cat' => $term
                        ];
                    }else{
                        return [
                            'request' => $req,
                            'title' => $wp_query->get_queried_object()->post_title,
                            'type' => $wp_query->get('post_type')
                        ];
                    }
                }else if(is_archive()){
                    if($wp_query->get('product_cat')){
                        return [
                            'request' => $req,
                            'title' => 'Product category: ' . $wp_query->get_queried_object()->name,
                            'type' => 'archive',
                            'product_cat' => $wp_query->get('product_cat')
                        ];
                    }else{
                        return [
                            'request' => $req,
                            'title' => 'Archive: ' . $wp_query->get_queried_object()->name,
                            'type' => 'archive',
                            'taxonomy' => $wp_query->get('taxonomy'),
                            'term' => $wp_query->get('term')
                        ];
                    }
                }
                break;
            case 'login':
                $user_id = $user->ID;
                $user_email = $user->user_email;
                $user_telephone = get_user_meta($user_id, 'billing_phone', true);
                $session_cookie = WC()->session->get('_fbf_initial_session');
                return [
                    'user_id' => $user_id,
                    'user_email' => $user_email,
                    'user_telephone' => $user_telephone,
                    'session_cookie' => $session_cookie
                ];
                break;
            case 'order':
                return [
                    'order' => 'data'
                ];
                break;
            case 'product-search':
                return $data;
                break;
            default:
                break;
        }
    }

    public static function login(\WP_User $user){
        $session_id = WC()->session->get_customer_id();
        $user_id = $user->ID;
        $user_email = $user->user_email;
        $user_phone = get_user_meta($user_id, 'billing_phone', true);

        // Set user data on all records with the same $session_id
        global $wpdb;
        $table_name = $wpdb->prefix . 'fbf_visitor_tracking';
        $update = $wpdb->update(
            $table_name,
            [
                'user_id' => $user_id,
                'customer_phone' => $user_phone,
                'customer_email' => $user_email
            ],
            [
                'session_cookie' => $session_id
            ]
        );

        // Set _fbf_initial_session in WC()->session with value of $session_id
        WC()->session->set('_fbf_initial_session', $session_id);

        // Track the login
        self::track('login', $user);
    }

    public static function order($order_id){
        // When the order gets placed - likely to be a non-logged in visitor but have to handle logged in as well
        $order = wc_get_order($order_id);
        $user_email = $order->get_billing_email();
        $user_phone = $order->get_billing_phone();
        $session_id = WC()->session->get_customer_id();

        if(strlen($session_id) > 20){
            // not logged in... assign contact details to all rows with same session_id
            global $wpdb;
            $table_name = $wpdb->prefix . 'fbf_visitor_tracking';
            $update = $wpdb->update(
                $table_name,
                [
                    'customer_phone' => $user_phone,
                    'customer_email' => $user_email
                ],
                [
                    'session_cookie' => $session_id
                ]
            );

            // also set a session variable with email and phone number for any more interactions after this point
            WC()->session->set('_fbf_session_anon_contact', [
                'email' => $user_email,
                'phone' => $user_phone
            ]);

            // finally add the row to the table
            self::track('order', null, $order);
        }else{
            //logged in
            $user_id = $order->get_customer_id();
            $user = get_user_by('ID', $user_id);
            self::track('order', $user, $order);
        }
    }
}
