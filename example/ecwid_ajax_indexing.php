<?php
mb_internal_encoding( 'UTF-8' );

// JSON code start
/**
 * Converts to and from JSON format.
 *
 * JSON (JavaScript Object Notation) is a lightweight data-interchange
 * format. It is easy for humans to read and write. It is easy for machines
 * to parse and generate. It is based on a subset of the JavaScript
 * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
 * This feature can also be found in  Python. JSON is a text format that is
 * completely language independent but uses conventions that are familiar
 * to programmers of the C-family of languages, including C, C++, C#, Java,
 * JavaScript, Perl, TCL, and many others. These properties make JSON an
 * ideal data-interchange language.
 *
 * This package provides a simple encoder and decoder for JSON notation. It
 * is intended for use with client-side Javascript applications that make
 * use of HTTPRequest to perform server communication functions - data can
 * be encoded into JSON notation for use in a client-side javascript, or
 * decoded from incoming Javascript requests. JSON format is native to
 * Javascript, and can be directly eval()'ed with no further parsing
 * overhead
 *
 * All strings should be in ASCII or UTF-8 format!
 *
 * LICENSE: Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met: Redistributions of source code must retain the
 * above copyright notice, this list of conditions and the following
 * disclaimer. Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
 * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category
 * @package   Services_JSON
 * @author    Michal Migurski <mike-json@teczno.com>
 * @author    Matt Knapp <mdknapp[at]gmail[dot]com>
 * @author    Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 * @copyright   2005 Michal Migurski
 * @version   CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
 * @license   http://www.opensource.org/licenses/bsd-license.php
 * @link    http://pear.php.net/pepr/pepr-proposal-show.php?id=198
 */

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_SLICE',   1);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_STR',  2);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_ARR',  3);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_OBJ',  4);

/**
 * Marker constant for Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_CMT', 5);

/**
 * Behavior switch for Services_JSON::decode()
 */
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
 * Behavior switch for Services_JSON::decode()
 */
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

/**
 * Converts to and from JSON format.
 *
 * Brief example of use:
 *
 * <code>
 * // create a new instance of Services_JSON
 * $json = new Services_JSON();
 *
 * // convert a complexe value to JSON notation, and send it to the browser
 * $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
 * $output = $json->encode($value);
 *
 * print($output);
 * // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
 *
 * // accept incoming POST data, assumed to be in JSON notation
 * $input = file_get_contents('php://input', 1000000);
 * $value = $json->decode($input);
 * </code>
 */
class Services_JSON
{
   /**
  * constructs a new JSON instance
  *
  * @param  int   $use  object behavior flags; combine with boolean-OR
  *
  *               possible values:
  *               - SERVICES_JSON_LOOSE_TYPE:  loose typing.
  *                   "{...}" syntax creates associative arrays
  *                   instead of objects in decode().
  *               - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
  *                   Values which can't be encoded (e.g. resources)
  *                   appear as NULL instead of throwing errors.
  *                   By default, a deeply-nested resource will
  *                   bubble up with an error, so all return values
  *                   from encode() should be checked with isError()
  */
  function Services_JSON($use = 0)
  {
    $this->use = $use;
  }

   /**
  * convert a string from one UTF-16 char to one UTF-8 char
  *
  * Normally should be handled by mb_convert_encoding, but
  * provides a slower PHP-only method for installations
  * that lack the multibye string extension.
  *
  * @param  string  $utf16  UTF-16 character
  * @return   string  UTF-8 character
  * @access   private
  */
  function utf162utf8($utf16)
  {
    // oh please oh please oh please oh please oh please
    if(function_exists('mb_convert_encoding')) {
      return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
    }

    $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

    switch(true) {
      case ((0x7F & $bytes) == $bytes):
        // this case should never be reached, because we are in ASCII range
        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
        return chr(0x7F & $bytes);

      case (0x07FF & $bytes) == $bytes:
        // return a 2-byte UTF-8 character
        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
        return chr(0xC0 | (($bytes >> 6) & 0x1F))
           . chr(0x80 | ($bytes & 0x3F));

      case (0xFFFF & $bytes) == $bytes:
        // return a 3-byte UTF-8 character
        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
        return chr(0xE0 | (($bytes >> 12) & 0x0F))
           . chr(0x80 | (($bytes >> 6) & 0x3F))
           . chr(0x80 | ($bytes & 0x3F));
    }

    // ignoring UTF-32 for now, sorry
    return '';
  }

