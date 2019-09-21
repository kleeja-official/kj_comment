<?php
// Kleeja Plugin
// KJ_COMMENT
// Version: 1.0
// Developer: Kleeja Team

// Prevent illegal run
if (! defined('IN_PLUGINS_SYSTEM'))
{
    exit();
}


// Plugin Basic Information
$kleeja_plugin['kj_comment']['information'] = [
    // The casual name of this plugin, anything can a human being understands
    'plugin_title' => [
        'en' => 'KJ Comment',
        'ar' => 'تعليقات كليجا'
    ],
    // Who wrote this plugin?
    'plugin_developer' => 'Kleeja Team',
    // This plugin version
    'plugin_version' => '1.0',
    // Explain what is this plugin, why should I use it?
    'plugin_description' => [
        'en' => 'Add Comments To Files',
        'ar' => 'إضافة تعليقات على الملفات'
    ],
    // Min version of Kleeja that's requiered to run this plugin
    'plugin_kleeja_version_min' => '3.1.4',
    // Max version of Kleeja that support this plugin, use 0 for unlimited
    'plugin_kleeja_version_max' => '3.9',
    // Should this plugin run before others?, 0 is normal, and higher number has high priority
    'plugin_priority' => 0
];

//after installation message, you can remove it, it's not requiered
$kleeja_plugin['kj_comment']['first_run']['ar'] = '
مكون إضافي لإضافة تعليقات للملف ، شكرًا لك على استخدام هذه الإضافة <br>
';
$kleeja_plugin['kj_comment']['first_run']['en'] = '
a plugin to add comments for each file , thank you to use this plugin <br>
Kleeja Team :)
';


// Plugin Installation function
$kleeja_plugin['kj_comment']['install'] = function ($plg_id) {
    global $SQL , $dbprefix;
    $SQL->query(
        "CREATE TABLE IF NOT EXISTS `{$dbprefix}comments` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `user` int(11) NOT NULL,
            `file_id` int(11) NOT NULL,
            `comment` TEXT NOT NULL,
            `time` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;"
    );

    add_olang([
        'NO_4_GUEST'           => 'You can\'t add comments as guest',
        'COMNTS'               => 'Comments',
        'COMNT'                => 'Comment',
        'ADD_COMNT'            => 'Add a Comment',
        'R_COMMENT_MANAGER'    => 'Comment Manager'
    ],
    'en', $plg_id);

    add_olang([
        'NO_4_GUEST'           => 'لا يمكنك إضافة تعليقات كضيف',
        'COMNTS'               => 'تعليقات',
        'COMNT'                => 'تعليق',
        'ADD_COMNT'            => 'اضف تعليق',
        'R_COMMENT_MANAGER'    => 'مدير التعليقات'
    ],
    'ar', $plg_id);
};


//Plugin update function, called if plugin is already installed but version is different than current
$kleeja_plugin['kj_comment']['update'] = function ($old_version, $new_version) {
};


// Plugin Uninstallation, function to be called at unistalling
$kleeja_plugin['kj_comment']['uninstall'] = function ($plg_id) {
    delete_olang(null, ['ar', 'en'], $plg_id);
};


