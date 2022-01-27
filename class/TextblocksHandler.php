<?php declare(strict_types=1);


namespace XoopsModules\Wgevents;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

/**
 * wgEvents module for xoops
 *
 * @copyright    2021 XOOPS Project (https://xoops.org)
 * @license      GPL 2.0 or later
 * @package      wgevents
 * @since        1.0.0
 * @min_xoops    2.5.11 Beta1
 * @author       Goffy - Wedega - Email:webmaster@wedega.com - Website:https://xoops.wedega.com
 */

use XoopsModules\Wgevents;


/**
 * Class Object Handler Textblocks
 */
class TextblocksHandler extends \XoopsPersistableObjectHandler
{
    /**
     * Constructor
     *
     * @param \XoopsDatabase $db
     */
    public function __construct(\XoopsDatabase $db)
    {
        parent::__construct($db, 'wgevents_textblocks', Textblocks::class, 'tb_id', 'tb_name');
    }

    /**
     * @param bool $isNew
     *
     * @return object
     */
    public function create($isNew = true)
    {
        return parent::create($isNew);
    }

    /**
     * retrieve a field
     *
     * @param int $i field id
     * @param null fields
     * @return \XoopsObject|null reference to the {@link Get} object
     */
    public function get($i = null, $fields = null)
    {
        return parent::get($i, $fields);
    }

    /**
     * get inserted id
     *
     * @param null
     * @return int reference to the {@link Get} object
     */
    public function getInsertId()
    {
        return $this->db->getInsertId();
    }

    /**
     * Get Count Textblocks in the database
     * @param int    $start
     * @param int    $limit
     * @param string $sort
     * @param string $order
     * @return int
     */
    public function getCountTextblocks($start = 0, $limit = 0, $sort = 'tb_id ASC, tb_name', $order = 'ASC')
    {
        $crCountTextblocks = new \CriteriaCompo();
        $crCountTextblocks = $this->getTextblocksCriteria($crCountTextblocks, $start, $limit, $sort, $order);
        return $this->getCount($crCountTextblocks);
    }

    /**
     * Get All Textblocks in the database
     * @param int    $start
     * @param int    $limit
     * @param string $sort
     * @param string $order
     * @return array
     */
    public function getAllTextblocks($start = 0, $limit = 0, $sort = 'tb_id ASC, tb_name', $order = 'ASC')
    {
        $crAllTextblocks = new \CriteriaCompo();
        $crAllTextblocks = $this->getTextblocksCriteria($crAllTextblocks, $start, $limit, $sort, $order);
        return $this->getAll($crAllTextblocks);
    }

    /**
     * Get Criteria Textblocks
     * @param        $crTextblocks
     * @param int $start
     * @param int $limit
     * @param string $sort
     * @param string $order
     * @return int
     */
    private function getTextblocksCriteria($crTextblocks, int $start, int $limit, string $sort, string $order)
    {
        $crTextblocks->setStart($start);
        $crTextblocks->setLimit($limit);
        $crTextblocks->setSort($sort);
        $crTextblocks->setOrder($order);
        return $crTextblocks;
    }
}