   /**
  * convert a string from one UTF-8 char to one UTF-16 char
  *
  * Normally should be handled by mb_convert_encoding, but
  * provides a slower PHP-only method for installations
  * that lack the multibye string extension.
  *
  * @param  string  $utf8   UTF-8 character
  * @return   string  UTF-16 character
  * @access   private
  */
  function utf82utf16($utf8)
  {
    // oh please oh please oh please oh please oh please
    if(function_exists('mb_convert_encoding')) {
      return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
    }

    switch(strlen($utf8)) {
      case 1:
        // this case should never be reached, because we are in ASCII range
        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
        return $utf8;

      case 2:
        // return a UTF-16 character from a 2-byte UTF-8 char
        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
        return chr(0x07 & (ord($utf8{0}) >> 2))
           . chr((0xC0 & (ord($utf8{0}) << 6))
             | (0x3F & ord($utf8{1})));

      case 3:
        // return a UTF-16 character from a 3-byte UTF-8 char
        // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
        return chr((0xF0 & (ord($utf8{0}) << 4))
             | (0x0F & (ord($utf8{1}) >> 2)))
           . chr((0xC0 & (ord($utf8{1}) << 6))
             | (0x7F & ord($utf8{2})));
    }

    // ignoring UTF-32 for now, sorry
    return '';
  }

