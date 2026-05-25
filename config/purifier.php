<?php

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'XHTML 1.0 Transitional',
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//%',
            'CSS.AllowTricky' => true,
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
            'AutoFormat.RemoveEmpty.RemoveNbsp' => true,
            'AutoFormat.Linkify' => true,
            'AutoFormat.RemoveSpansWithoutAttributes' => true,
            'HTML.ForbiddenAttributes' => ['onclick', 'onerror', 'onload', 'onmouseover', 'onmouseout', 'onfocus', 'onblur', 'onsubmit', 'onreset', 'onchange', 'onselect', 'onabort', 'onkeypress', 'onkeydown', 'onkeyup'],
        ],
        'custom_definition' => [
            'id' => 'html5-definitions',
            'rev' => 1,
            'debug' => true,
            'elements' => [
                ['section', 'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside', 'Block', 'Flow', 'Common'],
                ['header', 'Block', 'Flow', 'Common'],
                ['footer', 'Block', 'Flow', 'Common'],
                ['main', 'Block', 'Flow', 'Common'],
                ['nav', 'Block', 'Flow', 'Common'],
                ['figure', 'Block', 'Flow', 'Common'],
                ['figcaption', 'Block', 'Flow', 'Common'],
            ],
        ],
        'minimal' => [
            'HTML.Doctype' => 'XHTML 1.0 Transitional',
            'HTML.Allowed' => 'b,strong,i,em,u,a[href|title|target],p,br,span',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
        ],
    ],
];
