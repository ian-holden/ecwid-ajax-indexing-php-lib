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

    function process_request($url) {

        $result = false;
        $fetch_result = EcwidPlatform::fetch_url($url);
     
        if ($fetch_result['code'] == 200) {
            $this->error = '';
            $this->error_code = '';
            $json = $fetch_result['data'];
            $result = json_decode($json, true);
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


class EcwidCatalog
{
	var $store_id = 0;
	var $store_base_url = '';
	var $ecwid_api = null;
	var $ecwid_token = null;
	var $profile=null;

	public function __construct($store_id, $store_base_url, $token)
	{
		$this->store_id = intval($store_id);
		$this->store_base_url = $store_base_url;
		$this->ecwid_token = $token;
		$this->ecwid_api = new EcwidProductApi($this->store_id, $this->ecwid_token);

		$this->profile = $this->ecwid_api->get_profile($this->store_id);

	}

	public function get_product($id)
	{

		$profile = $this->profile;
		$product=$this->ecwid_api->get_product($id);

		$return = $this->_l('');
		
		if (is_array($product)) 
		{
		
			$return .= $this->_l('<div itemscope itemtype="http://schema.org/Product">', 1);
			$return .= $this->_l('<h2 class="ecwid_catalog_product_name" itemprop="name">' . EcwidPlatform::esc_html($product["name"]) . '</h2>');
			$return .= $this->_l('<p class="ecwid_catalog_product_sku" itemprop="sku">' . EcwidPlatform::esc_html($product["sku"]) . '</p>');
			
			if (!empty($product["thumbnailUrl"])) 
			{
				$return .= $this->_l('<div class="ecwid_catalog_product_image">', 1);
				$return .= $this->_l(
					sprintf(
						'<img itemprop="image" src="%s" alt="%s" />',
						EcwidPlatform::esc_attr($product['thumbnailUrl']),
						EcwidPlatform::esc_attr($product['name'] . ' ' . $product['sku'])
					)
				);
				$return .= $this->_l('</div>', -1);
			}
			
			if(is_array($product["categories"]))
			{
				foreach ($product["categories"] as $ecwid_category) 
				{
					if($ecwid_category["defaultCategory"] == true)
					{
						$return .= $this->_l('<div class="ecwid_catalog_product_category">' . EcwidPlatform::esc_html($ecwid_category['name']) . '</div>');
					}
				}
			}
			
			$return .= $this->_l('<div class="ecwid_catalog_product_price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">', 1);
			$return .=  $this->_l(EcwidPlatform::get_price_label() . ': <span itemprop="price">' . EcwidPlatform::esc_html($product["price"]) . '</span>');

			$return .= $this->_l('<span itemprop="priceCurrency">' . EcwidPlatform::esc_html($profile['formatsAndUnits']['currency']) . '</span>');
			if (!isset($product['quantity']) || (isset($product['quantity']) && $product['quantity'] > 0)) {
				$return .= $this->_l('<link itemprop="availability" href="http://schema.org/InStock" />In stock');
			}
			$return .= $this->_l('</div>', -1);

			$return .= $this->_l('<div class="ecwid_catalog_product_description" itemprop="description">', 1);
			$return .= $this->_l($product['description']);
			$return .= $this->_l('</div>', -1);

			if (is_array($product['attributes']) && !empty($product['attributes'])) {

				foreach ($product['attributes'] as $attribute) {
					if (trim($attribute['value']) != '') {
						$return .= $this->_l('<div class="ecwid_catalog_product_attribute">', 1);

						$attr_string = EcwidPlatform::esc_html($attribute['name']) . ':';

						if (isset($attribute['internalName']) && $attribute['internalName'] == 'Brand') {
							$attr_string .= '<span itemprop="brand">' . EcwidPlatform::esc_html($attribute['value']) . '</span>';
						} else {
							$attr_string .= EcwidPlatform::esc_html($attribute['value']);
						}

						$return .= $this->_l($attr_string);
						$return .= $this->_l('</div>', -1);
					}
				}
			}

			if (is_array($product["options"]))
			{
				$allowed_types = array('TEXTFIELD', 'DATE', 'TEXTAREA', 'SELECT', 'RADIO', 'CHECKBOX');
				foreach($product["options"] as $product_options)
				{
					if (!in_array($product_options['type'], $allowed_types)) continue;

					$return .= $this->_l('<div class="ecwid_catalog_product_options">', 1);
					$return .=$this->_l('<span>' . EcwidPlatform::esc_html($product_options["name"]) . '</span>');

					if($product_options["type"] == "TEXTFIELD" || $product_options["type"] == "DATE")
					{
						$return .=$this->_l('<input type="text" size="40" name="'. EcwidPlatform::esc_attr($product_options["name"]) . '">');
					}
					   if($product_options["type"] == "TEXTAREA")
					{
						 $return .=$this->_l('<textarea name="' . EcwidPlatform::esc_attr($product_options["name"]) . '></textarea>');
					}
					if ($product_options["type"] == "SELECT")
					{
						$return .= $this->_l('<select name='. $product_options["name"].'>', 1);
						foreach ($product_options["choices"] as $options_param) 
						{ 
							$return .= $this->_l(
								sprintf(
									'<option value="%s">%s (%s)</option>',
									EcwidPlatform::esc_attr($options_param['text']),
									EcwidPlatform::esc_html($options_param['text']),
									EcwidPlatform::esc_html($options_param['priceModifier'])
								)
							);
						}
						$return .= $this->_l('</select>', -1);
					}
					if($product_options["type"] == "RADIO")
					{
						foreach ($product_options["choices"] as $options_param) 
						{
							$return .= $this->_l(
								sprintf(
									'<input type="radio" name="%s" value="%s" />%s (%s)',
									EcwidPlatform::esc_attr($product_options['name']),
									EcwidPlatform::esc_attr($options_param['text']),
									EcwidPlatform::esc_html($options_param['text']),
									EcwidPlatform::esc_html($options_param['priceModifier'])
								)
							);
						}
					}
					if($product_options["type"] == "CHECKBOX")
					{
						foreach ($product_options["choices"] as $options_param)
						{
							$return .= $this->_l(
								sprintf(
									'<input type="checkbox" name="%s" value="%s" />%s (%s)',
									EcwidPlatform::esc_attr($product_options['name']),
									EcwidPlatform::esc_attr($options_param['text']),
									EcwidPlatform::esc_html($options_param['text']),
									EcwidPlatform::esc_html($options_param['priceModifier'])
								)
							);
						 }
					}

					$return .= $this->_l('</div>', -1);
				}
			}				
						
			if (is_array($product["galleryImages"])) 
			{
				foreach ($product["galleryImages"] as $galleryimage) 
				{
					if (empty($galleryimage["alt"]))  $galleryimage["alt"] = htmlspecialchars($product["name"]);
					$return .= $this->_l(
						sprintf(
							'<img src="%s" alt="%s" title="%s" />',
							EcwidPlatform::esc_attr($galleryimage['url']),
							EcwidPlatform::esc_attr($galleryimage['alt']),
							EcwidPlatform::esc_attr($galleryimage['alt'])
						)
					);
				}
			}

			$return .= $this->_l("</div>", -1);
		}

		return $return;
	}

	public function get_category($id)
	{
		$category=null;
		if ($id > 0) {
			$category=$this->ecwid_api->get_category($id);
		}
		$categories=$this->ecwid_api->get_subcategories_by_id($id);
		$products=$this->ecwid_api->get_products_by_category_id($id);
		$profile = $this->profile;


		$return = $this->_l('');

		if (!is_null($category)) {
			$return .= $this->_l('<h2>' . EcwidPlatform::esc_html($category['name']) . '</h2>');
			$return .= $this->_l('<div>' . $category['description'] . '</div>');
		}

		if (is_array($categories)) 
		{
			foreach ($categories as $category) 
			{
				$category_url = $this->get_category_url($category);

				$category_name = $category["name"];
				$return .= $this->_l('<div class="ecwid_catalog_category_name">', 1);
				$return .= $this->_l('<a href="' . EcwidPlatform::esc_attr($category_url) . '">' . EcwidPlatform::esc_html($category_name) . '</a>');
				$return .= $this->_l('</div>', -1);
			}
		}

		if (is_array($products)) 
		{
			foreach ($products as $product) 
			{

				$product_url = $this->get_product_url($product);

				$product_name = $product['name'];
				$product_price = $product['price'] . ' ' . $profile['formatsAndUnits']['currency'];
				$return .= $this->_l('<div>', 1);
				$return .= $this->_l('<span class="ecwid_product_name">', 1);
				$return .= $this->_l('<a href="' . EcwidPlatform::esc_attr($product_url) . '">' . EcwidPlatform::esc_html($product_name) . '</a>');
				$return .= $this->_l('</span>', -1);
				$return .= $this->_l('<span class="ecwid_product_price">' . EcwidPlatform::esc_html($product_price) . '</span>');
				$return .= $this->_l('</div>', -1);
			}
		}

		return $return;
	}

	public function parse_escaped_fragment($escaped_fragment)
	{
		$fragment = urldecode($escaped_fragment);
		$return = array();

		if (preg_match('/^(\/~\/)([a-z]+)\/(.*)$/', $fragment, $matches)) {
			parse_str($matches[3], $return);
			$return['mode'] = $matches[2];
		} elseif (preg_match('!.*/(p|c)/([0-9]*)!', $fragment, $matches)) {
			if (count($matches) == 3 && in_array($matches[1], array('p', 'c'))) {
				$return  = array(
					'mode' => 'p' == $matches[1] ? 'product' : 'category',
					'id' => $matches[2]
				);
			}
		}

		return $return;
	}

	public function get_category_name($id)
	{
		$category = $this->ecwid_api->get_category($id);

		$result = '';
		if (is_array($category) && isset($category['name'])) { 
			$result = $category['name'];
		}

		return $result;
	}

	public function get_product_name($id)
	{
		$product = $this->ecwid_api->get_product($id);
				
		$result = '';
		if (is_array($product) && isset($product['name'])) {
			$result = $product['name'];
		}

		return $result;
	}


	public function get_category_description($id)
	{
			$category = $this->ecwid_api->get_category($id);

			$result = '';
			if (is_array($category) && isset($category['description'])) {
					$result = $category['description'];
			}

			return $result;
	}

	public function get_product_description($id)
	{
			$product = $this->ecwid_api->get_product($id);

			$result = '';
			if (is_array($product) && isset($product['description'])) {
					$result = $product['description'];
			}

			return $result;
	}

	public function get_product_url($product)
	{
		if (is_numeric($product) && $this->ecwid_api->is_api_enabled()) {
			$product = $this->ecwid_api->get_product($product);
		}

		return $this->get_entity_url($product, 'p');
	}

	public function get_category_url($category)
	{
		if (is_numeric($category) && $this->ecwid_api->is_api_enabled()) {
			$category = $this->ecwid_api->get_category($category);
		}

		return $this->get_entity_url($category, 'c');
	}

	protected function get_entity_url($entity, $type) {

		$link = $this->store_base_url;

		if (is_numeric($entity)) {
			return $link . '#!/' . $type . '/' . $entity;
		} elseif (is_array($entity) && isset($entity['url'])) {
			$link .= substr($entity['url'], strpos($entity['url'], '#'));
		}

		return $link;

	}

	/*
	 * A helper function to produce indented html output. 
	 * Indent change need to be 1 for opening tag lines and -1 for closing tag lines. 
	 * Regular lines should omit the second parameter.
	 * Example:
	 * _l('<parent-tag>', 1);
	 * _l('<content-tag>content</content-tag>');
	 * _l('</parent-tag>', -1)
	 * 
	 */
	protected function _l($code, $indent_change = 0)
	{
		static $indent = 0;

		if ($indent_change < 0) $indent -= 1;
		$str = str_repeat('    ', $indent) . $code . "\n";
		if ($indent_change > 0) $indent += 1;

		return $str;
	}
}


class EcwidPlatform {

	static public function esc_attr($value)
	{
		return htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
	}

	static public function esc_html($value)
	{
		return htmlspecialchars($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
	}

	static public function get_price_label()
	{
		return 'Price';
	}

	static public function fetch_url($url)
	{
        $timeout = 90;
        if (!function_exists('curl_init')) {
            return array(
                'code' => '0',
                'data' => 'The libcurl module isn\'t installed on your server. Please contact  your hosting or server administrator to have it installed.'
            );
        }

        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = array();
        if ($error) {
            return array(
                'code' => '0',
                'data' => "libcurl error($errno): $error"
            );
        }

        return array(
            'code' => $httpcode, 
            'data' => $body
        );
	}
}

function ecwid_page_url () {

    $port = ($_SERVER['SERVER_PORT'] == 80 ?  "http://" : "https://");

    $parts = parse_url($_SERVER['REQUEST_URI']);

    $queryParams = array();
    parse_str($parts['query'], $queryParams);
    unset($queryParams['_escaped_fragment_']);

    $queryString = http_build_query($queryParams);
    $url = $parts['path'] . '?' . $queryString;

    return $port . $_SERVER['HTTP_HOST'] . $url;
}

function ecwid_prepare_meta_description($description) {
    if (empty($description)) {
          return "empty";
    }

    $description = strip_tags($description);
    $description = html_entity_decode($description, ENT_NOQUOTES, 'UTF-8');
    $description = preg_replace("![\\s]+!", " ", $description);
    $description = trim($description, " \t\xA0\n\r"); // Space, tab, non-breaking space, newline, carriage return  
    $description = mb_substr($description, 0, 160, 'UTF-8');
    $description = htmlspecialchars($description, ENT_COMPAT | ENT_HTML401, 'UTF-8');

    return $description;
}
 

$ecwid_html_index = $ecwid_title = '';

if (isset($_GET['_escaped_fragment_'])) {
    $catalog = new EcwidCatalog($ecwid_store_id, ecwid_page_url(), $ecwid_token);

    $params = $catalog->parse_escaped_fragment($_GET['_escaped_fragment_']);

    if (isset($params['mode']) && in_array($params['mode'], array('product', 'category'))) {
     
        if ($params['mode'] == 'product') {
            $ecwid_html_index  = $catalog->get_product($params['id']);
            $ecwid_title       = $catalog->get_product_name($params['id']);
            $ecwid_description = $catalog->get_product_description($params['id']);
            $ecwid_canonical   = $catalog->get_product_url($params['id']);

        } elseif ($params['mode'] == 'category') {
            $ecwid_html_index  = $catalog->get_category($params['id']);
            $ecwid_title       = $catalog->get_category_name($params['id']);
            $ecwid_description = $catalog->get_category_description($params['id']);
            $ecwid_canonical   = $catalog->get_category_url($params['id']);
        }

        $ecwid_html_index .= <<<HTML
<script type="text/javascript"> 
if (!document.location.hash) {
  document.location.hash = '!$_GET[_escaped_fragment_]';
}
</script>
HTML;

        $ecwid_description = ecwid_prepare_meta_description($ecwid_description);
    } else {
        $ecwid_html_index = $catalog->get_category(0);
        $ecwid_canonical = ecwid_page_url();
    }
}
