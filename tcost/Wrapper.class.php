<?php



/**
 * Транспорт
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_Wrapper extends plg_ProtoWrapper
{

    /**
     * Описание на табовете
     */
    function description()
    {
       $this->TAB('tcost_FeeZones', 'Навла', 'ceo, tcost');
       $this->title = 'Навла';
    }
}