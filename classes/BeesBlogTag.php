<?php
/**
 * 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    Thirty Bees <modules@thirtybees.com>
 *  @copyright 2017 Thirty Bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace BeesBlogModule;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BeesBlogTag
 */
class BeesBlogTag extends \ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var int $id_tag */
    public $id_tag;

    /** @var string $name */
    public $name;
    // @codingStandardsIgnoreEnd

    const PRIMARY = 'id_bees_blog_tag';
    const TABLE = 'bees_blog_tag';

    public static $definition = [
        'table'     => self::TABLE,
        'primary'   => self::PRIMARY,
        'multilang' => true,
        'fields'    => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'lang' => true, 'required' => true, 'db_type' => 'VARCHAR(255)'],
        ],
    ];

    /**
     * Check if tag exists
     *
     * @param string   $tag    Tag name
     * @param int|null $idLang Language ID
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     */
    public static function tagExists($tag, $idLang = null)
    {
        if (empty($idLang)) {
            $idLang = (int) \Context::getContext()->language->id;
        }

        $sql = new \DbQuery();
        $sql->select(self::PRIMARY);
        $sql->from(self::TABLE);
        $sql->where('`id_lang` = '.(int) $idLang);
        $sql->where('`name` = \''.pSQL($tag).'\'');

        return (bool) \Db::getInstance()->getValue($sql);
    }
}
