<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "splitcache".
 *
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array (
    'title' => 'Splitcache',
    'description' => 'Split Cache-Entries to multiple Backends',
    'category' => 'misc',
    'version' => '0.0.3',
    'module' => '',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 1,
    'lockType' => '',
    'author' => 'Oliver Gassner',
    'author_email' => 'og@plan2.net',
    'author_company' => 'plan2.net',
    'constraints' => array (
        'depends' => array(
            'typo3' => '8.7.0-8.7.99',
        ),
        'conflicts' => array (
        ),
        'suggests' => array (
        ),
    ),
);