   /**
  * encodes an arbitrary variable into JSON format
  *
  * @param  mixed   $var  any number, boolean, string, array, or object to be encoded.
  *               see argument 1 to Services_JSON() above for array-parsing behavior.
  *               if var is a strng, note that encode() always expects it
  *               to be in ASCII or UTF-8 format!
  *
  * @return   mixed   JSON string representation of input var or an error if a problem occurs
  * @access   public
  */
  function encode($var)
  {
    switch (gettype($var)) {
      case 'boolean':
        return $var ? 'true' : 'false';

      case 'NULL':
        return 'null';

      case 'integer':
        return (int) $var;

      case 'double':
      case 'float':
        return (float) $var;

      case 'string':
        // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
        $ascii = '';
        $strlen_var = strlen($var);

         /*
        * Iterate over every character in the string,
        * escaping with a slash or encoding to UTF-8 where necessary
        */
        for ($c = 0; $c < $strlen_var; ++$c) {

          $ord_var_c = ord($var{$c});

          switch (true) {
            case $ord_var_c == 0x08:
              $ascii .= '\b';
              break;
            case $ord_var_c == 0x09:
              $ascii .= '\t';
              break;
            case $ord_var_c == 0x0A:
              $ascii .= '\n';
              break;
            case $ord_var_c == 0x0C:
              $ascii .= '\f';
              break;
            case $ord_var_c == 0x0D:
              $ascii .= '\r';
              break;

            case $ord_var_c == 0x22:
            case $ord_var_c == 0x2F:
            case $ord_var_c == 0x5C:
              // double quote, slash, slosh
              $ascii .= '\\'.$var{$c};
              break;

            case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
              // characters U-00000000 - U-0000007F (same as ASCII)
              $ascii .= $var{$c};
              break;

            case (($ord_var_c & 0xE0) == 0xC0):
              // characters U-00000080 - U-000007FF, mask 110XXXXX
              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
              $c += 1;
              $utf16 = $this->utf82utf16($char);
              $ascii .= sprintf('\u%04s', bin2hex($utf16));
              break;

            case (($ord_var_c & 0xF0) == 0xE0):
              // characters U-00000800 - U-0000FFFF, mask 1110XXXX
              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              $char = pack('C*', $ord_var_c,
                     ord($var{$c + 1}),
                     ord($var{$c + 2}));
              $c += 2;
              $utf16 = $this->utf82utf16($char);
              $ascii .= sprintf('\u%04s', bin2hex($utf16));
              break;

            case (($ord_var_c & 0xF8) == 0xF0):
              // characters U-00010000 - U-001FFFFF, mask 11110XXX
              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              $char = pack('C*', $ord_var_c,
                     ord($var{$c + 1}),
                     ord($var{$c + 2}),
                     ord($var{$c + 3}));
              $c += 3;
              $utf16 = $this->utf82utf16($char);
              $ascii .= sprintf('\u%04s', bin2hex($utf16));
              break;

            case (($ord_var_c & 0xFC) == 0xF8):
              // characters U-00200000 - U-03FFFFFF, mask 111110XX
              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              $char = pack('C*', $ord_var_c,
                     ord($var{$c + 1}),
                     ord($var{$c + 2}),
                     ord($var{$c + 3}),
                     ord($var{$c + 4}));
              $c += 4;
              $utf16 = $this->utf82utf16($char);
              $ascii .= sprintf('\u%04s', bin2hex($utf16));
              break;

            case (($ord_var_c & 0xFE) == 0xFC):
              // characters U-04000000 - U-7FFFFFFF, mask 1111110X
              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
              $char = pack('C*', $ord_var_c,
                     ord($var{$c + 1}),
                     ord($var{$c + 2}),
                     ord($var{$c + 3}),
                     ord($var{$c + 4}),
                     ord($var{$c + 5}));
              $c += 5;
              $utf16 = $this->utf82utf16($char);
              $ascii .= sprintf('\u%04s', bin2hex($utf16));
              break;
          }
        }

        return '"'.$ascii.'"';

      case 'array':
         /*
        * As per JSON spec if any array key is not an integer
        * we must treat the the whole array as an object. We
        * also try to catch a sparsely populated associative
        * array with numeric keys here because some JS engines
        * will create an array with empty indexes up to
        * max_index which can cause memory issues and because
        * the keys, which may be relevant, will be remapped
        * otherwise.
        *
        * As per the ECMA and JSON specification an object may
        * have any string as a property. Unfortunately due to
        * a hole in the ECMA specification if the key is a
        * ECMA reserved word or starts with a digit the
        * parameter is only accessible using ECMAScript's
        * bracket notation.
        */

        // treat as a JSON object
        if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
          $properties = array_map(array($this, 'name_value'),
                      array_keys($var),
                      array_values($var));

          foreach($properties as $property) {
            if(Services_JSON::isError($property)) {
              return $property;
            }
          }

          return '{' . join(',', $properties) . '}';
        }

        // treat it like a regular array
        $elements = array_map(array($this, 'encode'), $var);

        foreach($elements as $element) {
          if(Services_JSON::isError($element)) {
            return $element;
          }
        }

        return '[' . join(',', $elements) . ']';

      case 'object':
        $vars = get_object_vars($var);

        $properties = array_map(array($this, 'name_value'),
                    array_keys($vars),
                    array_values($vars));

        foreach($properties as $property) {
          if(Services_JSON::isError($property)) {
            return $property;
          }
        }

        return '{' . join(',', $properties) . '}';

      default:
        return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
          ? 'null'
          : new Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
    }
  }

   /**
  * array-walking function for use in generating JSON-formatted name-value pairs
  *
  * @param  string  $name   name of key to use
  * @param  mixed   $value  reference to an array element to be encoded
  *
  * @return   string  JSON-formatted name-value pair, like '"name":value'
  * @access   private
  */
  function name_value($name, $value)
  {
    $encoded_value = $this->encode($value);

    if(Services_JSON::isError($encoded_value)) {
      return $encoded_value;
    }

    return $this->encode(strval($name)) . ':' . $encoded_value;
  }

   /**
  * reduce a string by removing leading and trailing comments and whitespace
  *
  * @param  $str  string    string value to strip of comments and whitespace
  *
  * @return   string  string value stripped of comments and whitespace
  * @access   private
  */
  function reduce_string($str)
  {
    $str = preg_replace(array(

        // eliminate single line comments in '// ...' form
        '#^\s*//(.+)$#m',

        // eliminate multi-line comments in '/* ... */' form, at start of string
        '#^\s*/\*(.+)\*/#Us',

        // eliminate multi-line comments in '/* ... */' form, at end of string
        '#/\*(.+)\*/\s*$#Us'

      ), '', $str);

    // eliminate extraneous space
    return trim($str);
  }

   /**
  * decodes a JSON string into appropriate variable
  *
  * @param  string  $str  JSON-formatted string
  *
  * @return   mixed   number, boolean, string, array, or object
  *           corresponding to given JSON input string.
  *           See argument 1 to Services_JSON() above for object-output behavior.
  *           Note that decode() always returns strings
  *           in ASCII or UTF-8 format!
  * @access   public
  */
  function decode($str)
  {
    $str = $this->reduce_string($str);

    switch (strtolower($str)) {
      case 'true':
        return true;

      case 'false':
        return false;

      case 'null':
        return null;

      default:
        $m = array();

        if (is_numeric($str)) {
          // Lookie-loo, it's a number

          // This would work on its own, but I'm trying to be
          // good about returning integers where appropriate:
          // return (float)$str;

          // Return float or int, as appropriate
          return ((float)$str == (integer)$str)
            ? (integer)$str
            : (float)$str;

        } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
          // STRINGS RETURNED IN UTF-8 FORMAT
          $delim = substr($str, 0, 1);
          $chrs = substr($str, 1, -1);
          $utf8 = '';
          $strlen_chrs = strlen($chrs);

          for ($c = 0; $c < $strlen_chrs; ++$c) {

            $substr_chrs_c_2 = substr($chrs, $c, 2);
            $ord_chrs_c = ord($chrs{$c});

            switch (true) {
              case $substr_chrs_c_2 == '\b':
                $utf8 .= chr(0x08);
                ++$c;
                break;
              case $substr_chrs_c_2 == '\t':
                $utf8 .= chr(0x09);
                ++$c;
                break;
              case $substr_chrs_c_2 == '\n':
                $utf8 .= chr(0x0A);
                ++$c;
                break;
              case $substr_chrs_c_2 == '\f':
                $utf8 .= chr(0x0C);
                ++$c;
                break;
              case $substr_chrs_c_2 == '\r':
                $utf8 .= chr(0x0D);
                ++$c;
                break;

              case $substr_chrs_c_2 == '\\"':
              case $substr_chrs_c_2 == '\\\'':
              case $substr_chrs_c_2 == '\\\\':
              case $substr_chrs_c_2 == '\\/':
                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                  $utf8 .= $chrs{++$c};
                }
                break;

              case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                // single, escaped unicode character
                $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                     . chr(hexdec(substr($chrs, ($c + 4), 2)));
                $utf8 .= $this->utf162utf8($utf16);
                $c += 5;
                break;

              case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                $utf8 .= $chrs{$c};
                break;

              case ($ord_chrs_c & 0xE0) == 0xC0:
                // characters U-00000080 - U-000007FF, mask 110XXXXX
                //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                $utf8 .= substr($chrs, $c, 2);
                ++$c;
                break;

              case ($ord_chrs_c & 0xF0) == 0xE0:
                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                $utf8 .= substr($chrs, $c, 3);
                $c += 2;
                break;

              case ($ord_chrs_c & 0xF8) == 0xF0:
                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                $utf8 .= substr($chrs, $c, 4);
                $c += 3;
                break;

              case ($ord_chrs_c & 0xFC) == 0xF8:
                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                $utf8 .= substr($chrs, $c, 5);
                $c += 4;
                break;

              case ($ord_chrs_c & 0xFE) == 0xFC:
                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                $utf8 .= substr($chrs, $c, 6);
                $c += 5;
                break;

            }

          }

          return $utf8;

        } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
          // array, or object notation

          if ($str{0} == '[') {
            $stk = array(SERVICES_JSON_IN_ARR);
            $arr = array();
          } else {
            if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
              $stk = array(SERVICES_JSON_IN_OBJ);
              $obj = array();
            } else {
              $stk = array(SERVICES_JSON_IN_OBJ);
              $obj = new stdClass();
            }
          }

          array_push($stk, array('what'  => SERVICES_JSON_SLICE,
                       'where' => 0,
                       'delim' => false));

          $chrs = substr($str, 1, -1);
          $chrs = $this->reduce_string($chrs);

          if ($chrs == '') {
            if (reset($stk) == SERVICES_JSON_IN_ARR) {
              return $arr;

            } else {
              return $obj;

            }
          }

          //print("\nparsing {$chrs}\n");

          $strlen_chrs = strlen($chrs);

          for ($c = 0; $c <= $strlen_chrs; ++$c) {

            $top = end($stk);
            $substr_chrs_c_2 = substr($chrs, $c, 2);

            if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE))) {
              // found a comma that is not inside a string, array, etc.,
              // OR we've reached the end of the character list
              $slice = substr($chrs, $top['where'], ($c - $top['where']));
              array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
              //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

              if (reset($stk) == SERVICES_JSON_IN_ARR) {
                // we are in an array, so just push an element onto the stack
                array_push($arr, $this->decode($slice));

              } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                // we are in an object, so figure
                // out the property name and set an
                // element in an associative array,
                // for now
                $parts = array();
                
                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                  // "name":value pair
                  $key = $this->decode($parts[1]);
                  $val = $this->decode($parts[2]);

                  if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                    $obj[$key] = $val;
                  } else {
                    $obj->$key = $val;
                  }
                } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                  // name:value pair, where name is unquoted
                  $key = $parts[1];
                  $val = $this->decode($parts[2]);

                  if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                    $obj[$key] = $val;
                  } else {
                    $obj->$key = $val;
                  }
                }

              }

            } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
              // found a quote, and we are not inside a string
              array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
              //print("Found start of string at {$c}\n");

            } elseif (($chrs{$c} == $top['delim']) &&
                 ($top['what'] == SERVICES_JSON_IN_STR) &&
                 ((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
              // found a quote, we're in a string, and it's not escaped
              // we know that it's not escaped becase there is _not_ an
              // odd number of backslashes at the end of the string so far
              array_pop($stk);
              //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

            } elseif (($chrs{$c} == '[') &&
                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
              // found a left-bracket, and we are in an array, object, or slice
              array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
              //print("Found start of array at {$c}\n");

            } elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
              // found a right-bracket, and we're in an array
              array_pop($stk);
              //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

            } elseif (($chrs{$c} == '{') &&
                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
              // found a left-brace, and we are in an array, object, or slice
              array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
              //print("Found start of object at {$c}\n");

            } elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
              // found a right-brace, and we're in an object
              array_pop($stk);
              //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

            } elseif (($substr_chrs_c_2 == '/*') &&
                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
              // found a comment start, and we are in an array, object, or slice
              array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
              $c++;
              //print("Found start of comment at {$c}\n");

            } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
              // found a comment end, and we're in one now
              array_pop($stk);
              $c++;

              for ($i = $top['where']; $i <= $c; ++$i)
                $chrs = substr_replace($chrs, ' ', $i, 1);

              //print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

            }

          }

          if (reset($stk) == SERVICES_JSON_IN_ARR) {
            return $arr;

          } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
            return $obj;

          }

        }
    }
  }

  /**
   * @todo Ultimately, this should just call PEAR::isError()
   */
  function isError($data, $code = null)
  {
    if (class_exists('pear')) {
      return PEAR::isError($data, $code);
    } elseif (is_object($data) && (get_class($data) == 'services_json_error' ||
                 is_subclass_of($data, 'services_json_error'))) {
      return true;
    }

    return false;
  }
}

