ecwid-seo
=========

* ecwid_json.php: json for use on older PHP without built in json functions
* ecwid_catalog.php: all ajax-content building functionality
* ecwid_product_api.php: Ecwid product API wrapper
* ecwid_platform.php: contains platform-specific functions. They are to be redefined in WordPress, Joomla etc., and for a standalone version these are used
* ecwid_misc.php: other functions
* run.php: the code that actually runs the processing. Since the original script produced its output into php variables, we also need that run code
* build.php: compiles all files into one. `php build.php > example/ecwid_ajax_indexing.php` produces a compiled file with that name

2016-09-19 - Uses v3 of ecwid API and supports older versions of PHP

You need an [access token](https://developers.ecwid.com/api-documentation#access-tokens) (either private or public) which you can get by registering an app and installing that app on the ecwid shop.

Set `$ecwid_store_id` to your store id, and `$ecwid_token` to your access token before including `ecwid_ajax_indexing.php`.
See `example/index.php`.

---

## history

* branch dev-v3 - convert to use ecwid API v3 ahead of v1 API removal in Feb 2017
