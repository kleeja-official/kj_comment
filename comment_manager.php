<?php

if (! defined('IN_ADMIN'))
{
    exit;
}

$stylee     = 'comment_manager';
$styleePath = dirname(__FILE__);

$comments_query = 
    [
        'SELECT'  => 'c.id , c.user , c.comment , c.file_id , c.time , u.name , f.real_filename',
        'FROM'    => $dbprefix . 'comments c',
        'JOINS'   =>
        [
            [
                'INNER JOIN' => "{$dbprefix}users u",
                'ON'         => 'c.user = u.id'
            ],
            [
                'INNER JOIN' => "{$dbprefix}files f",
                'ON'         => 'c.file_id = f.id'
            ]
        ],
        'ORDER BY' => 'c.id DESC'
    ];

$all_comments = $SQL->build($comments_query);

if ($num_rows = $SQL->num_rows($all_comments))
{
    $perpage                    = 21;
    $currentPage                = ig('page') ? g('page', 'int') : 1;
    $Pager                      = new Pagination($perpage, $num_rows, $currentPage);
    $start                      = $Pager->getStartRow();
    $linkgoto                   = $config['siteurl'] . 'index.php?cp=comment_manager';
    $page_nums                  = $Pager->print_nums($linkgoto);
    $comments_query['LIMIT']    = "$start, $perpage";
    $all_comments               = $SQL->build($comments_query);
    $comments                   = [];

    while ($cmnt = $SQL->fetch($all_comments))
    {
        $cmnt['time']      = kleeja_date($cmnt['time']);
        $cmnt['user_link'] = $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $cmnt['user'] . '.html' : 'ucp.php?go=fileuser&amp;id=' . $cmnt['user']);

        if ($cmnt['user'] == $usrcp->id() || $usrcp->group_id() == 1)
        {
            $cmnt['del_btn'] = true; // display delete btn if the user is comment auther or admin
        }
        $cmnt['comment'] = (strlen($cmnt['comment']) > 40 ? substr($cmnt['comment'], 0, 40) . '...' : $cmnt['comment']);
        $comments[]      = $cmnt;
    }
}
$delFormAction = $config['siteurl'] . 'ucp.php?go=comment&action=del';
$form_key      = kleeja_add_form_key('comment_for_' . $usrcp->name());