// Plugin functions
$kleeja_plugin['kj_comment']['functions'] = [
    'b4_showsty_downlaod_id_filename' => function ($args) {
        global $tpl , $usrcp;
        $file_info = $args['file_info'];
        $comments  = [];
        $kj_comment_file_id = $args['file_info']['id'];

        if (! $file_info)
        {
            return;
        }
        define('display_comments', true);
        global $SQL , $dbprefix , $config;
        $comments_query = $SQL->build(
            [
                'SELECT'  => 'c.id , c.user , c.comment , c.file_id , c.time , u.name',
                'FROM'    => $dbprefix . 'comments c',
                'JOINS'   =>
                [
                    [
                        'INNER JOIN' => "{$dbprefix}users u",
                        'ON'         => 'c.user = u.id'
                    ]
                ],
                'WHERE'    => 'c.file_id = ' . $file_info['id'],
                'ORDER BY' => 'c.id DESC'
            ]
        );

        if ($SQL->num_rows($comments_query))
        {
            while ($cmnt = $SQL->fetch($comments_query))
            {
                $cmnt['time'] = kleeja_date($cmnt['time']);
                $cmnt['user_link'] = $config['siteurl'] . ($config['mod_writer'] ? 'fileuser-' . $cmnt['user'] . '.html' : 'ucp.php?go=fileuser&amp;id=' . $cmnt['user']);

                if ($cmnt['user'] == $usrcp->id() || $usrcp->group_id() == 1)
                {
                    $cmnt['del_btn'] = true; // display delete btn if the user is comment auther or admin
                }
                else
                {
                    $cmnt['del_btn'] = false;
                }
                $comments[] = $cmnt;
            }
        }
        return compact('comments', 'kj_comment_file_id');
    },
    'print_Saafooter_func' => function ($args) {
        global $tpl , $config , $usrcp;

        if (! defined('IN_DOWNLOAD') || ! defined('display_comments'))
        {
            return;
        }

        $have_comments = count($args['comments']);

        $formAction    = $config['siteurl'] . 'ucp.php?go=comment&action=add';
        $delFormAction = $config['siteurl'] . 'ucp.php?go=comment&action=del';
        $form_key      = kleeja_add_form_key('comment_for_' . $usrcp->name());
        $is_login      = $usrcp->name();

        $tpl->assign('is_login', $is_login);
        $tpl->assign('our_File_ID', $args['file_info']['id']);
        $tpl->assign('formAction', $formAction);
        $tpl->assign('delFormAction', $delFormAction);
        $tpl->assign('have_comments', $have_comments);
        $tpl->assign('form_key', $form_key);
        $theFooter = $args['footer'];
        $footer = $tpl->display('comment', dirname(__FILE__));
        $footer .= $theFooter;
        return compact('footer');
    } ,

    'default_usrcp_page' => function ($args) {
        global $usrcp , $lang , $SQL , $dbprefix , $config;

        if (g('go') == 'comment')
        {

        // all actions in this hook is only for members
            if (! $usrcp->name())
            {
                return;
            }

            if (! kleeja_check_form_key('comment_for_' . $usrcp->name()))
            {
                kleeja_err($lang['INVALID_FORM_KEY']);

                exit;
            }

            if (g('action') == 'add')
            {
                if (ip('submit') && ip('file_id') && ip('comment') && ! empty(p('file_id')) && ! empty(p('comment')))
                {
                    $SQL->build(
                    [
                        'INSERT' => 'user , file_id , comment , time',
                        'INTO'   => $dbprefix . 'comments',
                        'VALUES' => implode(' , ', [$usrcp->id() , p('file_id') , "'" . $SQL->real_escape(str_replace(['"' , "'"], ['&quot;' ,'&#39;'], p('comment'))) . "'" , time()])
                    ]
                );
                    redirect($config['siteurl'] . 'do.php?id=' . p('file_id'));

                    exit;
                }
            }
            elseif (g('action') == 'del')
            {
                if (ip('del') && ip('file_id') && ip('comment_id'))
                {
                    $SQL->build([
                        'DELETE' => $dbprefix . 'comments',
                        'WHERE'  => 'file_id = ' . p('file_id') . ' AND id = ' . p('comment_id') . ($usrcp->group_id() != 1 ? ' AND user = ' . $usrcp->id() : '')
                    ]);

                    if (ip('admin_form'))
                    {
                        redirect($config['siteurl'] . 'admin/index.php?cp=comment_manager');

                        exit;
                    }
                    redirect($config['siteurl'] . 'do.php?id=' . p('file_id'));

                    exit;
                }
            }
        }
    } ,
    'begin_admin_page' => function ($args) {
        $adm_extensions = $args['adm_extensions'];
        $ext_icons = $args['ext_icons'];
        $adm_extensions[] = 'comment_manager';
        $ext_icons['comment_manager'] = 'comment';
        return compact('adm_extensions', 'ext_icons');
    },

    'not_exists_comment_manager' => function() {
        $include_alternative = dirname(__FILE__) . '/comment_manager.php';

        return compact('include_alternative');
    },
];
