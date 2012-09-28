<?php
/**
 * break-badge-manager
 *
 * Copyright (c) 2011-2012, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package break-badge-manager
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2011-2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Base-Controller
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2011-2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/break-badge-manager
 */
class IndexController extends Zend_Controller_Action {

    /**
     * Init the controller
     */
    public function init() {
        $this->view->badgeConfig = Zend_Registry::get('_config')->model->badge;
        $this->view->sSessionId = Zend_Session::getId();
    }

    /**
     * Provide an overview
     */
    public function indexAction() {
        $oBadges = new Model_BadgesMapper($this->view->badgeConfig);
        $this->view->badges = $oBadges->fetchAll();
    }

    /**
     * Add/Remove Badges
     */
    public function manageAction() {
        if ($this->getRequest()->isPost()) {
            if ($this->view->badgeConfig->password === $this->_getParam('password')) {
                $oMapper = new Model_BadgesMapper($this->view->badgeConfig);
                $oBadge = new Model_Badges();
                switch ($this->_getParam('sign')) {
                    case 'plus':
                        $oMapper->save($oBadge);
                        break;

                    case 'minus':
                        $bDelete = false;
                        $oBadge = $oMapper->findFree($oBadge);
                        if ($oBadge instanceof Model_Badges) {
                            $bDelete = $oMapper->delete($oBadge);
                        }

                        if ($bDelete !== true) {
                            $this->view->sFailureNotice = Model_Badges::FAILURE_DELETE;
                        }

                        break;

                    default:
                        /* nop */
                        break;
                }
            }
        }

        $this->_forward('index');
    }

    /**
     * Allocate/deallocate a badge
     */
    public function allocateAction() {
        if ($this->getRequest()->isPost()) {

            $oMapper = new Model_BadgesMapper($this->view->badgeConfig);
            $oBadge = new Model_Badges($this->_getAllParams());

            $oPresentBadget = $oMapper->find($oBadge->getId(), new Model_Badges());
            if ($oPresentBadget instanceof Model_Badges) {
                if ($oPresentBadget->getStatus() === Model_Badges::ALLOCATED) {
                    if ($oPresentBadget->getSession() === $oBadge->getSession()) {
                        $oMapper->save($oBadge);
                        $this->view->sSuccessNotice = Model_Badges::SUCCESS_FREE;
                    }
                    else {
                        $this->view->sFailureNotice = Model_Badges::FAILURE_FREE;
                    }
                }
                else {
                    $oPresentBadget = $oMapper->findBySessionId($oBadge->getSession(), new Model_Badges());
                    if ($oPresentBadget instanceof Model_Badges) {
                        $this->view->sFailureNotice = Model_Badges::FAILURE_ALLOCATE;
                    }
                    else {
                        $oMapper->save($oBadge);
                        $this->view->sSuccessNotice = Model_Badges::SUCCESS_ALLOCATE;
                    }
                }
            }
            else {
                $this->view->sFailureNotice = Model_Badges::FAILURE_UNKNOWN;
            }
        }

        $this->_forward('index');
    }
}

