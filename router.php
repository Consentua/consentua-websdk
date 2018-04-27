<?php

// Developing using PHP's CLI SAPI doesn't provide .htaccess redirects etc;
// this is an alternative

$p = dirname(__FILE__).$_SERVER['REQUEST_URI'];

if(!file_exists($p))
{
    // Try adding .php to the path
    $pp = $p.'.php';
    if(file_exists($pp))
    {
        include($pp);
        return true;
    }    
}

return false;

