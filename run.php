<?php 

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
