<?php

class EcwidProductApi {

    var $store_id = '';

    var $error = '';

    var $error_code = '';

    var $ECWID_PRODUCT_API_ENDPOINT = '';
    var $ECWID_TOKEN = '';

    # construct with the store id and public token or seret token of the registered app
    function __construct($store_id, $token) {

        $this->ECWID_PRODUCT_API_ENDPOINT = 'https://app.ecwid.com/api/v3';
        $this->store_id = intval($store_id);
        $this->ECWID_TOKEN = $token;
    }

    function EcwidProductApi($store_id, $token) {
        if(version_compare(PHP_VERSION,"5.0.0","<")) {
          $this->__construct($store_id, $token);
        }
    }

    function process_request($url) {

        $result = false;
        $fetch_result = EcwidPlatform::fetch_url($url);
     
        if ($fetch_result['code'] == 200) {
            $this->error = '';
            $this->error_code = '';
            $json = $fetch_result['data'];

            # decode the json using php builtin service, or our parser on older php versions
            if(version_compare(PHP_VERSION,"5.2.0",">=")) {
                $result = json_decode($json, true);
            }else{
                $json_parser = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
                $result = $json_parser->decode($json);
            }

        } else {
            $this->error = $fetch_result['data'];
            $this->error_code = $fetch_result['code'];
        }
        
        return $result;
    }

    function get_whole_list_of_items($api_url){

        $all_items = array();
        $more_to_read=true;
        $offset=0;

        while($more_to_read){
            $more_to_read=false;
            $result = $this->process_request($api_url . "&offset=$offset");

            $total=$result['total'];
            $count=$result['count'];
            $offset=$result['offset'];
            $items=$result['items'];

            foreach($items as $item){
               array_push($all_items, $item);
            }

            $offset+=$count;

            if($offset < $total){
                $more_to_read=true;
            }
        }

        return $all_items;
    }

    function get_all_categories() {
        
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . '/' . $this->store_id . '/categories?enabled=true&token=' .$this->ECWID_TOKEN;
        $categories = $this->get_whole_list_of_items($api_url);

        return $categories;
    }

    function get_subcategories_by_id($parent_category_id = 0) {
        
        $parent_category_id = intval($parent_category_id);
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . '/' . $this->store_id . '/categories?enabled=true&parent=' . $parent_category_id
            . '&token=' . $this->ECWID_TOKEN;
        $categories = $this->get_whole_list_of_items($api_url);

        return $categories;
    }

    function get_all_products() {

        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . '/' . $this->store_id . '/products?enabled=true&token=' .$this->ECWID_TOKEN;
        $products = $this->get_whole_list_of_items($api_url);

        return $products;
    }


    function get_products_by_category_id($category_id = 0) {

        $category_id = intval($category_id);
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id
            . "/products?enabled=true&category=" . $category_id . '&token=' . $this->ECWID_TOKEN;
        $products = $this->get_whole_list_of_items($api_url);

        return $products;
    }

    function get_product($product_id) {

        static $cached;

        $product_id = intval($product_id);

        if (isset($cached[$product_id])) {
            return $cached[$product_id];
        }

        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id
            . "/product?id=" . $product_id . '&token=' . $this->ECWID_TOKEN;
        $cached[$product_id] = $this->process_request($api_url);

        return $cached[$product_id];
    }

    function get_category($category_id) {

        static $cached = array();

        $category_id = intval($category_id);

        if (isset($cached[$category_id])) {
            return $cached[$category_id];
        }
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id
            . "/category?id=" . $category_id . '&token=' . $this->ECWID_TOKEN;
        $cached[$category_id] = $this->process_request($api_url);

        return $cached[$category_id];
    }
    
    function get_profile() {

        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id
            . "/profile?token=" . $this->ECWID_TOKEN;
        $profile = $this->process_request($api_url);

        return $profile;
    }

    function is_api_enabled() {

        // quick and lightweight request
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id
        . "/profile?token=" . $this->ECWID_TOKEN;
        $this->process_request($api_url);

        return $this->error_code === '';
    }

}
