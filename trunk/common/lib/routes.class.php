<?php

class SRoutes
{
    private static $modules     = array();
    private static $regexRoutes = array();
    private static $routesMap   = array();
    
    public static function connect($pattern, $options)
    {
        $regex = self::convertRegex($pattern, $options['validate']);
        unset($options['validate']);
        if (!isset($options['module'])) $options['module'] = 'root';
        self::$regexRoutes[$regex] = $options;
        self::$routesMap[$options['module']][$options['controller']][$options['action']] = $pattern;
    }
    
    public static function connectModule($module)
    {
        self::$modules[] = $module;
    }
    
    public static function rewriteUrl($options)
    {
        if (isset(self::$routesMap[$options['module']][$options['controller']][$options['action']]))
        {
            $regex = self::$routesMap[$options['module']][$options['controller']][$options['action']];
            return BASE_DIR.'/'.preg_replace('/\{(\w+)\}/e', "self::getOption('\\1', \$options)", $regex);
        }
        else
        {
            if ($options['module'] == 'root')
                $url = BASE_DIR.'/'.$options['controller'].'/'.$options['action'];
            else
                $url = BASE_DIR.'/'.$options['module'].'/'.$options['controller'].'/'.$options['action'];
                
            foreach ($options as $key => $value)
            {
                if (!in_array($key, array('module', 'controller', 'action'))) $url.= "/{$key}/{$value}";
            }
            return $url;
        }
    }
    
    public static function parseUrl($url)
    {
        foreach(self::$regexRoutes as $regex => $options)
        {
            if (preg_match($regex, $url, $matches))
            {
                // Removes numeric keys from the matches array
                foreach($matches as $key => $match)
                {
                    if (is_int($key)) unset($matches[$key]);
                }
                return array_merge($options, $matches);
            }
        }
        // Else...
        $set = explode('/', $url);
        if (in_array($set[0], self::$modules))
        {
            $options['module']     = $set[0];
            $options['controller'] = $set[1];
            $options['action']     = $set[2];
            $iInit = 3;
        }
        else
        {
            $options['module']     = 'root';
            $options['controller'] = $set[0];
            $options['action']     = $set[1];
            $iInit = 2;
        }
        
        for ($i = $iInit; $i < $count = count($set); $i = $i+2) $options[$set[$i]] = $set[$i+1];
        
        return $options;
    }
    
    public static function convertRegex($regex, $validate)
    {
        return '#^'.preg_replace('/\{(\w+)\}/e', "self::getVarRegex('\\1', \$validate)", $regex).'$#i';
    }
    
    private static function getVarRegex($key, $validate)
    {
        return "(?P<{$key}>{$validate[$key]})";
    }
    
    private static function getOption($key, $options)
    {
        return $options[$key];
    }
}

?>
