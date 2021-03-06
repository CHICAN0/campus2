<?php
/**
 * JO Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   JO
 * @package    JO_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Exception.php 20514 2010-01-22 07:57:10Z ralph $
 */

/**
 * @see JO_Db_Exception
 */
require_once 'JO/Db/Exception.php';

/**
 * JO_Db_Statement_Exception
 *
 * @category   JO
 * @package    JO_Db
 * @subpackage Statement
 * @copyright  Copyright (c) 2005-2010 JO Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class JO_Db_Statement_Exception extends JO_Db_Exception
{
    /**
     * Check if this general exception has a specific database driver specific exception nested inside.
     *
     * @return bool
     */
    public function hasChainedException()
    {
        return ($this->getPrevious() !== null);
    }

    /**
     * @return Exception|null
     */
    public function getChainedException()
    {
        return $this->getPrevious();
    }
}
