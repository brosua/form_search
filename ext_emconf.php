<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form search backend',
    'description' => 'Add search functionality to the TYPO3 backend form manager',
    'category' => 'misc',
    'author' => 'Josua Vogel',
    'author_email' => 'j.vogel97@web.de',
    'state' => 'beta',
    'constraints' => [
        'depends' => ['typo3' => '11.5.0-11.99.99'],
        'conflicts' => [],
        'suggests' => []
    ]
];
