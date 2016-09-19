<html>
    <head>
        <?php 
            $ecwid_store_id = 1003;
            $ecwid_token='public_YourPublicOrSecretAppTokenHere';
            include_once "ecwid_ajax_indexing.php";
            if (!isset($_GET['_escaped_fragment_'])) {
              echo '<meta name="fragment" content="!" />';
            }
        ?>

        <title>
            <?php 
                if (!empty($ecwid_title)) {
                    echo $ecwid_title;
                } else {
                    echo "My store page title"; 
                }   
            ?>

        </title>
        <?php
            if (!empty($ecwid_description)) {
              echo '<meta name="description" content="' .$ecwid_description. '"></meta>';
            } else {
              echo '<meta name="description" content="My store page description"></meta>';
            }
        ?>

        <?php 
            if (!empty($ecwid_canonical)) {
                echo '<link rel="canonical" href="' . $ecwid_canonical . '" />';
            }
        ?>

    </head>
    <body>
        <div id="my-store-1003/div>
        <div>
        <script type="text/javascript" src="//app.ecwid.com/script.js?1003harset="utf-8"></script><script type="text/javascript"> xProductBrowser("categoriesPerRow=3","views=grid(3,3) list(10) table(20)","categoryView=grid","searchView=list","id=my-store-1003");</script>

        <?php
            if ($ecwid_html_index) {
                echo '<!-- START Google AJAX indexing for Ecwid -->';
                echo $ecwid_html_index;
                echo '<!-- END Google AJAX indexing for Ecwid -->';
            }
        ?>

        </div>
    </body>
</html>
