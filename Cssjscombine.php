<?php
/**
 * CSS JS Minifier & Combiner
 *     Simple & Fast CSS & Javascript minifier, built only one files and less than 50 kb (with this comment also :D)
 *     + URL & Path helper
 *     just call the call and do it!
 *
 * tested on:
 *     - Code Igniter 2 & 3
 *     - Phalcon
 *     - Slim2 & 3
 *     - fat free
 *     - YII2
 *     - and many more by our project need
 *
 * inspire and use some code from :
 *     JShrink by - Robert Hafner <tedivm@tedivm.com>
 *     @see  {https://github.com/tedious/JShrink}
 *
 * The script is for combine and getting aso minifying assets Javascript and CSS
 * The script handle and minify css and javascript and combining multiple files
 * 
 * + Successfully about re-minifying of jquery minified version without issues
 * + High compression minify
 * + Fix for url() css rule ( with custom path or custom url)
 * + support allowing conditional comment that start /*! on javascript or you want it remove
 * + allow to show first comment to know what the css start for information
 *
 * Donation via @paypal are wellcome -> nawa@yahoo.com
 *
 * @package CSSJSMinifierCombiner
 * @version 1.0
 * @author  awan <nawa@yahoo.com>
 * @license GPL3/+
 */

/**
 * CssJsCombine
 *
 * NOTE :
 * If you use Code Igniter you could ->
 * Put on your application/([models / library] folder)/Cssjscombine.php
 * and call before you run the class
 * eg : on your controller index() or any where that you could load it
 *
 * if you use as library just call
 * $this->load->model('cssjscombine');
 *
 * or you put on library directory
 * $this->load->library('cssjscombine');
 *
 * and to doing minify :
 * 
 * $this->cssjscombine->minifyCSS('csstexthere'); // for css
 * $this->cssjscombine->minifyJS('jstexthere');   // for javascript
 *
 * Or if you want to cobine JS or CSS files from your sub directory of web
 * just set with:
 *
 * $this->cssjscombine->combine(
 *     array(
 *         '/full/path/to/your/script/filecss1.css',
 *         '/full/path/to/your/script/css/filecss2.css',
 *         '/full/path/to/your/script/css/filecss2.css',
 *         // etc etc ....
 *     ),
 *     false, // (boolean true|false) if you want print first comment if possible to your combined
 *           // files js / css per files request
 *     false, // (boolean true|false) if you want print conditional comment if possible to your combined
 *           // files js only (show conditional comment start with /*! on javascript will be ignored to replaced)
 * );
 *
 * please make sure you have only set if it css list with css files not js
 * or you want combine js you must list with file of js.
 *
 * If you want know what this class functions works, please read the comment on each method / functions.
 *
 * This script not returning error if failed parsing , but will be record as error_logs
 * call :
 * (classof) CssJsCombine->getErrorLog();
 * to get an array of error
 */
class CssJsCombine
{

    /* -------------------------------------------------------------+
     *                           PROPERTY                           |
     * -------------------------------------------------------------+
     */

    /**
     * Ignored Javascript to minified
     *     this contains regex , please dont use modifier here!
     *     eg: if want to add regex ignore all with minified
     *         just set => (.+).min.js
     * @var array
     */
    protected $ignored_js_minify = array(
        // '(.+).min.js' => true, # no for min for example
    );

    /**
     * The index_input javascript to be minified.
     *
     * @var string
     */
    protected $index_input;

    /**
     * Ceched Proccess
     * @var null
     */
    protected $all_cached = null;

    /**
     * The location of the character (in the index_input string) that is next to be
     * processed.
     *
     * @var int
     */
    protected $index_position = 0;

    /**
     * The first of the characters currently being looked at.
     *
     * @var string
     */
    protected $index_a = '';

    /**
     * The next character being looked at (after a);
     *
     * @var string
     */
    protected $index_b = '';

    /**
     * This character is only active when certain look ahead actions take place.
     *
     *  @var string
     */
    protected $index_c;

    /**
     * Contains lock ids which are used to replace certain code patterns and
     * prevent them from being minified
     *
     * @var array
     */
    protected $index_locks = array();

    /**
     * Just aditional resouce record to prevent 
     * Throws error
     * @var boolean
     */
    protected $is_failed = false;

    /**
     * Base directory (eg root path of script executed for the right uses)
     * @var string
     */
    protected $base_directory = null;

    /**
     * Base URL of site
     * @var string
     */
    protected $base_url = null;

