<?php

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML5',
            'HTML.Allowed' => 'div,b,strong,i,em,u,a[href|title|target],ul,ol,li,p[style],br,span[style],img[src|alt|width|height],h1,h2,h3,h4,h5,h6,blockquote,code,pre,hr,table,thead,tbody,tr,th,td,iframe[src|width|height|frameborder|allow|allowfullscreen],figure,figcaption',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align,margin,margin-left,margin-right,float,display,width,height',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.RemoveEmpty.RemoveNbsp' => true,
            'AutoFormat.Linkify' => true,
            'AutoFormat.RemoveSpansWithoutAttributes' => true,
            'HTML.ForbiddenAttributes' => ['onclick', 'onerror', 'onload', 'onmouseover', 'onmouseout', 'onfocus', 'onblur', 'onsubmit', 'onreset', 'onchange', 'onselect', 'onabort', 'onkeypress', 'onkeydown', 'onkeyup'],
        ],
        'minimal' => [
            'HTML.Doctype' => 'HTML5',
            'HTML.Allowed' => 'b,strong,i,em,u,a[href|title|target],p,br,span',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
        ],
    ],
];
