<?php

/**
 * Created by PhpStorm.
 * User: Dmitriy Bondarenko
 * Date: 7/27/2016
 * Time: 1:14 AM
 */

class CurlMulti {
    static private $stack = [];
    static private $stacExCallback = [];
    /**
     * @var callable(string $returnTransfer, array $curlGetInfo)
     */
    public $callbackOk;
    /**
     * @var callable(string $curl_error, array $curlGetInfo)
     */
    public $callbackError;
    /**
     * @var callable running at the start requests
     */
    public $callbackStartEx;
     /**
     * @var callable() running at the end of all requests
     */
    public $callbackFinishEx;
    
    public $curlHandle;
    /**
     * @var bool remove curl handle at the end of request
     */
    public $isCloseHandle = true;
    
    public function __construct($curlHandle) {
        $this->curlHandle = $curlHandle;
        static::$stack[] = $this;
    }
    public function __destruct() {
        if ($this->isCloseHandle)
            curl_close($this->curlHandle);
    }
    // Running this callbacks at the end of all requests
    public static function addCallbackAllExec(callable $callback) : null {
        if (is_callable($callback)) {
            static::$stacExCallback[] = $callback;
        }
    }
    /**
     * @param bool $recursive is run new handlers, are added to the process of execute
     * @return null
     */

    static function exec(bool $recursive = false): null {

        $callbacksFinishEx = [];


        while (count(self::$stack) > 0) { // recursive request
            
            $chm = curl_multi_init();
            
            foreach (self::$stack as $ch) {
                if (is_callable($ch->callbackStartEx))
                    call_user_func ($ch->callbackStartEx);
                curl_multi_add_handle($chm, $ch->curlHandle);
            }
            $active = null;
            do {
                curl_multi_exec($chm, $active);
                curl_multi_select($chm);
            } while ($active > 0);

            foreach (self::$stack as $k=>$s) {
                
                $info = curl_getinfo(self::$stack[$k]->curlHandle);
                $error = curl_error(self::$stack[$k]->curlHandle);
                
                if ($error) {
                    // run callbackError
                    if (is_callable(self::$stack[$k]->callbackError))
                        call_user_func(self::$stack[$k]->callbackError, $error, $info);
                    
                } else {
                    // run callbackOk
                    $content = curl_multi_getcontent(self::$stack[$k]->curlHandle);
                    if (is_callable(self::$stack[$k]->callbackOk))
                        call_user_func(self::$stack[$k]->callbackOk, $content, $info);
                }
                // Add the handler on the stack to run at the end of
                if (is_callable(self::$stack[$k]->callbackFinishEx))
                    $callbacksFinishEx[] = self::$stack[$k]->callbackFinishEx;
                
                curl_multi_remove_handle($chm, self::$stack[$k]->curlHandle);
                
                //var_dump(count(static::$stack));
                unset(self::$stack[$k]);

            }
            
            curl_multi_close($chm);
            
            if (!$recursive) // not recursive
                break;
        }

        foreach ($callbacksFinishEx as $func) {
            call_user_func($func);
        }
        
        foreach (static::$stacExCallback as $k=>$func) {
            call_user_func($func);
            unset(static::$stacExCallback[$k]);
        }


        return;
    }
}
