<?php declare(strict_types=1);

namespace XoopsModules\Xoopspoll;

/*
               XOOPS - PHP Content Management System
                   Copyright (c) 2000-2020 XOOPS.org
                      <https://xoops.org>
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting
 source code which is considered copyrighted (c) material of the
 original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA
*/

/**
 * Log class for the XoopsPoll Module
 *
 * @copyright ::  {@link https://xoops.org/ XOOPS Project}
 * @license   ::  {@link https://www.gnu.org/licenses/gpl-2.0.html GNU GPL 2.0 or later}
 * @subpackage::  class
 * @since     ::  1.40
 * @author    ::  {@link https://www.myweb.ne.jp/ Kazumi Ono (AKA onokazu)}
 **/

use XoopsModules\Xoopspoll\{
    Helper,
    Utility
};

/**
 * Class LogHandler
 */
class LogHandler extends \XoopsPersistableObjectHandler
{
    /**
     * LogHandler::__construct()
     *
     * @param null|\XoopsDatabase $db
     * @param null $helper
     */
    public function __construct(?\XoopsDatabase $db = null, $helper = null)
    {
        parent::__construct($db, 'xoopspoll_log', Log::class, 'log_id');
    }

    /**
     * Delete all log entries by Option ID
     * @param int $option_id
     * @return bool $success
     */
    public function deleteByOptionId(int $option_id): bool
    {
        $criteria = new \Criteria('option_id', $option_id, '=');
        $success  = $this->deleteAll($criteria);

        return $success;
    }

    /**
     * Delete all log entries by Poll ID
     * @param int $pid
     * @return bool $success
     * @uses CriteriaCompo
     */
    public function deleteByPollId(int $pid): bool
    {
        $criteria = new \Criteria('poll_id', $pid, '=');
        $success  = $this->deleteAll($criteria);

        return $success;
    }

    /**
     * Gets all log entries by Poll ID
     * @param int $pid
     * @param string|null $sortby  sort all results by this field
     * @param string $orderby sort order (ASC, DESC)
     * @return array $success
     * @uses CriteriaCompo
     */
    public function getAllByPollId(int $pid, ?string $sortby = null, string $orderby = 'ASC'): array
    {
        $sortby   ??= 'time';
        $ret      = [];
        $criteria = new \CriteriaCompo();
        $criteria->add(new \Criteria('poll_id', $pid, '='));
        $criteria->setSort($sortby);
        $criteria->setOrder($orderby);
        $ret = &$this->getAll($criteria);

        return $ret;
    }

    /**
     * Get the total number of votes by the Poll ID
     * @param int $pid
     * @return int
     * @uses CriteriaCompo
     */
    public function getTotalVotesByPollId(int $pid): int
    {
        $criteria = new \Criteria('poll_id', $pid, '=');
        $numVotes = $this->getCount($criteria);

        return $numVotes;
    }

    /**
     * Get the total number of voters for a specific Poll
     * @param int $pid
     * @return int
     * @uses CriteriaCompo
     */
    public function getTotalVotersByPollId(int $pid): int
    {
        $criteria = new \CriteriaCompo();
        $criteria->add(new \Criteria('poll_id', $pid, '='));
        $criteria->setGroupBy('ip');
        $voterGrps = $this->getCount($criteria);
        //TODO Parameter '$voterGrps' type is not compatible with declaration
        $numVoters = \count($voterGrps);

        return $numVoters;
    }

    /**
     * Get the total number of votes for an option
     * @param int $option_id
     * @return int
     * @uses CriteriaCompo
     */
    public function getTotalVotesByOptionId(int $option_id): int
    {
        $criteria = new \Criteria('option_id', $option_id, '=');
        $votes    = $this->getCount($criteria);

        return $votes;
    }

    /**
     * hasVoted indicates if user (logged in or not) has voted in a poll
     * @param int|null    $pid of the poll the check
     * @param string $ip  the ip address for this voter
     * @param int    $uid the XOOPS user id of this voter (0 for anon)
     * @return bool
     * @uses $_COOKIE
     */
    public function hasVoted(?int $pid, string $ip, int $uid = 0): bool
    {
//        $uid        = (int)$uid;
        $pid        = (int)$pid;
        $voted      = true;
        $votedPolls = Utility::getVoteCookie();
        //        $votedPolls = [];  //TESTING HACK TO BYPASS COOKIES
        $pollHandler = Helper::getInstance()->getHandler('Poll');
        $pollObj     = $pollHandler->get($pid);
        if ($pollObj) {
            $pollStarttime = $pollObj->getVar('start_time');
            $criteria      = new \CriteriaCompo();
            $criteria->add(new \Criteria('poll_id', $pid, '='));
            if ($uid > 0) {
                /**
                 *  {@internal check to see if vote was from before poll was started
                 *  and if so allow voting. This allows voting if poll is restarted
                 *  with new start date or if module is uninstalled and re-installed.}
                 */
                $criteria->add(new \Criteria('user_id', $uid, '='));
                $criteria->add(new \Criteria('time', (int)$pollStarttime, '>='));
                $vCount = $this->getCount($criteria);
                $voted  = $vCount > 0;
            } elseif (!empty($ip) && \filter_var($ip, \FILTER_VALIDATE_IP)) {
                $criteria->add(new \Criteria('ip', $ip, '='));
                $criteria->add(new \Criteria('time', (int)$pollStarttime, '>='));
                $criteria->add(new \Criteria('user_id', 0, '='));
                $vCount = $this->getCount($criteria);
                $voted  = $vCount > 0;
            } elseif (\array_key_exists($pid, $votedPolls) && ((int)$votedPolls[$pid] >= $pollStarttime)) {
                /* Check cookie to see if someone from this system has voted before */
                    $criteria = new \CriteriaCompo();
                    $criteria->add(new \Criteria('poll_id', $pid, '='));
                    $criteria->add(new \Criteria('time', (int)$pollStarttime, '>='));
                    $vCount = $this->getCount($criteria);
                    $voted  = $vCount > 0;
                } else {
                    $voted = false;
            }
        }

        return $voted;
    }
}