    /**
     * Error Logs
     * @var  array
     */
    protected $error_log = array();

    /**
     * This like a __construct()
     * change this into __construct($base_directory = null, $base_url = null)
     * @param  string $base_directory root directory of script execute / web root
     * @param  string $base_url       root url / base url or web root URI
     */
    public function cssJsCombine()
    {
        // base directory
        // on CI use FCPATH for Root path but we have fix with our method (:
        $this->base_directory = $this->currentRootpath();
        $this->base_url = $this->baseUrl();
    }

    /**
     * Set base URL
     * @param  string $base_url root url / base url or web root URI
     */
    public function setBaseUrl($base_url)
    {
        $this->base_url = $base_url;
    }

    /**
     * Set Base directory
     * @param  string $base_directory root directory of script execute / web root
     */
    public function setBaseDirectory($base_directory)
    {
        $this->base_directory = $base_directory;
    }

    /**
     * Add regex filename to ignored
     * @param  string $value regex
     */
    public function addJsFileNameIgnoredRegex($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (is_string($val) && trim($val)) {
                    $this->ignored_js_minify[$val] = true;
                }
            }

            return;
        }

        if (is_string($value) && trim($value)) {
            $this->ignored_js_minify[$value] = true;
        }
    }

    /**
     * Reset Regex Ignored Files to empty array
     */
    public function clearJsFileIgnoredRegex()
    {
        $this->ignored_js_minify = array();
    }

    /**
     * Remove regex files to ignored
     * @param  string $value regex as key
     */
    public function removeJsFileNameIgnoredRegex($value)
    {
        if (is_string($value) && trim($value)) {
            unset($this->ignored_js_minify[$value]);
        }
    }

    /* -------------------------------------------------------------+
     *                         PROCCESSOR                           |
     * -------------------------------------------------------------+
     */

    /**
     * Takes a string containing javascript / css and removes unneeded characters in
     * order to shrink the code without altering it's functionality.
     * By combining & minify of files certain as arrat
     *
     * @param  array|string     $files_to_read                 ful path or url to geti minified and combine it
     * @param  bool             $use_first_comment             show first comment to print into output
     * @param  bool             $allowed_conditional_comment   show conditional comment start with /*! on javascript will be ignored to replaced
     * @return bool|string
     */
    public function combine(
        $files_to_read = null,
        $use_first_comment = false,
        $allowed_conditional_comment = false
    ) {
        if (is_array($files_to_read)) {
            foreach ($files_to_read as $key => $value) {
                unset($files_to_read[$key]);
                if (is_string($value) && trim($value) && $tasked = $this->Combine(
                        $value,
                        $use_first_comment,
                        $allowed_conditional_comment
                    )
                ) {
                    $files_to_read[$key] = $tasked;
                    $tasked = '';
                }
            }

            if (!empty($files_to_read)) {
                return implode("\n", $files_to_read);
            }
        }
        if (is_string($files_to_read) && trim($files_to_read)) {
            if (!($use_http = preg_match('/(ht|f)tps?:\/\//i', $files_to_read)) // if not contain url ftp|http(s)
                && (is_file($files_to_read) && is_readable($files_to_read))
                || true
            ) {
                $ext = strtolower(pathinfo($files_to_read, PATHINFO_EXTENSION));
                $retval = '';
                if (!in_array($ext, array('js', 'css'))) {
                    return null;
                }

                /**
                 * Check if use local
                 */
                if (!$use_http && function_exists('fopen')) {
                    $retval = null;
                    if ($fp = @fopen($files_to_read, 'r+')) {
                        while (($buffer = fgets($fp, 4096)) !== false) {
                            $retval .= $buffer;
                        }
                        fclose($fp);
                    }
                } else {
                    if ($use_http) {
                        $ctx = stream_context_create(array('http'=>
                            array(
                                'timeout' => 10,  // 10 seconds has very very long time!!
                            )
                        ));
                        $retval = @file_get_contents($files_to_read, false, $ctx);
                    } else {
                        $retval = @file_get_contents($files_to_read, false);
                    }
                }
                if (is_string($retval)) {
                    if ($ext == 'js') {
                        foreach ($this->ignored_js_minify as $key => $v) {
                            if (preg_match('#'.$key.'$#', $files_to_read)) {
                                return $retval;
                            }
                        }
                    }

                    return $ext == 'js'
                        ? $this->internalMinifyJS($retval, $use_first_comment)
                        : $this->internalFixCSS($this->minifyCss($retval, $use_first_comment), $files_to_read);
                }
            }

            return '';
        }

        return null;
    }

    /* -------------------------------------------------------------+
     *                       CSS MINIFIER                           |
     * -------------------------------------------------------------+
     */

    /**
     * Minify css
     * @param  string  $cssText           css to minify
     * @param  boolean $use_first_comment put first comment of css for informational only
     * @param  string  $url_replacer      url assets directory placed
     * @return string                     minified css
     */
    public function minifyCss(
        $cssText,
        $use_first_comment = false,
        $url_replacer = null
    ) {
        if (!is_string($cssText)) {
            return false;
        }

        $first_comment = '';
        if ($use_first_comment) {
            // get first comment
            preg_match('%^\s*/\*(?:[^*]|[\r\n]|(?:\*+(?:[^*/]|[\r\n])))*\*+/%', $cssText, $match);
            if (!empty($match[0])) {
                $first_comment = "{$match[0]}\n";
            }
        }

        // remove comments
        $cssText = preg_replace('/(^\s*|\s*$|\/\*(?:(?!\*\/)[\s\S])*\*\/|[\r\n\t]+)/', '', $cssText);
        // remove multi twice characters into 3 characters sequence eg : #ffddee to #fde
        $cssText = preg_replace(
            '/(#(?:([a-f]|[A-F]|[0-9]){1}(?:\\2)([a-f]|[A-F]|[0-9]){1}(?:\\3))([a-f]|[A-F]|[0-9]){1}(?:\\4))\b/',
            '#$2$3$4',
            $cssText
        );
        $regex = '(?six)
                  \s*+;\s*(})\s*
                | \s*([*$~^|]?=|[{};,>~+-]|\s+!important\b)\s*
                | ([[(:])\s+
                | \s+([\]\)])
                | \s+(:)\s+
                (?!
                    (?>
                        [^{}"\']++
                        | \"(?:[^"\\\\]++|\\\\.)*\"
                        | \'(?:[^\'\\\\]++|\\\\.)*\' 
                    )*
                    {
                )
                | ^\s+|\s+ \z
                | (\s)\s+
                | (\#((?:[a-f]|[A-F]|[0-9]){3}))(?:\\2)?\b # replace same value hex digit to 3 character eg : #ffffff to #fff
                ';

        /**
         * Fix css url('statmenturi/');
         */
        if (is_string($url_replacer) && trim($url_replacer) && preg_match('/^(https?:)?\/\//', $url_replacer) === 0) {
            $cssText = $this->internalFixCSS($text, $url_replacer);
        }

        return $first_comment.preg_replace("%{$regex}%", '$1$2$3$4$5$6$7', $cssText);
    }

    /**
     * Fix css url path that enclosed with ../ to better uses
     * @param  string $text css text
     * @param  string $path url assets directory placed
     * @return string           sanitized css
     */
    public function fixCSSUri($text, $path)
    {
        if (!$text || !is_string($text) || !trim($text) || !is_string($path)) {
            return $text;
        }
        if (preg_match('/url\(.+?\)/i', $text)) {
            $ci   = $this;
            $text = preg_replace_callback('/url\((.+?)\)/ixm', function ($c) use($path, $ci) {
                $detach = trim($c[1]);
                $detach = trim($c[1], '"');
                $detach = trim($c[1], '\'');
                if (!preg_match('/^(https?:)?\/\//i', $detach)) {
                    $detach = preg_replace('/(\/|\\\)+/', '/', $detach);
                    $c[0] = rtrim($path, '/').'/'.ltrim($detach, '/');
                    $c[0] = strpos($c[0], '\'') !== false ? json_encode("{$c[0]}") : "'{$c[0]}'";
                    $c[0] = "url({$c[0]})";
                }
                return $c[0];
            }, $text);
        }

        return $text;
    }

    /**
     * Fix css url path that enclosed with ../ to better uses
     *     This methdo for internakl Use Only
     * @access private
     * @param  string $text css text
     * @param  string $path path to css file / full path
     * @return string sanitized css
     */
    private function internalFixCSS($text, $path)
    {
        if (!$text || !is_string($text) || !trim($text)) {
            return $text;
        }
        if (preg_match('/url\(.+?\)/i', $text)) {
            $ci   = $this;
            $text = preg_replace_callback('/url\((.+?)\)/ixm', function ($c) use($path, $ci) {
                $detach = trim($c[1]);
                $detach = trim($c[1], '"');
                $detach = trim($c[1], '\'');
                if (!preg_match('/^(https?:)?\/\//i', $detach)) {
                    $base_url   = $ci->base_url;
                    $detach_dir = realpath(dirname($path).'/'.dirname($detach));
                    $FCPATH = preg_replace('/(\/|\\\)+/', '/', $this->base_directory);
                    $detach_dir = preg_replace('/(\/|\\\)+/', '/', $detach_dir);
                    $detach_dir = (substr($detach_dir, strlen($FCPATH)));
                    $c[0] = preg_replace(
                        '/^https?:\/\//i',
                        '',

                        // base URL
                        // take from base URL of CI
                        trim(rtrim($base_url, '/').'/'.(trim($detach_dir, '/').'/'.basename($detach)))
                    );
                    $c[0] = strpos($c[0], '\'') !== false ? json_encode("//{$c[0]}") : "'//{$c[0]}'";
                    $c[0] = "url({$c[0]})";
                }
                return $c[0];
            }, $text);
        }

        return $text;
    }


    /* -------------------------------------------------------------+
     *                     JAVASCRIPT MINIFIER                      |
     * -------------------------------------------------------------+
     */

    /**
     * Takes a string containing javascript and removes unneeded characters in
     * order to shrink the code without altering it's functionality.
     *
     * @param  string      $js                            The raw javascript to be minified
     * @param  bool        $use_first_comment             show first comment to print into output
     * @param  bool        $allowed_conditional_comment   show conditional comment start with /*! on javascript will be ignored to replaced
     * @return bool|string
     */
    public function minifyJS(
        $js,
        $use_first_comment = false,
        $allowed_conditional_comment = false
    ) {
        if (!is_string($js) || !trim($js)) {
            return false;
        }
        
        /**
         * Clean the cached string
         * this method use clean all
         * @var null
         */
        $this->cleanAll();

        /**
         * Doing progress
         */
        $js = $this->lock($js);
        $this->minifyDirectToOutput($js);
        $this->unlock($this->all_cached);
        // celan again just use clean reset
        $this->cleanCached();
        if ($this->is_failed) {
            return false;
        }

        /**
         * Getting first comment if available and allow to print out
         * @var string
         */
        $first_comment = '';
        if ($use_first_comment && preg_match('%^\s*/\*(?:[^*]|[\r\n]|(?:\*+(?:[^*/]|[\r\n])))*\*+/%', $js, $match)
            && !empty($match[0])
        ) {
            $first_comment = trim($match[0])."\n";
            unset($match);
        }
        // freed the memory
        unset($js);

        // remove comments if not allowed conditional comment
        if (! $allowed_conditional_comment) {
            $this->all_cached = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '$1', $this->all_cached);
        }

        /**
         * Sanitized and and minfiy it!
         */
        $this->all_cached = preg_replace("/(\n)+/",'$1', $this->all_cached);
        $this->all_cached = preg_replace("/\n?(\{|\})\n?/",'$1', $this->all_cached);
        $this->all_cached = preg_replace('/;\s*\}|\s*}\s*/', '}', $this->all_cached);
        $this->all_cached = preg_replace('/;\n/', ';', $this->all_cached);
        /**
         * If position of ouput on first statments is it will be exec fix clean
         * comments
         */
        if (strpos(trim($this->all_cached), '/*!') === 0) {
            $this->all_cached = preg_replace('/(\/\*\!(?:[^*]|(?:\*+[^*\/]))*\*+\/)\s*/', "$1\n", $this->all_cached);
        }

        /**
         * If allowed conditional comment fix replace inside of
         */
        if ($allowed_conditional_comment) {
            $this->all_cached = preg_replace("/(\/\*\!)/", "\n$1", $this->all_cached);
            $this->all_cached = preg_replace("/\n *\*/m", "\n*", $this->all_cached);
        }

        /**
         * Trim outut result
         */
        $this->all_cached = trim($this->all_cached);
        return $first_comment.$this->all_cached;
    }

    /**
     * Takes a string containing javascript and removes unneeded characters in
     * order to shrink the code without altering it's functionality.
     *     this for internal Use Only
     *
     * @access private
     * @param  string      $js                            The raw javascript to be minified
     * @param  bool        $use_first_comment             show first comment to print into output
     * @param  bool        $allowed_conditional_comment   show conditional comment start with /*! on javascript will be ignored to replaced
     * @return bool|string
     */
    private function internalMinifyJS(
        $text,
        $use_first_comment = false,
        $allowed_conditional_comment = false
    ) {
        if (!is_string($text)) {
            return false;
        }
        // doing clean
        $this->cleanAll();
        $this->all_cached = $this->minifyJS(
            $text,
            $use_first_comment,
            $allowed_conditional_comment
        );
        return $this->all_cached;
    }

    /**
     * Processes a javascript string and outputs only the required characters,
     * stripping out all unneeded characters.
     *
     * @param string $js      The raw javascript to be minified
     * @param array  $options Various runtime options in an associative array
     */
    protected function minifyDirectToOutput($js)
    {
        if ($this->is_failed) {
            return null;
        }

        // $this->options = array_merge($this->$defaultOptions, $options);
        $js = str_replace("\r\n", "\n", $js);
        $this->index_input = str_replace("\r", "\n", $js);

        // We add a newline to the end of the script to make it easier to deal
        // with comments at the bottom of the script- this prevents the unclosed
        // comment error that can otherwise occur.
        $this->index_input .= PHP_EOL;

        // Populate "a" with a new line, "b" with the first character, before
        // entering the loop
        $this->index_a = "\n";
        $this->index_b = $this->getReal();

        $this->loop();
        $this->cleanCached();
    }

    /**
     * The primary action occurs here. This function loops through the index_input string,
     * outputting anything that's relevant and discarding anything that is not.
     */
    protected function loop()
    {
        while ($this->index_a !== false && !is_null($this->index_a) && $this->index_a !== '') {
            switch ($this->index_a) {
                // new lines
                case "\n":
                    // if the next line is something that can't stand alone preserve the newline
                    if (strpos('(-+{[@', $this->index_b) !== false) {
                        $this->all_cached .= $this->index_a;
                        $this->saveString();
                        break;
                    }

                    // if B is a space we skip the rest of the switch block and go down to the
                    // string/regex check below, resetting $this->index_b with getReal
                    if($this->index_b === ' ')
                        break;

                // otherwise we treat the newline like a space

                case ' ':
                    if($this->isAlphaNumeric($this->index_b)) {
                        $this->all_cached .=  $this->index_a;
                    }

                    $this->saveString();
                    break;

                default:
                    switch ($this->index_b) {
                        case "\n":
                            if (strpos('}])+-"\'', $this->index_a) !== false) {
                                $this->all_cached .= $this->index_a;
                                $this->saveString();
                                break;
                            } else {
                                if ($this->isAlphaNumeric($this->index_a)) {
                                    $this->all_cached .= $this->index_a;
                                    $this->saveString();
                                }
                            }
                            break;

                        case ' ':
                            if(!$this->isAlphaNumeric($this->index_a))
                                break;

                        default:
                            // check for some regex that breaks stuff
                            if ($this->index_a == '/' && ($this->index_b == '\'' || $this->index_b == '"')) {
                                $this->saveRegex();
                                continue;
                            }

                            $this->all_cached .= $this->index_a;
                            $this->saveString();
                            break;
                    }
            }

            // do reg check of doom
            $this->index_b = $this->getReal();

            if(($this->index_b == '/' && strpos('(,=:[!&|?', $this->index_a) !== false)) {
                $this->saveRegex();
            }
        }
    }

    /**
     * Resets attributes that do not need to be stored between requests so that
     * the next request is ready to go. Another reason for this is to make sure
     * the variables are cleared and are not taking up memory.
     */
    protected function cleanCached()
    {
        $this->index_input = null;
        $this->index_position = 0;
        $this->index_a   = $this->index_b = '';
        $this->index_c   = null;
        $this->is_failed = false;
        $this->index_locks = array();
    }

    /**
     * Clean all record,
     *     best to call on end of call result
     *     and you should call this to freed your memory ::[recommended]
     */
    public function cleanAll()
    {
        $this->cleanCached();
        $this->all_cached = null;
        $this->error_log = array();
    }

    /**
     * Returns the next string for processing based off of the current index_position.
     *
     * @return string
     */
    protected function getChar()
    {
        // Check to see if we had anything in the look ahead buffer and use that.
        if (isset($this->index_c)) {
            $char = $this->index_c;
            unset($this->index_c);

        // Otherwise we start pulling from the index_input.
        } else {
            $char = substr($this->index_input, $this->index_position, 1);

            // If the next character doesn't exist return false.
            if (isset($char) && $char === false) {
                return false;
            }

            // Otherwise increment the pointer and use this char.
            $this->index_position++;
        }

        // Normalize all whitespace except for the newline character into a
        // standard space.
        if($char !== "\n" && ord($char) < 32) {
            return ' ';
        }

        return $char;
    }

    /**
     * This function gets the next "real" character. It is essentially a wrapper
     * around the getChar function that skips comments. This has significant
     * performance benefits as the skipping is done using native functions (ie,
     * c code) rather than in script php.
     *
     *
     * @return string            Next 'real' character to be processed.
     * @throws \RuntimeException
     */
    protected function getReal()
    {
        $startIndex_position = $this->index_position;
        $char = $this->getChar();

        // Check to see if we're potentially in a comment
        if ($char !== '/') {
            return $char;
        }

        $this->index_c = $this->getChar();

        if ($this->index_c == '/') {
            return $this->processOneLineComments($startIndex_position);
        } elseif ($this->index_c == '*') {
            return $this->processMultiLineComments($startIndex_position);
        }

        return $char;
    }

    /**
     * Removed one line comments, with the exception of some very specific types of
     * conditional comments.
     *
     * @param  int    $startIndex_position The index_position point where "getReal" function started
     * @return string
     */
    protected function processOneLineComments($startIndex_position)
    {
        $thirdCommentString = substr($this->index_input, $this->index_position, 1);

        // kill rest of line
        $this->getNext("\n");

        if ($thirdCommentString == '@') {
            $endPoint = ($this->index_position) - $startIndex_position;
            unset($this->index_c);
            $char = "\n" . substr($this->index_input, $startIndex_position, $endPoint);
        } else {
            // first one is contents of $this->index_c
            $this->getChar();
            $char = $this->getChar();
        }

        return $char;
    }

    /**
     * Skips multiline comments where appropriate, and includes them where needed.
     * Conditional comments and "license" style blocks are preserved.
     *
     * @param  int               $startIndex_position The index_position point where "getReal" function started
     * @return bool|string       False if there's no character
     * @throws \RuntimeException Unclosed comments will throw an error
     */
    protected function processMultiLineComments($startIndex_position)
    {
        if ($this->is_failed) {
            return null;
        }

        $this->getChar(); // current C
        $thirdCommentString = $this->getChar();

        // kill everything up to the next */ if it's there
        if ($this->getNext('*/')) {

            $this->getChar(); // get *
            $this->getChar(); // get /
            $char = $this->getChar(); // get next real character

            // Now we reinsert conditional comments and YUI-style licensing comments
            if (($thirdCommentString == '!') || ($thirdCommentString == '@') ) {

                // If conditional comments or flagged comments are not the first thing in the script
                // we need to echo a and fill it with a space before moving on.
                if ($startIndex_position > 0) {
                    $this->all_cached .= $this->index_a;
                    $this->index_a = " ";

                    // If the comment started on a new line we let it stay on the new line
                    if ($this->index_input[($startIndex_position - 1)] == "\n") {
                        $this->all_cached .= "\n";
                    }
                }

                $endPoint = ($this->index_position - 1) - $startIndex_position;
                $this->all_cached .= substr($this->index_input, $startIndex_position, $endPoint);

                return $char;
            }

        } else {
            $char = false;
        }

        if($char === false) {
            $this->is_failed = true;
            $this->error_log[] = ('Unclosed multiline comment at position: ' . ($this->index_position - 2));
            return null;
        }

        // if we're here c is part of the comment and therefore tossed
        if(isset($this->index_c)) {
            unset($this->index_c);
        }

        return $char;
    }

    /**
     * Pushes the index_position ahead to the next instance of the supplied string. If it
     * is found the first character of the string is returned and the index_position is set
     * to it's position.
     *
     * @param  string       $string
     * @return string|false Returns the first character of the string or false.
     */
    protected function getNext($string)
    {
        // prevent execute
        if ($this->is_failed) {
            return null;
        }

        // Find the next occurrence of "string" after the current position.
        $pos = strpos($this->index_input, $string, $this->index_position);

        // If it's not there return false.
        if($pos === false) {
            return false;
        }

        // Adjust position of index_position to jump ahead to the asked for string
        $this->index_position = $pos;

        // Return the first character of that string.
        return substr($this->index_input, $this->index_position, 1);
    }

    /**
     * When a javascript string is detected this function crawls for the end of
     * it and saves the whole string.
     *
     * @throws \RuntimeException Unclosed strings will throw an error
     */
    protected function saveString()
    {
        if ($this->is_failed) {
            return null;
        }

        $startpos = $this->index_position;

        // saveString is always called after a gets cleared, so we push b into
        // that spot.
        $this->index_a = $this->index_b;

        // If this isn't a string we don't need to do anything.
        if ($this->index_a != "'" && $this->index_a != '"') {
            return;
        }

        // String type is the quote used, " or '
        $stringType = $this->index_a;

        // Echo out that starting quote
        $this->all_cached .= $this->index_a;

        // Loop until the string is done
        while (1) {

            // Grab the very next character and load it into a
            $this->index_a = $this->getChar();
            // exec this
            if ($this->is_failed) {
                return null;
            }
            switch ($this->index_a) {

                // If the string opener (single or double quote) is used
                // output it and break out of the while loop-
                // The string is finished!
                case $stringType:
                    break 2;

                // New lines in strings without line delimiters are bad- actual
                // new lines will be represented by the string \n and not the actual
                // character, so those will be treated just fine using the switch
                // block below.
                case "\n":
                    $this->is_failed = true;
                    $this->error_log[] = ('Unclosed string at position: ' . $startpos );
                    return null;
                    break;

                // Escaped characters get picked up here. If it's an escaped new line it's not really needed
                case '\\':

                    // a is a slash. We want to keep it, and the next character,
                    // unless it's a new line. New lines as actual strings will be
                    // preserved, but escaped new lines should be reduced.
                    $this->index_b = $this->getChar();

                    // If b is a new line we discard a and b and restart the loop.
                    if ($this->index_b == "\n") {
                        break;
                    }

                    // echo out the escaped character and restart the loop.
                    $this->all_cached .= $this->index_a . $this->index_b;
                    break;


                // Since we're not dealing with any special cases we simply
                // output the character and continue our loop.
                default:
                    $this->all_cached .= $this->index_a;
            }
        }
    }

    /**
     * When a regular expression is detected this function crawls for the end of
     * it and saves the whole regex.
     *
     * @throws \RuntimeException Unclosed regex will throw an error
     */
    protected function saveRegex()
    {
        if ($this->is_failed) {
            return null;
        }

        $this->all_cached .=  $this->index_a . $this->index_b;

        while (($this->index_a = $this->getChar()) !== false) {
            if ($this->is_failed) {
                return null;
            }
            if($this->index_a == '/') {
                break;
            }
            if ($this->index_a == '\\') {
                $this->all_cached .= $this->index_a;
                $this->index_a = $this->getChar();
            }

            if($this->index_a == "\n") {
                $this->is_failed = true;
                $this->error_log[] = ('Unclosed regex pattern at position: ' . $this->index_position);
                return null;
            }

            $this->all_cached .= $this->index_a;
        }

        $this->index_b = $this->getReal();
    }

    /**
     * Checks to see if a character is alphanumeric.
     *
     * @param  string $char Just one character
     * @return bool
     */
    protected function isAlphaNumeric($char)
    {
        return preg_match('/^[\w\$]$/', $char) === 1 || $char == '/';
    }

    /**
     * Replace patterns in the given string and store the replacement
     *
     * @param  string $js The string to lock
     * @return bool
     */
    protected function lock($js)
    {
        // no execute failed
        if ($this->is_failed) {
            return null;
        }

        /* lock things like <code>"asd" + ++x;</code> */
        $lock = '"LOCK---' . crc32(time()) . '"';
        $matches = array();
        preg_match('/([+-])(\s+)([+-])/', $js, $matches);
        if (empty($matches)) {
            return $js;
        }

        $this->index_locks[$lock] = $matches[2];

        $js = preg_replace('/([+-])\s+([+-])/', "$1{$lock}$2", $js);
        /* -- */

        return $js;
    }

    /**
     * Replace "locks" with the original characters
     *
     * @param  string $js The string to unlock
     * @return bool
     */
    protected function unlock($js)
    {
        // if got failed exit!!
        if ($this->is_failed) {
            return null;
        }

        $this->all_cached = $js;
        if (!count($this->index_locks)) {
            return $js;
        }

        foreach ($this->index_locks as $lock => $replacement) {
            $this->all_cached = str_replace($lock, $replacement, $js);
        }

        return $this->all_cached;
    }

    /* -------------------------------------------------------------+
     *                           HELPER                             |
     * -------------------------------------------------------------+
     */
    /**
     * Geting error Logs record if exists
     *
     * @return array
     */
    public function getErrorLog()
    {
        return $this->error_log;
    }

    /**
     * Get Host
     *
     * @return string
     */
    public function getHost()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            if (strpos($_SERVER['HTTP_HOST'], ':') !== false) {
                $hostParts = explode(':', $_SERVER['HTTP_HOST']);

                return $hostParts[0];
            }

            return $_SERVER['HTTP_HOST'];
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }

        return null;
    }

    /**
     * Get Port
     *
     * @return int
     */
    public function getPort()
    {
        static $port;
        if ($port === null) {
            $port = isset($_SERVER['SERVER_PORT']) ? intval($_SERVER['SERVER_PORT']) : null;
        }

        return $port;
    }

    /**
     * Determine Protocol used.
     *
     * @return string $http_protocol https / http
     */
    public function httpProtocol()
    {
        static $Protocol;

        if (! $Protocol || ! in_array($Protocol, array('http', 'https'))) {
            $Protocol = 'http';
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
                || !empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https'
            ) {
                $_SERVER['HTTPS'] = 'on';
            }

            $s = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : false;
            if ($s == 'on' || $s == '1') {
                $Protocol = 'https';
            } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS'])
                && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off'
            ) {
                $Protocol = 'https';
            }
        }

        return $Protocol; #returning the protocols used
    }

    /**
     * Clan Invalid Slashed to be only one slashed on separate
     *
     * @param  mixed $path  path to be cleaned
     */
    public function cleanSlashed($path)
    {
        if (is_array($path)) {
            foreach ($path as $key => $value) {
                $path[$key] = $this->cleanSlashed($value);
            }
        }
        if (is_object($path)) {
            foreach (get_object_vars($path) as $key => $value) {
                $path->{$key} = $this->cleanSlashed($value);
            }
        }
        if (is_string($path)) {
            static $path_tmp = array();
            $path_tmp[$path] = isset($path_tmp[$path])
                ? $path_tmp[$path]
                : preg_replace('/(\\\|\/)+/', '/', $path);
            return $path_tmp[$path];
        }

        return $path;
    }

    /**
     * Checking current request File as Root path
     * @return string base directory of root path
     */
    public function currentRootpath()
    {
        static $root;
        if (!$root) {
            $root =  $this->cleanSlashed(dirname($_SERVER['SCRIPT_FILENAME']));
        }
        return $root;
    }

    /**
     * Get URL (scheme + host [ + port if non-standard ])
     *
     * @return string
     */
    public function getUrl()
    {
        $url = $this->httpProtocol() . '://' . $this->getHost();
        if (($this->httpProtocol() === 'https' && $this->getPort() !== 443)
            || ($this->httpProtocol() === 'http' && $this->getPort() !== 80)
        ) {
            $url .= sprintf(':%s', $this->getPort());
        }

        return $url;
    }

    /**
     * Gettng base url detect by script of root executed
     *
     * @param  string $path path after URL
     * @return string $uri  url
     */
    public function baseUrl($path = '')
    {
        static $url;
        if (!$url) {
            $url  = $this->getUrl();
            $url .= $this->getPathAfterDocumentRoot($this->currentRootpath());
        }
        $uri = rtrim($url, '/');
        settype($path, 'string');
        if (trim($path)) {
            $uri .= '/'.ltrim($path, '/');
        }
        return $uri;
    }

    /**
     * get DOCUMENT_ROOT of the web
     *
     * @return  string path document root
     */
    public function documentRoot()
    {
        static $root;
        if (is_null($root)) {
            $directory = !empty($_SERVER['DOCUMENT_ROOT'])
                ? realpath($_SERVER['DOCUMENT_ROOT'])
                : (
                    !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])
                    ? $_SERVER['CONTEXT_DOCUMENT_ROOT']
                    :  substr(
                        rtrim($this->currentRootpath(), '/'),
                        0,
                        -(rtrim(strlen(dirname($_SERVER['SCRIPT_NAME'])), '/'))
                    )
                );
                $root = rtrim($this->cleanSlashed($directory), '/');
         }

        return $root;
    }

    /**
     * Geting path after Document root Path , this will be shown after
     *     Root path only
     * @param  string $path the path to be clean, make sure to get right values
     *                      checking path (path) must be check on after root path
     * @return string       if match path with root path
     */
    public function getPathAfterDocumentRoot($path)
    {
        $root = $this->documentRoot();
        $path = rtrim($this->cleanSlashed($path), '/');
        if (strpos($path, $root) !== false) {
            $path =  substr($path, strlen($root));
            return '/'.trim($path, '/');
        }

        return null;
    }
}