if (class_exists('PEAR_Error')) {

  class Services_JSON_Error extends PEAR_Error
  {
    function Services_JSON_Error($message = 'unknown error', $code = null,
                   $mode = null, $options = null, $userinfo = null)
    {
      parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
    }
  }

} else {

  /**
   * @todo Ultimately, this class shall be descended from PEAR_Error
   */
  class Services_JSON_Error
  {
    function Services_JSON_Error($message = 'unknown error', $code = null,
                   $mode = null, $options = null, $userinfo = null)
    {

    }
  }

}
// JSON code end


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
            . "/products/" . $product_id . '?token=' . $this->ECWID_TOKEN;
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
            . "/categories/" . $category_id . '?token=' . $this->ECWID_TOKEN;
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

	function EcwidCatalog($store_id, $store_base_url, $token)
	{
		if(version_compare(PHP_VERSION,"5.0.0","<"))
		$this->__construct($store_id, $store_base_url, $token);
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
			
			if(isset($product['defaultCategoryId']) && $product['defaultCategoryId'] > 0){
				list($default_category_name, $default_category_url) = $this->get_category_name_and_url($product['defaultCategoryId']);
				$return .= $this->_l('<div class="ecwid_catalog_product_category">' 
					. '<a href="' . EcwidPlatform::esc_attr($default_category_url) . '">'
					. EcwidPlatform::esc_html($default_category_name) . '</a></div>');
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
		} elseif (preg_match('!.*/(p|c)/([0-9]+)!', $fragment, $matches)) {
			$return  = array(
				'mode' => 'p' == $matches[1] ? 'product' : 'category',
				'id' => $matches[2]
			);
		}

		return $return;
	}

	public function get_category_title($id)
	{
		$category = $this->ecwid_api->get_category($id);

		$result = '';
		if (is_array($category)) {
			if (isset($category['seoTitle']) && $category['seoTitle'] != '') { 
				$result = $category['seoTitle'];
			}
			elseif (isset($category['name'])) { 
					$result = $category['name'];
			}
		}

		return $result;
	}

	public function get_category_name_and_url($id)
	{
		$category = $this->ecwid_api->get_category($id);

		$name = '';
		$url='';
		if (is_array($category)) {
			if (isset($category['name'])) { 
				$name = $category['name'];
			}
			if (isset($category['url'])) { 
				$url = $category['url'];
			}
		}

		return array($name, $url);
	}

	public function get_product_title($id)
	{
		$product = $this->ecwid_api->get_product($id);

		$result = '';
		if (is_array($product)) {
			if (isset($product['seoTitle']) && $product['seoTitle'] != '') {
				$result = $product['seoTitle'];
			}elseif (isset($product['name'])){
				$result = $product['name'];
			}
		}

		return $result;
	}


	public function get_category_description($id)
	{
			$category = $this->ecwid_api->get_category($id);

			$result = '';
			if (is_array($category)) {
				if (isset($category['seoDescription']) && $category['seoDescription'] != '') {
					$result = $category['seoDescription'];
				}elseif (isset($category['description'])) {
					$result = $category['description'];
				}
			}

			return $result;
	}

	public function get_product_description($id)
	{
			$product = $this->ecwid_api->get_product($id);

			$result = '';
			if (is_array($product)) {
				if (isset($product['seoDescription']) && $product['seoDescription'] != '') {
					$result = $product['seoDescription'];
				}elseif (isset($product['description'])) {
					$result = $product['description'];
				}
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
            $ecwid_title       = $catalog->get_product_title($params['id']);
            $ecwid_description = $catalog->get_product_description($params['id']);
            $ecwid_canonical   = $catalog->get_product_url($params['id']);

        } elseif ($params['mode'] == 'category') {
            $ecwid_html_index  = $catalog->get_category($params['id']);
            $ecwid_title       = $catalog->get_category_title($params['id']);
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
