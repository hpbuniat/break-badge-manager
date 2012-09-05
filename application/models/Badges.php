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
 * A badge entity
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2011-2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/break-badge-manager
 */
class Model_Badges {

    /**
     * Indicator for a allocated badge
     *
     * @var int
     */
    const ALLOCATED = 1;

    /**
     * Indicator for a unused badge
     *
     * @var int
     */
    const FREE = 0;

    /**
     * Message, if an allocation was successful
     *
     * @var string
     */
    const SUCCESS_ALLOCATE = 'Das Pausenschild ist jetzt belegt!';

    /**
     * Message, if a de-allocation was successful
     *
     * @var string
     */
    const SUCCESS_FREE = 'Das Pausenschild wurde freigegeben!';

    /**
     * Message, if a badge-allocation was not successful
     *
     * @var string
     */
    const FAILURE_ALLOCATE = 'Das Pausenschild ist bereits belegt oder Sie haben bereits ein Pausenschild!';

    /**
     * Message, if a badge-de-allocation was not successful
     *
     * @var string
     */
    const FAILURE_FREE = 'Das Pausenschild konnte nicht freigegeben werden!';

    /**
     * Message, if a badge is not longer present
     *
     * @var string
     */
    const FAILURE_UNKNOWN = 'Das Pausenschild wurde eingezogen!';

    /**
     * Message, if a badge could not be removed
     *
     * @var string
     */
    const FAILURE_DELETE = 'Es konnte kein Pausenschild entfernt werden.';

    /**
     * The status
     *
     * @var int
     */
    protected $_iStatus;

    /**
     * The badge-id
     *
     * @var int
     */
    protected $_iId;

    /**
     * The timestamp of the last allocation
     *
     * @var int
     */
    protected $_iTimestamp;

    /**
     * The duration-limit
     *
     * @var int
     */
    protected $_iLimit;

    /**
     * The ip-address of the current allocator
     *
     * @var int
     */
    protected $_sIp = '';

    /**
     * Create a badge entity
     *
     * @param array $aOptions
     */
    public function __construct($aOptions = array()) {
        $this->_iStatus = self::FREE;
        if (empty($aOptions) !== true) {
            $aOptions['ip'] = Zend_Controller_Front::getInstance()->getRequest()->getServer('REMOTE_ADDR');
            if (empty($aOptions['allocate']) !== true) {
                $this->setIp($aOptions['ip'])->setId($aOptions['allocate'])->setStatus(self::ALLOCATED)->setStarted(time());
            }
            elseif (empty($aOptions['deallocate']) !== true) {
                $this->setIp($aOptions['ip'])->setId($aOptions['deallocate'])->setStatus(self::FREE);
            }
        }
    }

    /**
     * Set the time-limit
     *
     * @param  int $iLimit
     *
     * @return Model_Badges
     */
    public function setTimelimit($iLimit) {
        $this->_iLimit = (int) $iLimit;
        return $this;
    }

    /**
     * Check if the time-limit is exceeded
     *
     * @return boolean
     */
    public function hasExceeded() {
        return (time() > ($this->_iTimestamp + $this->_iLimit));
    }

    /**
     * Set the status
     *
     * @param  int $iStatus
     *
     * @return Model_Badges
     */
    public function setStatus($iStatus) {
        $this->_iStatus = (int) $iStatus;
        return $this;
    }

    /**
     * Get the status
     *
     * @return int
     */
    public function getStatus() {
        return $this->_iStatus;
    }

    /**
     * Set the allocation time
     *
     * @param  string|int $mTimestamp
     *
     * @return Model_Badges
     */
    public function setStarted($mTimestamp) {
        if (is_numeric($mTimestamp) === false) {
            $mTimestamp = strtotime($mTimestamp);
        }

        $this->_iTimestamp = (int) $mTimestamp;
        return $this;
    }

    /**
     * Get the allocation time as unix-timestamp
     *
     * @return int
     */
    public function getStarted() {
        return $this->_iTimestamp;
    }

    /**
     * Set the id
     *
     * @param  int $iId
     *
     * @return Model_Badges
     */
    public function setId($iId) {
        $this->_iId = (int) $iId;
        return $this;
    }

    /**
     * Get the id
     *
     * @return int
     */
    public function getId() {
        return $this->_iId;
    }

    /**
     * Set the ip
     *
     * @param  string $sIp
     *
     * @return Model_Badges
     */
    public function setIp($sIp) {
        $this->_sIp = (string) $sIp;
        return $this;
    }

    /**
     * Get the ip
     *
     * @return string
     */
    public function getIp() {
        return $this->_sIp;
    }
}

