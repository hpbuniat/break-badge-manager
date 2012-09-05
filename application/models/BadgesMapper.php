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
 * The mapper
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2011-2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/break-badge-manager
 */
class Model_BadgesMapper {

    /**
     * The db-table
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;

    /**
     * The configuration
     *
     * @var Zend_Config
     */
    protected $_oConfig;

    /**
     * Create the mapper
     *
     * @param Zend_Config $oConfig
     */
    public function __construct(Zend_Config $oConfig) {
        $this->_oConfig = $oConfig;
    }

    /**
     * Set the dbtable
     *
     * @param unknown_type $dbTable
     *
     * @throws Exception
     *
     * @return Model_BadgesMapper
     */
    public function setDbTable($dbTable) {
        if (is_string($dbTable) === true) {
            $dbTable = new $dbTable();
        }

        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }

        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * Get the dbtable
     *
     * @return Zend_Db_Table_Abstract
     */
    public function getDbTable() {
        if (null === $this->_dbTable) {
            $this->setDbTable('Model_DbTable_Badges');
        }

        return $this->_dbTable;
    }

    /**
     * Save a badge
     *
     * @param  Model_Badges $oBadge
     *
     * @return Model_BadgesMapper
     */
    public function save(Model_Badges $oBadge) {
        $data = array(
            'status' => $oBadge->getStatus(),
            'ip' => $oBadge->getIp(),
            'started' => date('r', $oBadge->getStarted())
        );

        if (null === ($id = $oBadge->getId())) {
            unset($data['id']);
            $this->getDbTable()->insert($data);
        }
        else {
            $this->getDbTable()->update($data, array(
                'id = ?' => $id
            ));
        }

        return $this;
    }

    /**
     * Delete a badge
     *
     * @param  Model_Badges $oBadge
     *
     * @return bool
     */
    public function delete(Model_Badges $oBadge) {
        $iId = $oBadge->getId();
        $bReturn = false;
        if (is_null($iId) !== true) {
            $db = $this->getDbTable();
            if ($db->delete($db->getAdapter()->quoteInto('id = ?', $iId)) === 1) {
                $bReturn = true;
            }
        }

        return $bReturn;
    }

    /**
     * Find a badge by id
     *
     * @param  int $iId
     * @param  Model_Badges $oBadge
     *
     * @return void|Model_Badges
     */
    public function find($iId, Model_Badges $oBadge) {
        $result = $this->getDbTable()->find($iId);
        if (0 == count($result)) {
            return;
        }

        return $this->_set($oBadge, $result->current());
    }

    /**
     * Find a badge by ip
     *
     * @param  string $sIp
     * @param  Model_Badges $oBadge
     *
     * @return void|Model_Badges
     */
    public function findByIp($sIp, Model_Badges $oBadge) {
        $db = $this->getDbTable();
        $select = $db->select()->where('ip = ?', $sIp)->where('status = ?', Model_Badges::ALLOCATED);
        $result = $db->fetchAll($select);
        if (0 == count($result)) {
            return;
        }

        return $this->_set($oBadge, $result->current());
    }

    /**
     * Find a free badge
     *
     * @param  Model_Badges $oBadge
     *
     * @return void|Model_Badges
     */
    public function findFree(Model_Badges $oBadge) {
        $db = $this->getDbTable();
        $select = $db->select()->where('status = ?', Model_Badges::FREE);
        $result = $db->fetchAll($select);
        if (0 == count($result)) {
            return;
        }

        return $this->_set($oBadge, $result->current());
    }

    /**
     * Get all badges
     *
     * @return multitype:Model_Badges
     */
    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $aBadges = array();
        foreach ($resultSet as $row) {
            $oBadge = $this->_set(new Model_Badges(), $row);
            if ($oBadge->getStatus() === Model_Badges::ALLOCATED and $oBadge->hasExceeded() === true) {
                $oBadge->setStatus(Model_Badges::FREE);
                $this->save($oBadge);
            }

            $aBadges[] = $oBadge;
        }

        return $aBadges;
    }

    /**
     * Set the common params to a badge
     *
     * @param  Model_Badges $oBadge
     * @param  Zend_Db_Table_Row_Abstract $oRow
     *
     * @return Model_Badges
     */
    protected function _set(Model_Badges $oBadge, Zend_Db_Table_Row_Abstract $oRow) {
        $oBadge->setIp($oRow->ip)->setTimelimit($this->_oConfig->duration)->setStarted($oRow->started)->setId($oRow->id)->setStatus($oRow->status);
        return $oBadge;
    }
}

