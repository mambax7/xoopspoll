<?php
//
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                  Copyright (c) 2000-2016 XOOPS.org                        //
//                       <http://xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
//  Author: phppp (D.J., infomax@gmail.com)                                  //
//  URL: http://xoopsforge.com, http://xoops.org.cn                          //
//  Project: Article Project                                                 //
//  ------------------------------------------------------------------------ //

// defined('XOOPS_ROOT_PATH') || exit('XOOPS root path not defined');

use XoopsModules\Newbb\Helper;

defined('NEWBB_FUNCTIONS_INI') || include XOOPS_ROOT_PATH . '/modules/newbb/include/functions.ini.php';
newbb_load_object();

/**
 * Class Post
 */
class Post extends XoopsObject
{
    //class Post extends XoopsObject {
    public $attachment_array = [];

    /**
     * Post constructor.
     */
    public function __construct()
    {
        parent::__construct('bb_posts');
        $this->initVar('post_id', XOBJ_DTYPE_INT);
        $this->initVar('topic_id', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('forum_id', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('post_time', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('poster_ip', XOBJ_DTYPE_INT, 0);
        $this->initVar('poster_name', XOBJ_DTYPE_TXTBOX, '');
        $this->initVar('subject', XOBJ_DTYPE_TXTBOX, '', true);
        $this->initVar('pid', XOBJ_DTYPE_INT, 0);
        $this->initVar('dohtml', XOBJ_DTYPE_INT, 0);
        $this->initVar('dosmiley', XOBJ_DTYPE_INT, 1);
        $this->initVar('doxcode', XOBJ_DTYPE_INT, 1);
        $this->initVar('doimage', XOBJ_DTYPE_INT, 1);
        $this->initVar('dobr', XOBJ_DTYPE_INT, 1);
        $this->initVar('uid', XOBJ_DTYPE_INT, 1);
        $this->initVar('icon', XOBJ_DTYPE_TXTBOX, '');
        $this->initVar('attachsig', XOBJ_DTYPE_INT, 0);
        $this->initVar('approved', XOBJ_DTYPE_INT, 1);
        $this->initVar('post_karma', XOBJ_DTYPE_INT, 0);
        $this->initVar('require_reply', XOBJ_DTYPE_INT, 0);
        $this->initVar('attachment', XOBJ_DTYPE_TXTAREA, '');
        $this->initVar('post_text', XOBJ_DTYPE_TXTAREA, '');
        $this->initVar('post_edit', XOBJ_DTYPE_TXTAREA, '');
    }

    // ////////////////////////////////////////////////////////////////////////////////////
    // attachment functions    TODO: there should be a file/attachment management class
    /**
     * @return array|mixed|null
     */
    public function getAttachment()
    {
        if (count($this->attachment_array)) {
            return $this->attachment_array;
        }
        $attachment = $this->getVar('attachment');
        if (empty($attachment)) {
            $this->attachment_array = null;
        } else {
            $this->attachment_array = @unserialize(base64_decode($attachment));
        }

        return $this->attachment_array;
    }

    /**
     * @param $attach_key
     * @return bool
     */
    public function incrementDownload($attach_key)
    {
        if (!$attach_key) {
            return false;
        }
        $this->attachment_array[(string)$attach_key]['num_download']++;

        return $this->attachment_array[(string)$attach_key]['num_download'];
    }

    /**
     * @return bool
     */
    public function saveAttachment()
    {
        $attachment_save = '';
        if (is_array($this->attachment_array) && count($this->attachment_array) > 0) {
            $attachment_save = base64_encode(serialize($this->attachment_array));
        }
        $this->setVar('attachment', $attachment_save);
        $sql = 'UPDATE ' . $GLOBALS['xoopsDB']->prefix('bb_posts') . ' SET attachment=' . $GLOBALS['xoopsDB']->quoteString($attachment_save) . ' WHERE post_id = ' . $this->getVar('post_id');
        if (!$result = $GLOBALS['xoopsDB']->queryF($sql)) {
            //xoops_error($GLOBALS["xoopsDB"]->error());
            return false;
        }

        return true;
    }

    /**
     * @param null $attach_array
     * @return bool
     */
    public function deleteAttachment($attach_array = null)
    {
        $attach_old = $this->getAttachment();
        if (!is_array($attach_old) || count($attach_old) < 1) {
            return true;
        }
        $this->attachment_array = [];

        if ($attach_array === null) {
            $attach_array = array_keys($attach_old);
        } // to delete all!
        if (!is_array($attach_array)) {
            $attach_array = [$attach_array];
        }

        foreach ($attach_old as $key => $attach) {
            if (in_array($key, $attach_array)) {
                @unlink(XOOPS_ROOT_PATH . '/' . $GLOBALS['xoopsModuleConfig']['dir_attachments'] . '/' . $attach['name_saved']);
                @unlink(XOOPS_ROOT_PATH . '/' . $GLOBALS['xoopsModuleConfig']['dir_attachments'] . '/thumbs/' . $attach['name_saved']); // delete thumbnails
                continue;
            }
            $this->attachment_array[$key] = $attach;
        }
        if (is_array($this->attachment_array) && count($this->attachment_array) > 0) {
            $attachment_save = base64_encode(serialize($this->attachment_array));
        } else {
            $attachment_save = '';
        }
        $this->setVar('attachment', $attachment_save);

        return true;
    }

    /**
     * @param string $name_saved
     * @param string $name_display
     * @param string $mimetype
     * @param int    $num_download
     * @return bool
     */
    public function setAttachment($name_saved = '', $name_display = '', $mimetype = '', $num_download = 0)
    {
        static $counter = 0;
        $this->attachment_array = $this->getAttachment();
        if ($name_saved) {
            $key                          = (string)(time() + $counter++);
            $this->attachment_array[$key] = [
                'name_saved'   => $name_saved,
                'name_display' => $name_display ?? $name_saved,
                'mimetype'     => $mimetype,
                'num_download' => isset($num_download) ? (int)$num_download : 0,
            ];
        }
        if (is_array($this->attachment_array)) {
            $attachment_save = base64_encode(serialize($this->attachment_array));
        } else {
            $attachment_save = null;
        }
        $this->setVar('attachment', $attachment_save);

        return true;
    }

    /**
     * TODO: refactor
     * @param bool $asSource
     * @return string
     */
    public function displayAttachment($asSource = false)
    {
        $post_attachment = '';
        $attachments     = $this->getAttachment();
        if (is_array($attachments) && count($attachments) > 0) {
            $iconHandler = newbb_getIconHandler();
            $mime_path   = $iconHandler->getPath('mime');
            include_once $GLOBALS['xoops']->path('modules/' . $GLOBALS['xoopsModule']->getVar('dirname', 'n') . '/include/functions.image.php');
            $image_extensions = ['jpg', 'jpeg', 'gif', 'png', 'bmp']; // need improve !!!
            $post_attachment  .= '<br><strong>' . _MD_ATTACHMENT . '</strong>:';
            $post_attachment  .= "<div style='margin: 1em 0; border-top: 1px solid;'></div>\n";
            //            $post_attachment .= '<br><hr style="height: 1px;" noshade="noshade" /><br>';
            foreach ($attachments as $key => $att) {
                $file_extension = ltrim(strrchr($att['name_saved'], '.'), '.');
                $filetype       = $file_extension;
                if (file_exists($GLOBALS['xoops']->path("{$mime_path}/{$filetype}.gif"))) {
                    $icon_filetype = $GLOBALS['xoops']->url("{$mime_path}/{$filetype}.gif");
                } else {
                    $icon_filetype = $GLOBALS['xoops']->url("{$mime_path}/unknown.gif");
                }
                $file_size = @filesize($GLOBALS['xoops']->path($GLOBALS['xoopsModuleConfig']['dir_attachments'] . '/' . $att['name_saved']));
                $file_size = number_format($file_size / 1024, 2) . ' KB';
                if (in_array(strtolower($file_extension), $image_extensions)
                    && $GLOBALS['xoopsModuleConfig']['media_allowed']) {
                    $post_attachment .= '<br><img src="' . $icon_filetype . '" alt="' . $filetype . '" /><strong>&nbsp; ' . $att['name_display'] . '</strong> <small>(' . $file_size . ')</small>';
                    $post_attachment .= '<br>' . newbb_attachmentImage($att['name_saved']);
                    $isDisplayed     = true;
                } else {
                    if (empty($GLOBALS['xoopsModuleConfig']['show_userattach'])) {
                        $post_attachment .= "<a href='"
                                            . $GLOBALS['xoops']->url('/modules/' . $GLOBALS['xoopsModule']->getVar('dirname', 'n') . "/dl_attachment.php?attachid={$key}&amp;post_id=" . $this->getVar('post_id'))
                                            . "'> <img src='{$icon_filetype}' alt='{$filetype}' /> {$att['name_display']}</a> "
                                            . _MD_FILESIZE
                                            . ": {$file_size}; "
                                            . _MD_HITS
                                            . ": {$att['num_download']}";
                    } elseif (($GLOBALS['xoopsUser'] instanceof XoopsUser) && $GLOBALS['xoopsUser']->uid() > 0
                              && $GLOBALS['xoopsUser']->isActive()) {
                        $post_attachment .= "<a href='"
                                            . $GLOBALS['xoops']->url('/modules/' . $GLOBALS['xoopsModule']->getVar('dirname', 'n') . "/dl_attachment.php?attachid={$key}&amp;post_id=" . $this->getVar('post_id'))
                                            . "'> <img src='"
                                            . $icon_filetype
                                            . "' alt='{$filetype}' /> {$att['name_display']}</a> "
                                            . _MD_FILESIZE
                                            . ": {$file_size}; "
                                            . _MD_HITS
                                            . ": {$att['num_download']}";
                    } else {
                        $post_attachment .= _MD_NEWBB_SEENOTGUEST;
                    }
                }
                $post_attachment .= '<br>';
            }
        }

        return $post_attachment;
    }
    // attachment functions
    // ////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param string $poster_name
     * @param string $post_editmsg
     * @return bool
     */
    public function setPostEdit($poster_name = '', $post_editmsg = '')
    {
        if (empty($GLOBALS['xoopsModuleConfig']['recordedit_timelimit'])
            || (time() - $this->getVar('post_time')) < $GLOBALS['xoopsModuleConfig']['recordedit_timelimit'] * 60
            || $this->getVar('approved') < 1) {
            return true;
        }
        if (($GLOBALS['xoopsUser'] instanceof XoopsUser) && $GLOBALS['xoopsUser']->isActive()) {
            if ($GLOBALS['xoopsModuleConfig']['show_realname'] && $GLOBALS['xoopsUser']->getVar('name')) {
                $edit_user = $GLOBALS['xoopsUser']->getVar('name');
            } else {
                $edit_user = $GLOBALS['xoopsUser']->getVar('uname');
            }
        }
        $post_edit              = [];
        $post_edit['edit_user'] = $edit_user; // The proper way is to store uid instead of name. However, to save queries when displaying, the current way is ok.
        $post_edit['edit_time'] = time();
        $post_edit['edit_msg']  = $post_editmsg;

        $post_edits = $this->getVar('post_edit');
        if (!empty($post_edits)) {
            $post_edits = unserialize(base64_decode($post_edits));
        }
        if (!is_array($post_edits)) {
            $post_edits = [];
        }
        $post_edits[] = $post_edit;
        $post_edit    = base64_encode(serialize($post_edits));
        unset($post_edits);
        $this->setVar('post_edit', $post_edit);

        return true;
    }

    /**
     * @return bool|string
     */
    public function displayPostEdit()
    {
        global $myts;

        if (empty($GLOBALS['xoopsModuleConfig']['recordedit_timelimit'])) {
            return false;
        }

        $post_edit  = '';
        $post_edits = $this->getVar('post_edit');
        if (!empty($post_edits)) {
            $post_edits = unserialize(base64_decode($post_edits));
        }
        if (!isset($post_edits) || !is_array($post_edits)) {
            $post_edits = [];
        }
        if (is_array($post_edits) && count($post_edits) > 0) {
            foreach ($post_edits as $postedit) {
                $edit_time = (int)$postedit['edit_time'];
                $edit_user = ($postedit['edit_user']);
                $edit_msg  = (!empty($postedit['edit_msg'])) ? ($postedit['edit_msg']) : '';
                // Start irmtfan add option to do only the latest edit when do_latestedit=0 (Alfred)
                if (empty($GLOBALS['xoopsModuleConfig']['do_latestedit'])) {
                    $post_edit = '';
                }
                // End irmtfan add option to do only the latest edit when do_latestedit=0 (Alfred)
                // START hacked by irmtfan
                // display/save all edit records.
                $post_edit .= _MD_EDITEDBY . ' ' . $edit_user . ' ' . _MD_ON . ' ' . newbb_formatTimestamp((int)$edit_time) . '<br>';
                // if reason is not empty
                if ($edit_msg !== '') {
                    $post_edit .= _MD_EDITEDMSG . ' ' . $edit_msg . '<br>';
                }
                // START hacked by irmtfan
            }
        }

        return $post_edit;
    }

    /**
     * @return array
     */
    public function &getPostBody()
    {
        global $myts;
        $GLOBALS['xoopsModuleConfig'] = newbb_load_config(); // irmtfan  load all newbb configs - newbb config in blocks activated in some modules like profile
        mod_loadFunctions('user', 'newbb');
        mod_loadFunctions('render', 'newbb');

        $uid          = ($GLOBALS['xoopsUser'] instanceof XoopsUser) ? $GLOBALS['xoopsUser']->getVar('uid') : 0;
        $karmaHandler = Helper::getInstance()->getHandler('Karma');
        $user_karma   = $karmaHandler->getUserKarma();

        $post               = [];
        $post['attachment'] = false;
        $post_text          = newbb_displayTarea($this->vars['post_text']['value'], $this->getVar('dohtml'), $this->getVar('dosmiley'), $this->getVar('doxcode'), $this->getVar('doimage'), $this->getVar('dobr'));
        if (newbb_isAdmin($this->getVar('forum_id')) or $this->checkIdentity()) {
            $post['text'] = $post_text . '<br>' . $this->displayAttachment();
        } elseif ($GLOBALS['xoopsModuleConfig']['enable_karma'] && $this->getVar('post_karma') > $user_karma) {
            $post['text'] = sprintf(_MD_KARMA_REQUIREMENT, $user_karma, $this->getVar('post_karma'));
        } elseif ($GLOBALS['xoopsModuleConfig']['allow_require_reply'] && $this->getVar('require_reply')
                  && (!$uid
                      || !isset($viewtopic_users[$uid]))) {
            $post['text'] = _MD_REPLY_REQUIREMENT;
        } else {
            $post['text'] = $post_text . '<br>' . $this->displayAttachment();
        }
        /** @var \XoopsMemberHandler $memberHandler */
        $memberHandler = xoops_getHandler('member');
        $eachposter    = $memberHandler->getUser($this->getVar('uid'));
        if (is_object($eachposter) && $eachposter->isActive()) {
            if ($GLOBALS['xoopsModuleConfig']['show_realname'] && $eachposter->getVar('name')) {
                $post['author'] = $eachposter->getVar('name');
            } else {
                $post['author'] = $eachposter->getVar('uname');
            }
            unset($eachposter);
        } else {
            $post['author'] = $this->getVar('poster_name') ?: $GLOBALS['xoopsConfig']['anonymous'];
        }

        $post['subject'] = newbb_htmlspecialchars($this->vars['subject']['value']);
        $post['date']    = $this->getVar('post_time');

        return $post;
    }

    /**
     * @return bool
     */
    public function isTopic()
    {
        return !$this->getVar('pid');
    }

    /**
     * @param string $action_tag
     * @return bool
     */
    public function checkTimelimit($action_tag = 'edit_timelimit')
    {
        $newbb_config = newbb_load_config();
        if (empty($newbb_config['edit_timelimit'])) {
            return true;
        }

        return ($this->getVar('post_time') > time() - $newbb_config[$action_tag] * 60);
    }

    /**
     * @param int $uid
     * @return bool
     */
    public function checkIdentity($uid = -1)
    {
        $uid = ($uid > -1) ? $uid : (($GLOBALS['xoopsUser'] instanceof XoopsUser) ? $GLOBALS['xoopsUser']->getVar('uid') : 0);
        if ($this->getVar('uid') > 0) {
            $user_ok = ($uid === $this->getVar('uid')) ? true : false;
        } else {
            static $user_ip;
            if (!isset($user_ip)) {
                $user_ip = XoopsUserUtility::getIP();
            }
            $user_ok = ($user_ip === $this->getVar('poster_ip')) ? true : false;
        }

        return $user_ok;
    }

    // TODO: cleaning up and merge with post hanldings in viewpost.php

    /**
     * @param $isadmin
     * @return array
     */
    public function showPost($isadmin)
    {
        global $myts;
        global $forumUrl, $forumImage;
        global $viewtopic_users, $viewtopic_posters, $forum_obj, $topic_obj, $online, $user_karma, $viewmode, $order, $start, $total_posts, $topic_status;
        static $post_NO = 0;
        static $name_anonymous;

        if (!isset($name_anonymous)) {
            $name_anonymous = htmlspecialchars($GLOBALS['xoopsConfig']['anonymous']);
        }

        mod_loadFunctions('time', 'newbb');
        mod_loadFunctions('render', 'newbb');
        mod_loadFunctions('text', 'newbb'); // irmtfan add text functions

        $post_id  = $this->getVar('post_id');
        $topic_id = $this->getVar('topic_id');
        $forum_id = $this->getVar('forum_id');

        $query_vars              = ['status', 'order', 'start', 'mode', 'viewmode'];
        $query_array             = [];
        $query_array['topic_id'] = "topic_id={$topic_id}";
        foreach ($query_vars as $var) {
            if (!empty($_GET[$var])) {
                $query_array[$var] = "{$var}={$_GET[$var]}";
            }
        }
        $page_query = htmlspecialchars(implode('&', array_values($query_array)));

        $uid = ($GLOBALS['xoopsUser'] instanceof XoopsUser) ? $GLOBALS['xoopsUser']->getVar('uid') : 0;

        ++$post_NO;
        if (strtolower($order) === 'desc') {
            $post_no = $total_posts - ($start + $post_NO) + 1;
        } else {
            $post_no = $start + $post_NO;
        }

        if ($isadmin || $this->checkIdentity()) {
            $post_text       = $this->getVar('post_text');
            $post_attachment = $this->displayAttachment();
        } elseif ($GLOBALS['xoopsModuleConfig']['enable_karma'] && $this->getVar('post_karma') > $user_karma) {
            $post_text       = "<div class='karma'>" . sprintf(_MD_KARMA_REQUIREMENT, $user_karma, $this->getVar('post_karma')) . '</div>';
            $post_attachment = '';
        } elseif ($GLOBALS['xoopsModuleConfig']['allow_require_reply'] && $this->getVar('require_reply')
                  && (!$uid
                      || !in_array($uid, $viewtopic_posters))) {
            $post_text       = "<div class='karma'>" . _MD_REPLY_REQUIREMENT . "</div>\n";
            $post_attachment = '';
        } else {
            $post_text       = $this->getVar('post_text');
            $post_attachment = $this->displayAttachment();
        }
        // START irmtfan add highlight feature
        // Hightlighting searched words
        $post_title = $this->getVar('subject');
        if (isset($_GET['keywords']) && !empty($_GET['keywords'])) {
            $keywords   = htmlspecialchars(trim(urldecode($_GET['keywords'])));
            $post_text  = newbb_highlightText($post_text, $keywords);
            $post_title = newbb_highlightText($post_title, $keywords);
        }
        // END irmtfan add highlight feature
        if (isset($viewtopic_users[$this->getVar('uid')])) {
            $poster = $viewtopic_users[$this->getVar('uid')];
        } else {
            $name   = ($post_name = $this->getVar('poster_name')) ? $post_name : $name_anonymous;
            $poster = [
                'poster_uid' => 0,
                'name'       => $name,
                'link'       => $name,
            ];
        }

        if ($posticon = $this->getVar('icon')) {
            $post_image = "<a name='{$post_id}'><img src='" . $GLOBALS['xoops']->url("images/subject/{$posticon}") . "' alt='' /></a>";
        } else {
            $post_image = "<a name='{$post_id}'><img src='" . $GLOBALS['xoops']->url('images/icons/posticon.gif') . "' alt='' /></a>";
        }

        $thread_buttons = [];
        $mod_buttons    = [];

        if ($isadmin
            && (($GLOBALS['xoopsUser'] instanceof XoopsUser)
                && $GLOBALS['xoopsUser']->getVar('uid') !== $this->getVar('uid'))
            && ($this->getVar('uid') > 0)) {
            $mod_buttons['bann']['image']    = newbb_displayImage('p_bann', _MD_SUSPEND_MANAGEMENT);
            $mod_buttons['bann']['link']     = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/moderate.php?forum={$forum_id}&amp;fuid=" . $this->getVar('uid'));
            $mod_buttons['bann']['name']     = _MD_SUSPEND_MANAGEMENT;
            $thread_buttons['bann']['image'] = newbb_displayImage('p_bann', _MD_SUSPEND_MANAGEMENT);
            $thread_buttons['bann']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/moderate.php?forum={$forum_id}&amp;fuid=" . $this->getVar('uid'));
            $thread_buttons['bann']['name']  = _MD_SUSPEND_MANAGEMENT;
        }

        if ($GLOBALS['xoopsModuleConfig']['enable_permcheck']) {
            /** @var NewbbTopicHandler $topicHandler */
            $topicHandler = Helper::getInstance()->getHandler('Topic');
            $topic_status = $topic_obj->getVar('topic_status');
            if ($topicHandler->getPermission($forum_id, $topic_status, 'edit')) {
                $edit_ok = ($isadmin || ($this->checkIdentity() && $this->checkTimelimit('edit_timelimit')));
                if ($edit_ok) {
                    $thread_buttons['edit']['image'] = newbb_displayImage('p_edit', _EDIT);
                    $thread_buttons['edit']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/edit.php?{$page_query}");
                    $thread_buttons['edit']['name']  = _EDIT;
                    $mod_buttons['edit']['image']    = newbb_displayImage('p_edit', _EDIT);
                    $mod_buttons['edit']['link']     = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/edit.php?{$page_query}");
                    $mod_buttons['edit']['name']     = _EDIT;
                }
            }

            if ($topicHandler->getPermission($forum_id, $topic_status, 'delete')) {
                $delete_ok = ($isadmin || ($this->checkIdentity() && $this->checkTimelimit('delete_timelimit')));

                if ($delete_ok) {
                    $thread_buttons['delete']['image'] = newbb_displayImage('p_delete', _DELETE);
                    $thread_buttons['delete']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/delete.php?{$page_query}");
                    $thread_buttons['delete']['name']  = _DELETE;
                    $mod_buttons['delete']['image']    = newbb_displayImage('p_delete', _DELETE);
                    $mod_buttons['delete']['link']     = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/delete.php?{$page_query}");
                    $mod_buttons['delete']['name']     = _DELETE;
                }
            }
            if ($topicHandler->getPermission($forum_id, $topic_status, 'reply')) {
                $thread_buttons['reply']['image'] = newbb_displayImage('p_reply', _MD_REPLY);
                $thread_buttons['reply']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/reply.php?{$page_query}");
                $thread_buttons['reply']['name']  = _MD_REPLY;

                $thread_buttons['quote']['image'] = newbb_displayImage('p_quote', _MD_QUOTE);
                $thread_buttons['quote']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/reply.php?{$page_query}&amp;quotedac=1");
                $thread_buttons['quote']['name']  = _MD_QUOTE;
            }
        } else {
            $mod_buttons['edit']['image'] = newbb_displayImage('p_edit', _EDIT);
            $mod_buttons['edit']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/edit.php?{$page_query}");
            $mod_buttons['edit']['name']  = _EDIT;

            $mod_buttons['delete']['image'] = newbb_displayImage('p_delete', _DELETE);
            $mod_buttons['delete']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/delete.php?{$page_query}");
            $mod_buttons['delete']['name']  = _DELETE;

            $thread_buttons['reply']['image'] = newbb_displayImage('p_reply', _MD_REPLY);
            $thread_buttons['reply']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/reply.php?{$page_query}");
            $thread_buttons['reply']['name']  = _MD_REPLY;
        }

        if (!$isadmin && $GLOBALS['xoopsModuleConfig']['reportmod_enabled']) {
            $thread_buttons['report']['image'] = newbb_displayImage('p_report', _MD_REPORT);
            $thread_buttons['report']['link']  = $GLOBALS['xoops']->url('modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/report.php?{$page_query}");
            $thread_buttons['report']['name']  = _MD_REPORT;
        }

        $thread_action = [];
        // irmtfan add pdf permission
        if (file_exists($GLOBALS['xoops']->path('Frameworks/tcpdf/tcpdf.php'))
            && $topicHandler->getPermission($forum_id, $topic_status, 'pdf')) {
            $thread_action['pdf']['image']  = newbb_displayImage('pdf', _MD_PDF);
            $thread_action['pdf']['link']   = $GLOBALS['xoops']->url('modules/newbb/makepdf.php?type=post&amp;pageid=0');
            $thread_action['pdf']['name']   = _MD_PDF;
            $thread_action['pdf']['target'] = '_blank';
        }
        // irmtfan add print permission
        if ($topicHandler->getPermission($forum_id, $topic_status, 'print')) {
            $thread_action['print']['image']  = newbb_displayImage('printer', _MD_PRINT);
            $thread_action['print']['link']   = $GLOBALS['xoops']->url("modules/newbb/print.php?form=2&amp;forum={$forum_id}&amp;topic_id={$topic_id}");
            $thread_action['print']['name']   = _MD_PRINT;
            $thread_action['print']['target'] = '_blank';
        }

        if ($GLOBALS['xoopsModuleConfig']['show_sociallinks']) {
            $full_title  = $this->getVar('subject');
            $clean_title = preg_replace('/[^A-Za-z0-9-]+/', '+', $this->getVar('subject'));
            $full_link   = $GLOBALS['xoops']->url("modules/newbb/viewtopic.php?post_id={$post_id}");

            $thread_action['social_twitter']['image']  = newbb_displayImage('twitter', _MD_SHARE_TWITTER);
            $thread_action['social_twitter']['link']   = "http://twitter.com/share?text={$clean_title}&amp;url={$full_link}";
            $thread_action['social_twitter']['name']   = _MD_SHARE_TWITTER;
            $thread_action['social_twitter']['target'] = '_blank';

            $thread_action['social_facebook']['image']  = newbb_displayImage('facebook', _MD_SHARE_FACEBOOK);
            $thread_action['social_facebook']['link']   = "http://www.facebook.com/sharer.php?u={$full_link}";
            $thread_action['social_facebook']['name']   = _MD_SHARE_FACEBOOK;
            $thread_action['social_facebook']['target'] = '_blank';

            $thread_action['social_gplus']['image']  = newbb_displayImage('googleplus', _MD_SHARE_GOOGLEPLUS);
            $thread_action['social_gplus']['link']   = "https://plusone.google.com/_/+1/confirm?hl=en&url={$full_link}";
            $thread_action['social_gplus']['name']   = _MD_SHARE_GOOGLEPLUS;
            $thread_action['social_gplus']['target'] = '_blank';

            $thread_action['social_linkedin']['image']  = newbb_displayImage('linkedin', _MD_SHARE_LINKEDIN);
            $thread_action['social_linkedin']['link']   = "http://www.linkedin.com/shareArticle?mini=true&amp;title={$full_title}&amp;url={$full_link}";
            $thread_action['social_linkedin']['name']   = _MD_SHARE_LINKEDIN;
            $thread_action['social_linkedin']['target'] = '_blank';

            $thread_action['social_delicious']['image']  = newbb_displayImage('delicious', _MD_SHARE_DELICIOUS);
            $thread_action['social_delicious']['link']   = "http://del.icio.us/post?title={$full_title}&amp;url={$full_link}";
            $thread_action['social_delicious']['name']   = _MD_SHARE_DELICIOUS;
            $thread_action['social_delicious']['target'] = '_blank';

            $thread_action['social_digg']['image']  = newbb_displayImage('digg', _MD_SHARE_DIGG);
            $thread_action['social_digg']['link']   = "http://digg.com/submit?phase=2&amp;title={$full_title}&amp;url={$full_link}";
            $thread_action['social_digg']['name']   = _MD_SHARE_DIGG;
            $thread_action['social_digg']['target'] = '_blank';

            $thread_action['social_reddit']['image']  = newbb_displayImage('reddit', _MD_SHARE_REDDIT);
            $thread_action['social_reddit']['link']   = "http://reddit.com/submit?title={$full_title}&amp;url={$full_link}";
            $thread_action['social_reddit']['name']   = _MD_SHARE_REDDIT;
            $thread_action['social_reddit']['target'] = '_blank';

            $thread_action['social_wong']['image']  = newbb_displayImage('wong', _MD_SHARE_MRWONG);
            $thread_action['social_wong']['link']   = "http://www.mister-wong.de/index.php?action=addurl&bm_url=$full_link}";
            $thread_action['social_wong']['name']   = _MD_SHARE_MRWONG;
            $thread_action['social_wong']['target'] = '_blank';
        }

        $post = [
            'post_id'         => $post_id,
            'post_parent_id'  => $this->getVar('pid'),
            'post_date'       => newbb_formatTimestamp($this->getVar('post_time')),
            'post_image'      => $post_image,
            'post_title'      => $post_title,        // irmtfan $post_title to add highlight keywords
            'post_text'       => $post_text,
            'post_attachment' => $post_attachment,
            'post_edit'       => $this->displayPostEdit(),
            'post_no'         => $post_no,
            'post_signature'  => $this->getVar('attachsig') ? @$poster['signature'] : '',
            'poster_ip'       => ($isadmin
                                  && $GLOBALS['xoopsModuleConfig']['show_ip']) ? long2ip($this->getVar('poster_ip')) : '',
            'thread_action'   => $thread_action,
            'thread_buttons'  => $thread_buttons,
            'mod_buttons'     => $mod_buttons,
            'poster'          => $poster,
            'post_permalink'  => "<a href='" . $GLOBALS['xoops']->url('/modules/' . $GLOBALS['xoopsModule']->getVar('dirname') . "/viewtopic.php?post_id={$post_id}") . "'></a>",
        ];

        unset($thread_buttons, $mod_buttons, $eachposter);

        return $post;
    }
}

/**
 * Class NewbbPostHandler
 */
//class NewbbPostHandler extends ArtObjectHandler
class NewbbPostHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param null|XoopsDatabase $db
     */
    public function __construct(XoopsDatabase $db)
    {
        parent::__construct($db, 'bb_posts', 'Post', 'post_id', 'subject');
    }

    /**
     * @param mixed|null $id
     * @return null|XoopsObject
     */
    public function get($id)
    {
        $id   = (int)$id;
        $post = null;
        $sql  = 'SELECT p.*, t.* FROM ' . $this->db->prefix('bb_posts') . ' p LEFT JOIN ' . $this->db->prefix('bb_posts_text') . ' t ON p.post_id=t.post_id WHERE p.post_id=' . $id;
        if ($array = $this->db->fetchArray($this->db->query($sql))) {
            $post = $this->create(false);
            $post->assignVars($array);
        }

        return $post;
    }

    /**
     * @param int $topic_id
     * @param int $limit
     * @param int $approved
     * @return array
     */
    public function &getByLimit($topic_id, $limit, $approved = 1)
    {
        $sql    = 'SELECT p.*, t.*, tp.topic_status FROM '
                  . $this->db->prefix('bb_posts')
                  . ' p LEFT JOIN '
                  . $this->db->prefix('bb_posts_text')
                  . ' t ON p.post_id=t.post_id LEFT JOIN '
                  . $this->db->prefix('bb_topics')
                  . ' tp ON tp.topic_id=p.topic_id WHERE p.topic_id='
                  . $topic_id
                  . ' AND p.approved ='
                  . $approved
                  . ' ORDER BY p.post_time DESC';
        $result = $this->db->query($sql, $limit, 0);
        $ret    = [];
        while ($myrow = $this->db->fetchArray($result)) {
            $post = $this->create(false);
            $post->assignVars($myrow);

            $ret[$myrow['post_id']] = $post;
            unset($post);
        }

        return $ret;
    }

    /**
     * @param $post
     * @return mixed
     */
    public function getPostForPDF($post)
    {
        return $post->getPostBody(true);
    }

    /**
     * @param $post
     * @return mixed
     */
    public function getPostForPrint($post)
    {
        return $post->getPostBody();
    }

    /**
     * @param       $post
     * @param bool  $force
     * @return bool
     */
    public function approve(&$post, $force = false)
    {
        if (empty($post)) {
            return false;
        }
        if (is_numeric($post)) {
            $post = $this->get($post);
        }
        $post_id = $post->getVar('post_id');

        $wasApproved = $post->getVar('approved');
        // irmtfan approve post if the approved = 0 (pending) or -1 (deleted)
        if (empty($force) && $wasApproved > 0) {
            return true;
        }
        $post->setVar('approved', 1);
        $this->insert($post, true);

        /** @var NewbbTopicHandler $topicHandler */
        $topicHandler = Helper::getInstance()->getHandler('Topic');
        $topic_obj    = $topicHandler->get($post->getVar('topic_id'));
        if ($topic_obj->getVar('topic_last_post_id') < $post->getVar('post_id')) {
            $topic_obj->setVar('topic_last_post_id', $post->getVar('post_id'));
        }
        if ($post->isTopic()) {
            $topic_obj->setVar('approved', 1);
        } else {
            $topic_obj->setVar('topic_replies', $topic_obj->getVar('topic_replies') + 1);
        }
        $topicHandler->insert($topic_obj, true);

        /** @var NewbbForumHandler $forumHandler */
        $forumHandler = Helper::getInstance()->getHandler('Forum');
        $forum_obj    = $forumHandler->get($post->getVar('forum_id'));
        if ($forum_obj->getVar('forum_last_post_id') < $post->getVar('post_id')) {
            $forum_obj->setVar('forum_last_post_id', $post->getVar('post_id'));
        }
        $forum_obj->setVar('forum_posts', $forum_obj->getVar('forum_posts') + 1);
        if ($post->isTopic()) {
            $forum_obj->setVar('forum_topics', $forum_obj->getVar('forum_topics') + 1);
        }
        $forumHandler->insert($forum_obj, true);

        // Update user stats
        if ($post->getVar('uid') > 0) {
            /** @var \XoopsMemberHandler $memberHandler */
            $memberHandler = xoops_getHandler('member');
            $poster        = $memberHandler->getUser($post->getVar('uid'));
            if (is_object($poster) && $post->getVar('uid') === $poster->getVar('uid')) {
                $poster->setVar('posts', $poster->getVar('posts') + 1);
                $res = $memberHandler->insertUser($poster, true);
                unset($poster);
            }
        }

        // Update forum stats
        $statsHandler = Helper::getInstance()->getHandler('Stats');
        $statsHandler->update($post->getVar('forum_id'), 'post');
        if ($post->isTopic()) {
            $statsHandler->update($post->getVar('forum_id'), 'topic');
        }

        return true;
    }

    /**
     * @param XoopsObject $post
     * @param bool        $force
     * @return bool
     */
    public function insert(XoopsObject $post, $force = true)
    {
        // Set the post time
        // The time should be 'publish' time. To be adjusted later
        if (!$post->getVar('post_time')) {
            $post->setVar('post_time', time());
        }

        /** @var NewbbTopicHandler $topicHandler */
        $topicHandler = Helper::getInstance()->getHandler('Topic');
        // Verify the topic ID
        if ($topic_id = $post->getVar('topic_id')) {
            $topic_obj = $topicHandler->get($topic_id);
            // Invalid topic OR the topic is no approved and the post is not top post
            if (!$topic_obj//            || (!$post->isTopic() && $topic_obj->getVar("approved") < 1)
            ) {
                return false;
            }
        }
        if (empty($topic_id)) {
            $post->setVar('topic_id', 0);
            $post->setVar('pid', 0);
            $post->setNew();
            $topic_obj = $topicHandler->create();
        }
        $textHandler    = Helper::getInstance()->getHandler('Text');
        $post_text_vars = ['post_text', 'post_edit', 'dohtml', 'doxcode', 'dosmiley', 'doimage', 'dobr'];
        if ($post->isNew()) {
            if (!$topic_id = $post->getVar('topic_id')) {
                $topic_obj->setVar('topic_title', $post->getVar('subject', 'n'));
                $topic_obj->setVar('topic_poster', $post->getVar('uid'));
                $topic_obj->setVar('forum_id', $post->getVar('forum_id'));
                $topic_obj->setVar('topic_time', $post->getVar('post_time'));
                $topic_obj->setVar('poster_name', $post->getVar('poster_name'), true);
                $topic_obj->setVar('approved', $post->getVar('approved'), true);

                if (!$topic_id = $topicHandler->insert($topic_obj, $force)) {
                    $post->deleteAttachment();
                    $post->setErrors('insert topic error');

                    //xoops_error($topic_obj->getErrors());
                    return false;
                }
                $post->setVar('topic_id', $topic_id);

                $pid = 0;
                $post->setVar('pid', 0);
            } elseif (!$post->getVar('pid')) {
                $pid = $topicHandler->getTopPostId($topic_id);
                $post->setVar('pid', $pid);
            }

            $text_obj = $textHandler->create();
            foreach ($post_text_vars as $key) {
                $text_obj->vars[$key] = $post->vars[$key];
            }
            $post->destroyVars($post_text_vars);
            if (!$post_id = parent::insert($post, $force)) {
                return false;
            } else {
                $post->unsetNew();
            }
            $text_obj->setVar('post_id', $post_id);
            if (!$textHandler->insert($text_obj, $force)) {
                $this->delete($post);
                $post->setErrors('post text insert error');

                //xoops_error($text_obj->getErrors());
                return false;
            }
            if ($post->getVar('approved') > 0) {
                $this->approve($post, true);
            }
            $post->setVar('post_id', $post_id);
        } else {
            if ($post->isTopic()) {
                if ($post->getVar('subject') !== $topic_obj->getVar('topic_title')) {
                    $topic_obj->setVar('topic_title', $post->getVar('subject', 'n'));
                }
                if ($post->getVar('approved') !== $topic_obj->getVar('approved')) {
                    $topic_obj->setVar('approved', $post->getVar('approved'));
                }
                $topic_obj->setDirty();
                if (!$result = $topicHandler->insert($topic_obj, $force)) {
                    $post->setErrors('update topic error');

                    //                    xoops_error($topic_obj->getErrors());
                    return false;
                }
            }
            $text_obj =& $textHandler->get($post->getVar('post_id'));
            $text_obj->setDirty();
            foreach ($post_text_vars as $key) {
                $text_obj->vars[$key] = $post->vars[$key];
            }
            $post->destroyVars($post_text_vars);
            if (!$post_id = parent::insert($post, $force)) {
                //                xoops_error($post->getErrors());
                return false;
            } else {
                $post->unsetNew();
            }
            if (!$textHandler->insert($text_obj, $force)) {
                $post->setErrors('update post text error');

                //                xoops_error($text_obj->getErrors());
                return false;
            }
        }

        return $post->getVar('post_id');
    }

    /**
     * @param XoopsObject $post
     * @param bool        $isDeleteOne
     * @param bool        $force
     * @return bool
     */
    public function delete($post, $isDeleteOne = true, $force = false)
    {
        $retVal = false;
        if (($post instanceof Post) && ($post->getVar('post_id') > 0)) {
            if ($isDeleteOne) {
                if ($post->isTopic()) {
                    $criteria = new CriteriaCompo(new Criteria('topic_id', $post->getVar('topic_id')));
                    $criteria->add(new Criteria('approved', 1));
                    $criteria->add(new Criteria('pid', 0, '>'));
                    if (!$this->getPostCount($criteria) > 0) {
                        $retVal = $this->_delete($post, $force);
                    }
                } else {
                    $retVal = $this->_delete($post, $force);
                }
            } else { // want to delete multiple posts
                //@TODO: test replacement of XoopsTree with XoopsObjectTree
                require_once $GLOBALS['xoops']->path('class/tree.php');
                // get tree with this object as the root
                $myObjTree = new XoopsObjectTree($this->getAll(), 'post_id', 'pid', $post->getVar('post_id'));
                $arr       = $myObjtree->getAllChild(); // get all children of this object
                /*
                                require_once $GLOBALS['xoops']->path("class/xoopstree.php");
                                $mytree = new XoopsTree($this->db->prefix("bb_posts"), "post_id", "pid");
                                $arr = $mytree->getAllChild($post->getVar('post_id'));
                */
                // irmtfan - delete children in a reverse order
                $success = true;
                for ($i = count($arr) - 1; $i >= 0; $i--) {
                    $childpost = $this->create(false);
                    $childpost->assignVars($arr[$i]);
                    $thisSuccess = $this->_delete($childpost, $force);
                    $success     = $success && $thisSuccess;
                    unset($childpost);
                }
                if ($success) {
                    // if we successfully deleted all children then try and delete this post
                    $retVal = $this->_delete($post, $force);
                } else {
                    // did not successfully delte all children so don't delete this post
                    $retVal = false;
                }
            }
        }

        return $retVal;
    }

    /**
     * @param       $post
     * @param bool  $force
     * @return bool
     */
    private function _delete($post, $force = false)
    {
        if ((!$post instanceof Post) || (0 === $post->getVar('post_id'))) {
            return false;
        }

        /* Set active post as deleted */
        if (($post->getVar('approved') > 0) && empty($force)) {
            $sql = 'UPDATE ' . $this->db->prefix('bb_posts') . ' SET approved = -1 WHERE post_id = ' . $post->getVar('post_id');
            if (!$result = $this->db->queryF($sql)) {
                //@TODO: add error check here
            }
        } else { /* delete pending post directly */
            $sql = sprintf('DELETE FROM %s WHERE post_id = %u', $this->db->prefix('bb_posts'), $post->getVar('post_id'));
            if (!$result = $this->db->queryF($sql)) {
                $post->setErrors('delte post error: ' . $sql);

                return false;
            }
            $post->deleteAttachment();

            $sql = sprintf('DELETE FROM %s WHERE post_id = %u', $this->db->prefix('bb_posts_text'), $post->getVar('post_id'));
            if (!$result = $this->db->queryF($sql)) {
                $post->setErrors('Could not remove post text: ' . $sql);

                return false;
            }
        }

        if ($post->isTopic()) {
            /** @var NewbbTopicHandler $topicHandler */
            $topicHandler = Helper::getInstance()->getHandler('Topic');
            $topic_obj    = $topicHandler->get($post->getVar('topic_id'));
            if (is_object($topic_obj) && $topic_obj->getVar('approved') > 0 && empty($force)) {
                $topiccount_toupdate = 1;
                $topic_obj->setVar('approved', -1);
                $topicHandler->insert($topic_obj);
                xoops_notification_deletebyitem($GLOBALS['xoopsModule']->getVar('mid'), 'thread', $post->getVar('topic_id'));
            } else {
                if (is_object($topic_obj)) {
                    if ($topic_obj->getVar('approved') > 0) {
                        xoops_notification_deletebyitem($GLOBALS['xoopsModule']->getVar('mid'), 'thread', $post->getVar('topic_id'));
                    }

                    $poll_id = $topic_obj->getVar('poll_id');
                    /** @var XoopsModuleHandler $moduleHandler */
                    /** @var \XoopsModuleHandler $moduleHandler */
                    $moduleHandler = xoops_getHandler('module');
                    if ($poll_id > 0) {
                        $poll_moduleHandler = $moduleHandler->getByDirname('xoopspoll');
                        if (($poll_moduleHandler instanceof XoopsModuleHandler) && $poll_moduleHandler->isactive()) {
                            $pollHandler = xoops_getModuleHandler('poll', 'xoopspoll');
                            if (false !== $pollHandler->deleteAll(new Criteria('poll_id', $poll_id, '='))) {
                                $optionHandler = xoops_getModuleHandler('option', 'xoopspoll');
                                $optionHandler->deleteAll(new Criteria('poll_id', $poll_id, '='));
                                $logHandler = xoops_getModuleHandler('log', 'xoopspoll');
                                $logHandler->deleteAll(new Criteria('poll_id', $poll_id, '='));
                                xoops_comment_delete($GLOBALS['xoopsModule']->getVar('mid'), $poll_id);
                            }
                        } else {
                            $poll_moduleHandler = $moduleHandler->getByDirname('umfrage');
                            if (($poll_moduleHandler instanceof XoopsModuleHandler)
                                && $poll_moduleHandler->isactive()) {
                                include_once $GLOBALS['xoops']->path('modules/umfrage/class/umfrage.php');
                                include_once $GLOBALS['xoops']->path('modules/umfrage/class/umfrageoption.php');
                                include_once $GLOBALS['xoops']->path('modules/umfrage/class/umfragelog.php');
                                include_once $GLOBALS['xoops']->path('modules/umfrage/class/umfragerenderer.php');

                                $poll = new Umfrage($poll_id);
                                if (false !== $poll->delete()) {
                                    (new UmfrageOption())->deleteByPollId($poll_id);
                                    (new UmfrageLog())->deleteByPollId($poll_id);
                                    xoops_comment_delete($GLOBALS['xoopsModule']->getVar('mid'), $poll_id);
                                }
                            }
                        }
                    }
                }

                $sql = sprintf('DELETE FROM %s WHERE topic_id = %u', $this->db->prefix('bb_topics'), $post->getVar('topic_id'));
                if (!$result = $this->db->queryF($sql)) {
                    //                  xoops_error($this->db->error());
                }
                $sql = sprintf('DELETE FROM %s WHERE topic_id = %u', $this->db->prefix('bb_votedata'), $post->getVar('topic_id'));
                if (!$result = $this->db->queryF($sql)) {
                    //                  xoops_error($this->db->error());
                }
            }
        } else {
            $sql = 'UPDATE '
                   . $this->db->prefix('bb_topics')
                   . ' t '
                   . 'LEFT JOIN '
                   . $this->db->prefix('bb_posts')
                   . ' p ON p.topic_id = t.topic_id '
                   . 'SET t.topic_last_post_id = p.post_id '
                   . 'WHERE t.topic_last_post_id = '
                   . $post->getVar('post_id')
                   . ' '
                   . 'AND p.post_id = (SELECT MAX(post_id) FROM '
                   . $this->db->prefix('bb_posts')
                   . ' '
                   . 'WHERE topic_id=t.topic_id)';
            if (!$result = $this->db->queryF($sql)) {
                //@TODO: add error checking here
            }
        }

        $postcount_toupdate = $post->getVar('approved');

        if ($postcount_toupdate > 0) {
            // Update user stats
            if ($post->getVar('uid') > 0) {
                /** @var \XoopsMemberHandler $memberHandler */
                $memberHandler = xoops_getHandler('member');
                $poster        = $memberHandler->getUser($post->getVar('uid'));
                if (is_object($poster) && $post->getVar('uid') === $poster->getVar('uid')) {
                    $poster->setVar('posts', $poster->getVar('posts') - 1);
                    $res = $memberHandler->insertUser($poster, true);
                    unset($poster);
                }
            }
            // irmtfan - just update the pid for approved posts when the post is not topic (pid=0)
            if (!$post->isTopic()) {
                $sql = 'UPDATE ' . $this->db->prefix('bb_posts') . ' SET pid = ' . $post->getVar('pid') . ' WHERE approved=1 AND pid=' . $post->getVar('post_id');
                if (!$result = $this->db->queryF($sql)) {
                    //                  xoops_error($this->db->error());
                }
            }
        }

        return true;
    }

    // START irmtfan enhance getPostCount when there is join (read_mode = 2)

    /**
     * @param null $criteria
     * @param null $join
     * @return int|null
     */
    public function getPostCount($criteria = null, $join = null)
    {
        // If not join get the count from XOOPS/class/model/stats as before
        if (empty($join)) {
            return parent::getCount($criteria);
        }

        $sql = 'SELECT COUNT(*) AS count' . ' ' . 'FROM ' . $this->db->prefix('bb_posts') . ' AS p' . ' ' . 'LEFT JOIN ' . $this->db->prefix('bb_posts_text') . ' ' . 'AS t ON t.post_id = p.post_id';
        // LEFT JOIN
        $sql .= $join;
        // WHERE
        if (\is_object($criteria) && is_subclass_of($criteria,  \CriteriaElement::class)) {
            $sql .= ' ' . $criteria->renderWhere();
        }
        if (!$result = $this->db->query($sql)) {
            //            xoops_error($this->db->error().'<br>'.$sql);
            return null;
        }
        $myrow = $this->db->fetchArray($result);
        $count = $myrow['count'];

        return $count;
    }
    // END irmtfan enhance getPostCount when there is join (read_mode = 2)

    /*
     *@TODO: combining viewtopic.php
     */
    /**
     * @param null $criteria
     * @param int  $limit
     * @param int  $start
     * @param null $join
     * @return array
     */
    public function &getPostsByLimit($criteria = null, $limit = 1, $start = 0, $join = null)
    {
        $ret = [];
        $sql = 'SELECT p.*, t.* ' . 'FROM ' . $this->db->prefix('bb_posts') . ' AS p ' . 'LEFT JOIN ' . $this->db->prefix('bb_posts_text') . ' AS t ON t.post_id = p.post_id';
        if (!empty($join)) {
            $sql .= (substr($join, 0, 1) === ' ') ? $join : ' ' . $join;
        }
        if (\is_object($criteria) && is_subclass_of($criteria,  \CriteriaElement::class)) {
            $sql .= ' ' . $criteria->renderWhere();
            if ($criteria->getSort() !== '') {
                $sql .= ' ORDER BY ' . $criteria->getSort() . ' ' . $criteria->getOrder();
            }
        }
        $result = $this->db->query($sql, (int)$limit, (int)$start);
        if (!$result) {
            //            xoops_error($this->db->error());
            return $ret;
        }
        while ($myrow = $this->db->fetchArray($result)) {
            $post = $this->create(false);
            $post->assignVars($myrow);
            $ret[$myrow['post_id']] = $post;
            unset($post);
        }

        return $ret;
    }

    /**
     * @return bool
     */
    public function synchronization()
    {
        //      $this->cleanOrphan();
        return true;
    }

    /**
     * clean orphan items from database
     *
     * @return bool true on success
     */
    public function cleanOrphan()
    {
        $this->deleteAll(new Criteria('post_time', 0), true, true);
        parent::cleanOrphan($this->db->prefix('bb_topics'), 'topic_id');
        parent::cleanOrphan($this->db->prefix('bb_posts_text'), 'post_id');

        if ($this->mysql_major_version() >= 4) { /* for MySQL 4.1+ */
            $sql = 'DELETE FROM ' . $this->db->prefix('bb_posts_text') . ' ' . 'WHERE (post_id NOT IN ( SELECT DISTINCT post_id FROM ' . $this->table . ') )';
        } else { /* for 4.0+ */
            /* */
            $sql = 'DELETE ' . $this->db->prefix('bb_posts_text') . ' FROM ' . $this->db->prefix('bb_posts_text') . ' ' . 'LEFT JOIN ' . $this->table . ' AS aa ON ' . $this->db->prefix('bb_posts_text') . '.post_id = aa.post_id ' . ' ' . 'WHERE (aa.post_id IS NULL)';
            /* */ // Alternative for 4.1+
            /*
            $sql = "DELETE bb FROM ".$this->db->prefix("bb_posts_text")." AS bb" . " "
                       . "LEFT JOIN ".$this->table." AS aa ON bb.post_id = aa.post_id " . " "
                       . "WHERE (aa.post_id IS NULL)";
            */
        }
        if (!$result = $this->db->queryF($sql)) {
            //            xoops_error($this->db->error());
            return false;
        }

        return true;
    }

    /**
     * clean expired objects from database
     *
     * @param int $expire time limit for expiration
     * @return bool true on success
     */
    public function cleanExpires($expire = 0)
    {
        // irmtfan if 0 no cleanup look include/plugin.php
        if (!func_num_args()) {
            $newbbConfig = newbb_load_config();
            $expire      = isset($newbbConfig['pending_expire']) ? (int)$newbbConfig['pending_expire'] : 7;
            $expire      = $expire * 24 * 3600; // days to seconds
        }
        if (empty($expire)) {
            return false;
        }
        $crit_expire = new CriteriaCompo(new Criteria('approved', 0, '<='));
        //        if (!empty($expire)) {
        $crit_expire->add(new Criteria('post_time', time() - (int)$expire, '<'));

        //        }
        return $this->deleteAll($crit_expire, true/*, true*/);
    }
}
